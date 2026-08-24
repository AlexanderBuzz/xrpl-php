<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;

/**
 * Conformance test for the ledger object types introduced by MPTokensV1,
 * Credentials, PermissionedDomains and PermissionedDEX.
 *
 * The reference fixtures of ripple-binary-codec predate these amendments and
 * contain none of them, so ledger-object-fixtures.json was captured from XRPL
 * Testnet instead: examples/mptoken.php and examples/permissioned-domain.php
 * created the objects, and both the binary and the JSON come from rippled via
 * `ledger_entry` / `account_objects`. The accounts are throwaway Testnet
 * wallets, funded by the faucet, whose seeds exist nowhere.
 *
 * Two documented adjustments were applied to rippled's JSON:
 *
 *   - UInt64 node fields (OwnerNode, IssuerNode, …) are stored in the canonical
 *     16 character hex form. rippled's JSON API trims them to "0", the binary
 *     codec and xrpl.js do not.
 *   - MPTokenIssuance.mpt_issuance_id was removed. rippled computes it for the
 *     API response, it is not part of the serialized object.
 *
 * This covers paths the reference fixtures leave untouched: Hash192 decoding,
 * MPTAmount as a base 10 UInt64, MPTokenMetadata, AcceptedCredentials and
 * DomainID on an Offer.
 */
final class LedgerObjectFixturesTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private BinaryCodec $binaryCodec;

    protected function setUp(): void
    {
        $this->binaryCodec = new BinaryCodec();
    }

    public static function ledgerObjectProvider(): array
    {
        $raw = json_decode(file_get_contents(__DIR__ . "/ledger-object-fixtures.json"), true);

        $cases = [];
        foreach ($raw['ledgerObjects'] as $case) {
            $cases[$case['type']] = [$case];
        }

        return $cases;
    }

    #[DataProvider('ledgerObjectProvider')]
    public function testLedgerObjectFixture(array $case): void
    {
        $this->assertEquals(
            $case['binary'],
            strtoupper($this->binaryCodec->encode(json_encode($case['json']))),
            'encode'
        );

        $this->assertEquals(
            self::normalize($case['json']),
            self::normalize($this->binaryCodec->decode($case['binary'])),
            'decode'
        );
    }

    /**
     * The MPT amount fields are the one place where a UInt64 is rendered in
     * base 10 rather than as a hex string.
     */
    public function testMptAmountIsRenderedInBase10(): void
    {
        $cases = self::ledgerObjectProvider();

        $issuance = $this->binaryCodec->decode($cases['MPTokenIssuance'][0]['binary']);
        $this->assertEquals('600', $issuance['OutstandingAmount']);
        $this->assertEquals('100000000', $issuance['MaximumAmount']);

        $mpToken = $this->binaryCodec->decode($cases['MPToken'][0]['binary']);
        $this->assertEquals('600', $mpToken['MPTAmount']);

        // A plain UInt64 in the same object stays hex
        $this->assertEquals('0000000000000000', $mpToken['OwnerNode']);
    }

    /**
     * MPTokenIssuanceID is a Hash192, which the codec did not support at all
     * before MPTokensV1 was implemented.
     */
    public function testMpTokenIssuanceIdIsHash192(): void
    {
        $cases = self::ledgerObjectProvider();
        $mpToken = $this->binaryCodec->decode($cases['MPToken'][0]['binary']);

        $this->assertEquals(48, strlen($mpToken['MPTokenIssuanceID']));
        $this->assertEquals(
            '0133F3337C33F843BE3AED87959F8C52EDEF1E89897E3A6A',
            $mpToken['MPTokenIssuanceID']
        );
    }

    /**
     * Sort object keys recursively so that only the content is compared
     */
    private static function normalize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $normalized = array_map(self::normalize(...), $value);
        if (!array_is_list($normalized)) {
            ksort($normalized);
        }

        return $normalized;
    }
}
