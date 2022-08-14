<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Wallet\Wallet;

/**
 * XRPL4J:
 *
 *
 * XRPL.JS
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/wallet/index.ts
 */
class WalletTest extends TestCase
{
    //section constructor

    public function testConstructor(): void
    {
        $masterAddress = 'rUAi7pipxGpYfPNg3LtPcf2ApiS8aw9A93';

        $regularKeyPair = [
            'publicKey' => 'aBRNH5wUurfhZcoyR6nRwDSa95gMBkovBJ8V4cp1C1pM28H7EPL1',
            'privateKey' => 'sh8i92YRnEjJy3fpFkL8txQSCVo79',
        ];

        $wallet = new Wallet(
            $regularKeyPair['publicKey'],
            $regularKeyPair['privateKey'],
            ['masterAddress' => 'rUAi7pipxGpYfPNg3LtPcf2ApiS8aw9A93']
        );
    }

    /*

    //Section generate

    public function testDefaultAlgorithm(): void
    {

    }

    public function testGenerateEcsdaSecp256k(): void
    {

    }

    public function testGenerateEd25519(): void
    {

    }

    //section fromSeed

    public function testSeedDeriveEcsdaSecp256k(): void
    {

    }

    public function testSeedDeriveEd25519(): void
    {

    }

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