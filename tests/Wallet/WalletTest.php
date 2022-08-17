<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use XRPL_PHP\Wallet\Wallet;

class WalletTest extends TestCase
{
    private const CLASSIC_ADDRESS_PREFIX = 'r';
    private const ED25519_KEY_PRERFIX = 'ED';
    private const SECP256K1_PRIVATE_KEY_PREFIX = '00';

    public function testConstructor(): void
    {
        $masterAddress = 'rUAi7pipxGpYfPNg3LtPcf2ApiS8aw9A93';

        $regularKeyPair = [
            'publicKey' => 'aBRNH5wUurfhZcoyR6nRwDSa95gMBkovBJ8V4cp1C1pM28H7EPL1',
            'privateKey' => 'sh8i92YRnEjJy3fpFkL8txQSCVo79',
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
    /*
        public function testSignSuccessfully(): void
        {
            $wallet = Wallet::fromSeed('ss1x3KLrSvfg7irFc1D929WXZ7z9H');


        }


        public function testSeedDerivSecp256k1(): void
        {

        }

        public function testSeedDeriveEd25519(): void
        {

        }
        */

    /*
    public function testSeedDeriveFromMnemonicEcsdaSecp256k(): void
    {

    }

    public function testSeedDeriveFromMnemonicEd25519(): void
    {

    }

    public function testSeedDeriveFromLowercaseMnemonicEd25519(): void
    {

    }

    public function testSeedDeriveFromRegularKeypair(): void
    {

    }

    //Test for Errors

    //section FromSecret

    public function testSecretDeriveUseDefaultAlgorithm(): void
    {

    }

    public function testSecretDeriveEcsdaSecp256k(): void
    {

    }

    public function testSecretDeriveEd25519(): void
    {

    }

    public function testSecretDeriveFromRegularKeypair(): void
    {

    }

    //section from Mnemonic

    public function testDeriveInputDerivation(): void
    {

    }

    public function testDeriveRegularKeypair(): void
    {

    }

    //section sign

    public function testSignSuccessfully(): void
    {

    }

    public function testSignWithLowercaseInHex(): void
    {

    }

    public function testSignWithEscrowFinish(): void
    {

    }

    public function testSignWithMultisignAddress(): void
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