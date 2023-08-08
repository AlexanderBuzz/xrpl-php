<?php declare(strict_types=1);

namespace XRPL_PHP\Test\Wallet;

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleKeyPairs\KeyPair;
use XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;
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
            'seed' => 'sh8i92YRnEjJy3fpFkL8txQSCVo79',
            'publicKey' => '03AEEFE1E8ED4BBC009DE996AC03A8C6B5713B1554794056C66E5B8D1753C7DD0E',
            'privateKey' => '004265A28F3E18340A490421D47B2EB8DBC2C0BF2C24CEFEA971B61CED2CABD233',
        ];

        $wallet = new Wallet(
            $regularKeyPair['publicKey'],
            $regularKeyPair['privateKey'],
            $regularKeyPair['seed'],
            $masterAddress
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

    public function testDeriveWallet(): void
    {
        $ed25519_fixture = [
            'seed' => 'sEdVUQjtZodxPWQRNsYpxs4tfEXkgAD',
            'publicKey' => 'ED78955DAFE798B8575256974856386EE2DF08E10739667F49A6D21C1BB62B9737',
            'privateKey' => 'EDA45F83DADC737B905C57C34E6B061E2BC7963BA4C6C86C55CF9F3B5193C977FC',
            'classicAddress' => 'rDmtBkGk5P1BnX4h8KAsZ8rZhNb9uGjmTi'
        ];
        $ed25519_wallet = Wallet::fromSeed($ed25519_fixture['seed']);
        $this->assertEquals($ed25519_fixture['publicKey'], $ed25519_wallet->getPublicKey());
        $this->assertEquals($ed25519_fixture['privateKey'], $ed25519_wallet->getPrivateKey());
        $this->assertEquals($ed25519_fixture['classicAddress'], $ed25519_wallet->getClassicAddress());


        $secp256k1_fixture = [
            'seed' => 'ssNYVX6qYzKNu48FBBs4LuvgfivEJ',
            'publicKey' => '03D818927E512DD16BB3177007837620C47E00CCEB394B241B56551FBB41C6E898',
            'privateKey' => '002982031E3AB068FF214042E23DAC34103ECD5179DDA2FD46EF4C83063B2BB9C0',
            'classicAddress' => 'rNWgYiADKLCJDfZX3oAdPcsRHe9vtJdCVD'
        ];
        $secp256k1_wallet = Wallet::fromSeed($secp256k1_fixture['seed']);
        $this->assertEquals($secp256k1_fixture['publicKey'], $secp256k1_wallet->getPublicKey());
        $this->assertEquals($secp256k1_fixture['privateKey'], $secp256k1_wallet->getPrivateKey());
        $this->assertEquals($secp256k1_fixture['classicAddress'], $secp256k1_wallet->getClassicAddress());


    }

    public function testSignSuccessfully(): void
    {
        $wallet = Wallet::fromSeed(seed: 'ss1x3KLrSvfg7irFc1D929WXZ7z9H');

        // TODO: Pull this from fixtures
        $expected = [
            'tx_blob' => '12000322800000002400000017201B0086955368400000000000000C732102A8A44DB3D4C73EEEE11DFE54D2029103B776AA8A8D293A91D645977C9DF5F54474463044022025464FA5466B6E28EEAD2E2D289A7A36A11EB9B269D211F9C76AB8E8320694E002205D5F99CB56E5A996E5636A0E86D029977BEFA232B7FB64ABA8F6E29DC87A9E89770B6578616D706C652E636F6D81145E7B112523F68D2F5E879DB4EAC51C6698A69304',
            'hash' => '93F6C6CE73C092AA005103223F3A1F557F4C097A2943D96760F6490F04379917',
        ];

        // TODO: Pull this from fixtures
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

        $this->assertEquals($expected, $wallet->sign($tx));
    }

    public function testSignWithMultisignAddress(): void
    {
        $wallet = Wallet::fromSeed(seed: 'ss1x3KLrSvfg7irFc1D929WXZ7z9H');

        // TODO: Pull this from fixtures
        $expected = [
            'tx_blob' => '120000240000000261400000003B9ACA00684000000000000032730081142E244E6F20104E57C0C60BD823CB312BF10928C78314B5F762798A53D543A014CAF8B297CFF8F2F937E8F3E010732102A8A44DB3D4C73EEEE11DFE54D2029103B776AA8A8D293A91D645977C9DF5F54474473045022100B3F8205578C6A68D3BBD27650F5D2E983718D502C250C5147F07B7EDD8E8583E02207B892818BD58E328C2797F15694A505937861586D527849065B582523E390B128114B3263BD0A9BF9DFDBBBBD07F536355FF477BF0E9E1F1',
            'hash' => 'D8CF5FC93CFE5E131A34599AFB7CE186A5B8D1B9F069E35F4634AD3B27837E35',
        ];

        // TODO: Pull this from fixtures
        $tx = [
            "Account" => "rnUy2SHTrB9DubsPmkJZUXTf5FcNDGrYEA",
            "Amount" => "1000000000",
            "Destination" => "rHb9CJAWyB4rj91VRWn96DkukG4bwdtyTh",
            "Fee" => "50",
            "Sequence" => 2,
            "TransactionType" => "Payment"
        ];

        $this->assertEquals($expected, $wallet->sign($tx, true));
    }

    /*
    public function testSignWithXAddressAndNoGivenTagForMultisignAddress(): void
    {

    }

    public function testSignWithXAddressAndTagForMultisignAddress(): void
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

    public function testVerifyTransaction(): void
    {
        $seed = 'ssL9dv2W5RK8L3tuzQxYY6EaZhSxW';
        $publicKey = '030E58CDD076E798C84755590AAF6237CA8FAE821070A59F648B517A30DC6F589D';
        $privateKey = '00141BA006D3363D2FB2785E8DF4E44D3A49908780CB4FB51F6D217C08C021429F';
        $preparedTx = [
            'signedTransaction' => '1200002400000001614000000001312D0068400000000000000C7321030E58CDD076E798C84755590AAF6237CA8FAE821070A59F648B517A30DC6F589D74473045022100CAF99A63B241F5F62B456C68A593D2835397101533BB5D0C4DC17362AC22046F022016A2CA2CF56E777B10E43B56541A4C2FB553E7E298CDD39F7A8A844DA491E51D81142AF1861DEC1316AEEC995C94FF9E2165B1B784608314FDB08D07AAA0EB711793A3027304D688E10C3648',
            'id' => '30D9ECA2A7FB568C5A8607E5850D9567572A9E7C6094C26BEFD4DC4C2CF2657A'
        ];

        $wallet = new Wallet($publicKey, $privateKey, $seed);

        $this->assertTrue($wallet->verifyTransaction($preparedTx['signedTransaction']));
    }

    public function testGetXAddress(): void
    {
        $seed = 'ssL9dv2W5RK8L3tuzQxYY6EaZhSxW';
        $publicKey = '030E58CDD076E798C84755590AAF6237CA8FAE821070A59F648B517A30DC6F589D';
        $privateKey = '00141BA006D3363D2FB2785E8DF4E44D3A49908780CB4FB51F6D217C08C021429F';

        $wallet = new Wallet($publicKey, $privateKey, $seed);

        $tag = 1337;
        $mainnetXAddress = 'X7gJ5YK8abHf2eTPWPFHAAot8Knck11QGqmQ7a6a3Z8PJvk';
        $testnetXAddress = 'T7bq3e7kxYq9pwDz8UZhqAZoEkcRGTXSNr5immvcj3DYRaV';

        // Check: Returns a Mainnet X-address when test is false
        $result = $wallet->getXAddress($tag, false);
        $this->assertEquals($mainnetXAddress, $result);

        // Check: returns a Mainnet X-address when test is false
        $result = $wallet->getXAddress($tag, true);
        $this->assertEquals($testnetXAddress, $result);
    }

    /*

    // Test for Errors

    // TODO: Check for those edge tests

    public function testSignWithLowercaseInHex(): void
    {

    }

    public function testSignWithEscrowFinish(): void
    {

    }

    public function testSignWithXAnEscrowExecutionTransaction(): void
    {

    }
    */
}