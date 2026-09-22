<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Wallet;

use Exception;
use PHPUnit\Framework\TestCase;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Wallet\Wallet;

/**
 * Payment channel claims are signed and verified locally, without a node.
 *
 * The vectors are those of xrpl.js: the claim blob from
 * ripple-binary-codec's signing-data-encoding test, the two signatures from
 * the authorizeChannel test. A secp256k1 signature is deterministic (RFC 6979)
 * and an Ed25519 one always, so both compare byte for byte.
 */
final class PaymentChannelClaimTest extends TestCase
{
    private const CHANNEL = '5DB01B7FFED6B67E6B0414DED11E051D2EE2B7619CE0EAA6286D67A3A4D5BDB3';

    private const AMOUNT = '1000000';

    private const SECP256K1_SEED = 'snGHNrPbHrdUcszeuDEigMdC1Lyyd';

    private const SECP256K1_SIGNATURE = '304402204E7052F33DDAFAAA55C9F5B132A5E50EE95B2CF68C0902F61DFE77299BC893740220353640B951DCD24371C16868B3F91B78D38B6F3FD1E826413CDF891FA8250AAC';

    private const ED25519_SEED = 'sEdSuqBPSQaood2DmNYVkwWTn1oQTj2';

    private const ED25519_SIGNATURE = '7E1C217A3E4B3C107B7A356E665088B4FBA6464C48C58267BEF64975E3375EA338AE22E6714E3F5E734AE33E6B97AAD59058E1E196C1F92346FC1498D0674404';

    public function testEncodeForSigningClaim(): void
    {
        $channel = '43904CBFCDCEC530B4037871F86EE90BF799DF8D2E0EA564BC8A3F332E4F5FB1';

        $this->assertSame(
            '434C4D00' . $channel . '00000000000003E8',
            (new BinaryCodec())->encodeForSigningClaim(['channel' => $channel, 'amount' => '1000'])
        );
    }

    public function testEncodeForSigningClaimNormalizesCase(): void
    {
        $codec = new BinaryCodec();

        $this->assertSame(
            $codec->encodeForSigningClaim(['channel' => self::CHANNEL, 'amount' => 1]),
            $codec->encodeForSigningClaim(['channel' => strtolower(self::CHANNEL), 'amount' => '1'])
        );
    }

    public function testSignsLikeXrplJsWithSecp256k1(): void
    {
        $wallet = Wallet::fromSeed(self::SECP256K1_SEED);

        $this->assertSame(
            self::SECP256K1_SIGNATURE,
            $wallet->signPaymentChannelClaim(self::CHANNEL, self::AMOUNT)
        );
    }

    public function testSignsLikeXrplJsWithEd25519(): void
    {
        $wallet = Wallet::fromSeed(self::ED25519_SEED);

        $this->assertSame(
            self::ED25519_SIGNATURE,
            $wallet->signPaymentChannelClaim(self::CHANNEL, self::AMOUNT)
        );
    }

    public function testVerifiesXrplJsSignatures(): void
    {
        $secp = Wallet::fromSeed(self::SECP256K1_SEED);
        $ed = Wallet::fromSeed(self::ED25519_SEED);

        $this->assertTrue(Wallet::verifyPaymentChannelClaim(
            self::CHANNEL, self::AMOUNT, self::SECP256K1_SIGNATURE, $secp->getPublicKey()
        ));
        $this->assertTrue(Wallet::verifyPaymentChannelClaim(
            self::CHANNEL, self::AMOUNT, self::ED25519_SIGNATURE, $ed->getPublicKey()
        ));
    }

    /**
     * A claim over another amount, another channel or another key is not
     * this claim.
     */
    public function testRejectsWhatWasNotSigned(): void
    {
        $wallet = Wallet::generate();
        $other = Wallet::generate();
        $signature = $wallet->signPaymentChannelClaim(self::CHANNEL, self::AMOUNT);

        $this->assertTrue(Wallet::verifyPaymentChannelClaim(self::CHANNEL, self::AMOUNT, $signature, $wallet->getPublicKey()));
        $this->assertFalse(Wallet::verifyPaymentChannelClaim(self::CHANNEL, '1000001', $signature, $wallet->getPublicKey()));
        $this->assertFalse(Wallet::verifyPaymentChannelClaim(strrev(self::CHANNEL), self::AMOUNT, $signature, $wallet->getPublicKey()));
        $this->assertFalse(Wallet::verifyPaymentChannelClaim(self::CHANNEL, self::AMOUNT, $signature, $other->getPublicKey()));
    }

    /**
     * Drops go up to 10^17, beyond PHP_INT_MAX on no platform but well beyond
     * 32 bits; the amount is a UInt64 either way.
     */
    public function testLargeAmount(): void
    {
        $wallet = Wallet::generate();
        $amount = '100000000000000000';
        $signature = $wallet->signPaymentChannelClaim(self::CHANNEL, $amount);

        $this->assertTrue(Wallet::verifyPaymentChannelClaim(self::CHANNEL, $amount, $signature, $wallet->getPublicKey()));
    }

    public function testRejectsMalformedChannel(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Channel ID has to be 64 hex characters');

        Wallet::generate()->signPaymentChannelClaim('invalid-id', self::AMOUNT);
    }

    public function testRejectsMalformedAmount(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Claim amount has to be a number of drops');

        Wallet::generate()->signPaymentChannelClaim(self::CHANNEL, '1.5');
    }

    public function testRejectsUnknownKeyType(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Key Type not recognized');

        Wallet::verifyPaymentChannelClaim(self::CHANNEL, self::AMOUNT, self::ED25519_SIGNATURE, 'FF00');
    }
}
