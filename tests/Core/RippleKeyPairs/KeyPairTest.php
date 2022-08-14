<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Core\RippleKeyPairs\Ed25519KeyPairService;
use XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;
use XRPL_PHP\Core\RippleKeyPairs\Secp256k1KeyPairService;


/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-keypairs/test/api-test.js
 */
class KeyPairTest extends TestCase
{
    private array $fixtures;

    private Ed25519KeyPairService $ed25519;

    private Secp256k1KeyPairService $secp256k1;

    protected function setUp(): void
    {
        $raw = file_get_contents("/app/tests/Core/RippleKeyPairs/fixtures.json"); //TODO: use proper path
        $this->fixtures = json_decode($raw, true);

        $this->ed25519 = Ed25519KeyPairService::getInstance();
        $this->secp256k1 = Secp256k1KeyPairService::getInstance();
    }

    /*
    public function testEncodeEd25519Seed(): void
    {
        $addressCodec = new AddressCodec();
        $hex = '4C3A1D213FBDFB14C7C28D609469B341';

        $result = $addressCodec->encodeSeed(Buffer::from($hex), 'ed25519');

        $this->assertEquals(
            $result,
            'sEdTM1uX8pu2do5XvTnutH6HsouMaM2'
        );
    }
    */

    public function testDeriveEd25519KeypairFromSeed(): void
    {
        $seed = $this->fixtures['ed25519']['seed'];
        $keypair = $this->fixtures['ed25519']['keypair'];

        $this->assertEquals($keypair, $this->ed25519->deriveKeyPair($seed)->toArray());
    }

    public function testSignWithEd25519Key(): void
    {
        $privateKey = $this->fixtures['ed25519']['keypair']['privateKey'];
        $message = $this->fixtures['ed25519']['message'];
        $signature = $this->fixtures['ed25519']['signature'];

        $this->assertEquals(
            $signature,
            $this->ed25519->sign(Buffer::from($message, 'utf-8'), $privateKey)
        );
    }

    public function testVerifyWithEd25519Key(): void
    {
        $publicKey = $this->fixtures['ed25519']['keypair']['publicKey'];
        $message = $this->fixtures['ed25519']['message'];
        $signature =$this->fixtures['ed25519']['signature'];

        $this->assertEquals(
            true,
            $this->ed25519->verify(Buffer::from($message, 'utf-8'), $signature, $publicKey)
        );
    }

    /*
    public function testDeriveNodeAddress(): void
    {
        $publicKey = $this->fixtures['ed25519']['keypair']['publicKey'];
        $message = $this->fixtures['ed25519']['message'];
        $signature =$this->fixtures['ed25519']['signature'];

        $this->assertEquals(
            true,
            $this->ed25519->verify(Buffer::from($message, 'utf-8'), $signature, $publicKey)
        );
    }
    */

    public function testDeriveSecp256k1KeypairFromSeed(): void //TODO: with validator
    {
        $seed = $this->fixtures['secp256k1']['seed'];
        $keypair = $this->fixtures['secp256k1']['keypair'];

        $this->assertEquals($keypair, $this->secp256k1->deriveKeyPair($seed)->toArray());
    }
}