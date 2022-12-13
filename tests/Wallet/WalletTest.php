<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Wallet;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use XRPL_PHP\Core\RippleKeyPairs\Secp256k1KeyPairService;
use XRPL_PHP\Wallet\Wallet;

final class WalletTest extends TestCase
{
    private const CLASSIC_ADDRESS_PREFIX = 'r';
    private const ED25519_KEY_PRERFIX = 'ED';
    private const SECP256K1_PRIVATE_KEY_PREFIX = '00';

    public function testConstructor(): void
    {
        $masterAddress = 'rUAi7pipxGpYfPNg3LtPcf2ApiS8aw9A93';

        $regularKeyPair = [
            'publicKey' => '03AEEFE1E8ED4BBC009DE996AC03A8C6B5713B1554794056C66E5B8D1753C7DD0E',
            'privateKey' => '004265A28F3E18340A490421D47B2EB8DBC2C0BF2C24CEFEA971B61CED2CABD233',
        ];

        $wallet = new Wallet(
            publicKey: $regularKeyPair['publicKey'],
            privateKey: $regularKeyPair['privateKey'],
            masterAddress: $masterAddress
        );

        $this->assertEquals($regularKeyPair['publicKey'], $wallet->getPublicKey());
        $this->assertEquals($regularKeyPair['privateKey'], $wallet->getPrivateKey());
        $this->assertEquals($masterAddress, $wallet->getClassicAddress());
    }

    public function testGenerate(): void
    {
        $wallet = Wallet::generate();

        $this->assertNotEmpty($wallet->getPublicKey());
        $this->assertNotEmpty($wallet->getPrivateKey());
        $this->assertNotEmpty($wallet->getClassicAddress());
    }

    public function testGenerateDefaultAlgorithm(): void
    {
        $wallet = Wallet::generate();

        $this->assertTrue(str_starts_with($wallet->getPublicKey(), self::ED25519_KEY_PRERFIX));
        $this->assertTrue(str_starts_with($wallet->getPrivateKey(), self::ED25519_KEY_PRERFIX));
        $this->assertTrue(str_starts_with($wallet->getClassicAddress(), self::CLASSIC_ADDRESS_PREFIX));

        $this->assertEquals(
            $wallet->getClassicAddress(),
            $wallet->getAddress()
        );
    }

    public function testGenerateSecp256k1(): void
    {
        $wallet = Wallet::generate(KeyPair::EC);

        $this->assertTrue(str_starts_with($wallet->getPrivateKey(), self::SECP256K1_PRIVATE_KEY_PREFIX));
        $this->assertTrue(str_starts_with($wallet->getClassicAddress(), self::CLASSIC_ADDRESS_PREFIX));
    }

    public function testGenerateEd25519(): void
    {
        $wallet = Wallet::generate(KeyPair::EDDSA);

        $this->assertTrue(str_starts_with($wallet->getPublicKey(), self::ED25519_KEY_PRERFIX));
        $this->assertTrue(str_starts_with($wallet->getPrivateKey(), self::ED25519_KEY_PRERFIX));
        $this->assertTrue(str_starts_with($wallet->getClassicAddress(), self::CLASSIC_ADDRESS_PREFIX));
    }

    public function testSignSuccessfully(): void
    {
        $wallet = Wallet::fromSeed(
            seed: 'ss1x3KLrSvfg7irFc1D929WXZ7z9H',
            type: KeyPair::EC
        );

        //TODO: make fromSeed() recognize KeyPair type

        $expected = [
            'tx_blob' => '12000322800000002400000017201B0086955368400000000000000C732102A8A44DB3D4C73EEEE11DFE54D2029103B776AA8A8D293A91D645977C9DF5F54474463044022025464FA5466B6E28EEAD2E2D289A7A36A11EB9B269D211F9C76AB8E8320694E002205D5F99CB56E5A996E5636A0E86D029977BEFA232B7FB64ABA8F6E29DC87A9E89770B6578616D706C652E636F6D81145E7B112523F68D2F5E879DB4EAC51C6698A69304',
            'hash' => '93F6C6CE73C092AA005103223F3A1F557F4C097A2943D96760F6490F04379917',
        ];

        $tx = [
            "TransactionType" => "AccountSet",
            "Flags" => 2147483648,
            "Sequence" => 23,
            "LastLedgerSequence" => 8820051,
            "Fee" => "12",
            "SigningPubKey" => "02A8A44DB3D4C73EEEE11DFE54D2029103B776AA8A8D293A91D645977C9DF5F544",
            "Domain" => "6578616D706C652E636F6D",
            "Account" => "r9cZA1mLK5R5Am25ArfXFmqgNwjZgnfk59"
        ];

        $this->assertEquals(
            $expected,
            $wallet->sign($tx)
        );

    }

    /*
    public function testSignWithMultisignAddress(): void
    {

    }
    */

    public function testSignEncodedTxWithSecp256k1Key(): void
    {
        $privateKey = "0001080EC4673A7296598091E712B58591065C21294F117F7AD69C104517EC7B64";
        $encodedTx = "5354580012000322800000002400000017201B0086955368400000000000000C732102A8A44DB3D4C73EEEE11DFE54D2029103B776AA8A8D293A91D645977C9DF5F544770B6578616D706C652E636F6D81145E7B112523F68D2F5E879DB4EAC51C6698A69304";
        $expectedSignature = "3044022025464FA5466B6E28EEAD2E2D289A7A36A11EB9B269D211F9C76AB8E8320694E002205D5F99CB56E5A996E5636A0E86D029977BEFA232B7FB64ABA8F6E29DC87A9E89";

        $secp256k1 = Secp256k1KeyPairService::getInstance();

        $this->assertEquals(
            $expectedSignature,
            $secp256k1->sign(Buffer::from($encodedTx), $privateKey)
        );
    }

    /*

    // Test for Errors

    // TODO: Check for those edge tests

    public function testSignSuccessfully(): void
    {

    }

    public function testSignWithLowercaseInHex(): void
    {

    }

    public function testSignWithEscrowFinish(): void
    {

    }

    public function testSignWithXAddressAndNoTagForMultisignAddress(): void
    {

    }

    public function testSignWithXAddressAndTagForMultisignAddress(): void
    {

    }

    //test already signed error

    public function testSignWithXAnEscrowExecutionTransaction(): void
    {

    }

    */

    //449 ff.
}