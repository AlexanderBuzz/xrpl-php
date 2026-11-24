<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Wallet;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Exceptions\ValidationException;
use Hardcastle\XRPL_PHP\Utils\Hashes\HashLedger;
use Hardcastle\XRPL_PHP\Wallet\Wallet;
use PHPUnit\Framework\TestCase;

/**
 * Verifying a blob somebody else signed, and refusing the non-canonical
 * forms of a signature that rippled refuses: each of them is a second blob
 * for the same transaction, with another hash.
 */
final class VerifySignatureTest extends TestCase
{
    /**
     * secp256k1, the verifyTransaction vector of xrpl.js
     * (packages/xrpl/test/wallet/index.test.ts), signed with
     * ssL9dv2W5RK8L3tuzQxYY6EaZhSxW; WalletTest uses it too.
     */
    private const SECP_BLOB = '1200002400000001614000000001312D0068400000000000000C7321030E58CDD076E798C84755590AAF6237CA8FAE821070A59F648B517A30DC6F589D74473045022100CAF99A63B241F5F62B456C68A593D2835397101533BB5D0C4DC17362AC22046F022016A2CA2CF56E777B10E43B56541A4C2FB553E7E298CDD39F7A8A844DA491E51D81142AF1861DEC1316AEEC995C94FF9E2165B1B784608314FDB08D07AAA0EB711793A3027304D688E10C3648';

    /** The same signature with s replaced by n - s. */
    private const SECP_HIGH_S = '3046022100CAF99A63B241F5F62B456C68A593D2835397101533BB5D0C4DC17362AC22046F022100E95D35D30A918884EF1BC4A9ABE5B3CF055AF504167ACC9C4547DA3F2BA45C24';

    /** The same signature with r padded by a needless zero byte. */
    private const SECP_PADDED_R = '304602220000CAF99A63B241F5F62B456C68A593D2835397101533BB5D0C4DC17362AC22046F022016A2CA2CF56E777B10E43B56541A4C2FB553E7E298CDD39F7A8A844DA491E51D';

    /** Ed25519, a Payment signed here with sEdTM1uX8pu2do5XvTnutH6HsouMaM2 (Ed25519 signing is deterministic). */
    private const ED_BLOB = '120000220000000024000000016140000000000003E868400000000000000C7321EDA57EBBCB502C2009EFE17229E8DC865DCCB192C52D7888D624DC9EBADDB815F07440745F6DCAD69267FC59CDC677EFEC5FB96A21C0ECD9E48CA5A086CD8203AFA89F77EBEE25EDF6BB2F53E6364F7E8F265566FAB560A50A935F791B9A0120768C0B8114A6070B8A1822E3322676A99F0C804EE2D15B82708314F667B0CA50CC7709A220B0561B85E53A48461FA8';

    private const ED_HASH = '338CBF595319A3CA9A0D3B7D7A5CE943BFAEAC824A384241E1E12F138D456D9E';

    /** The same signature with S replaced by S + L. */
    private const ED_S_PLUS_L = '745F6DCAD69267FC59CDC677EFEC5FB96A21C0ECD9E48CA5A086CD8203AFA89F64BFE482075ACE8729832EF25C89056A66FAB560A50A935F791B9A0120768C1B';

    public function testVerifiesAgainstTheSigningPubKeyOfTheTransaction(): void
    {
        $this->assertTrue(Wallet::verifySignature(self::SECP_BLOB));
        $this->assertTrue(Wallet::verifySignature(self::ED_BLOB));
        $this->assertTrue(Wallet::verifySignature((new BinaryCodec())->decode(self::ED_BLOB)));
    }

    public function testVerifyTransactionChecksTheWalletsOwnKey(): void
    {
        $other = Wallet::fromSeed('sEdTM1uX8pu2do5XvTnutH6HsouMaM2');

        $this->assertTrue($other->verifyTransaction(self::ED_BLOB));
        $this->assertFalse($other->verifyTransaction(self::SECP_BLOB));
    }

    public function testAGivenKeyOverridesSigningPubKey(): void
    {
        $edKey = Wallet::fromSeed('sEdTM1uX8pu2do5XvTnutH6HsouMaM2')->getPublicKey();

        $this->assertFalse(Wallet::verifySignature(self::SECP_BLOB, $edKey));
        $this->assertTrue(Wallet::verifySignature(self::ED_BLOB, strtolower($edKey)));
    }

    public function testAKeyThatIsNoKeyVerifiesNothing(): void
    {
        $this->assertFalse(Wallet::verifySignature(self::SECP_BLOB, '02' . str_repeat('00', 32)));
        $this->assertFalse(Wallet::verifySignature(self::SECP_BLOB, 'nonsense'));
    }

    public function testRefusesAHighS(): void
    {
        $this->assertFalse(Wallet::verifySignature(self::withSignature(self::SECP_BLOB, self::SECP_HIGH_S)));
    }

    public function testRefusesNonStrictDer(): void
    {
        $this->assertFalse(Wallet::verifySignature(self::withSignature(self::SECP_BLOB, self::SECP_PADDED_R)));
    }

    public function testRefusesAnEd25519SAboveTheGroupOrder(): void
    {
        $malleated = self::withSignature(self::ED_BLOB, self::ED_S_PLUS_L);

        $this->assertNotSame(self::ED_HASH, HashLedger::hashSignedTx($malleated));
        $this->assertFalse(Wallet::verifySignature($malleated));
    }

    public function testAnUnsignedTransactionIsAnError(): void
    {
        $tx = (new BinaryCodec())->decode(self::ED_BLOB);
        unset($tx['TxnSignature']);

        $this->expectException(ValidationException::class);
        Wallet::verifySignature($tx);
    }

    public function testAMultiSignedTransactionIsAnError(): void
    {
        $tx = (new BinaryCodec())->decode(self::ED_BLOB);
        unset($tx['TxnSignature']);
        $tx['SigningPubKey'] = '';

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('multi-signed');
        Wallet::verifySignature($tx);
    }

    public function testATransactionWithoutSigningPubKeyNeedsAKey(): void
    {
        $tx = (new BinaryCodec())->decode(self::ED_BLOB);
        unset($tx['SigningPubKey']);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('SigningPubKey');
        Wallet::verifySignature($tx);
    }

    private static function withSignature(string $blob, string $signature): string
    {
        $codec = new BinaryCodec();
        $tx = $codec->decode($blob);
        $tx['TxnSignature'] = $signature;

        return $codec->encode($tx);
    }
}
