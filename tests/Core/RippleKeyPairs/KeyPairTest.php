<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleKeyPairs;

use PHPUnit\Framework\TestCase;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\Ed25519KeyPairService;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;
use Hardcastle\XRPL_PHP\Core\RippleKeyPairs\Secp256k1KeyPairService;


/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/ripple-keypairs/test/api-test.js
 */
final class KeyPairTest extends TestCase
{
    /** @psalm-suppress PropertyNotSetInConstructor */
    private array $fixtures;

    private array $entropy = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16];

    /** @psalm-suppress PropertyNotSetInConstructor */
    private Ed25519KeyPairService $ed25519;

    /** @psalm-suppress PropertyNotSetInConstructor */
    private Secp256k1KeyPairService $secp256k1;

    protected function setUp(): void
    {
        //TODO: use relative file path
        $raw = file_get_contents(__DIR__ . "/fixtures.json"); //TODO: use proper path
        $this->fixtures = json_decode($raw, true);

        $this->ed25519 = Ed25519KeyPairService::getInstance();
        $this->secp256k1 = Secp256k1KeyPairService::getInstance();
    }

    //Ed25519 section

    public function testGenerateEd25519Seed(): void
    {
        $seed = $this->fixtures['ed25519']['seed'];

        $this->assertEquals(
            $seed,
            $this->ed25519->generateSeed(Buffer::from($this->entropy))
        );
    }

    public function testGenerateRandomEd25519Seed(): void
    {
        $seed = $this->ed25519->generateSeed();

        $this->assertEquals(
            'sEd',
            substr($seed, 0, 3)
        );
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

        $this->assertEquals(
            $keypair,
            $this->ed25519->deriveKeyPair($seed)->toArray()
        );
    }

    public function testDeriveEd25519ValidatorKeypairFromSeed(): void
    {
        $seed = $this->fixtures['ed25519']['seed'];
        $keypair = $this->fixtures['ed25519']['validatorKeypair'];

        $this->assertEquals(
            $keypair,
            $this->ed25519->deriveKeyPair($seed, true)->toArray()
        );
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

        $this->assertTrue(
            $this->ed25519->verify(Buffer::from($message, 'utf-8'), $signature, $publicKey)
        );
    }

    /**
     * S + L verifies in elliptic-php but is a second signature for the same
     * message; rippled refuses it, and so does verify().
     */
    public function testVerifyRefusesAnEd25519SAboveTheGroupOrder(): void
    {
        $publicKey = $this->fixtures['ed25519']['keypair']['publicKey'];
        $message = Buffer::from($this->fixtures['ed25519']['message'], 'utf-8');
        $malleated = 'CB199E1BFD4E3DAA105E4832EEDFA36413E1F44205E4EFB9E27E826044C21E3E'
            . '1B5881199CF8FAED714AF02A38955225AD1B7047EF11B682E0D068F73749751E';

        $this->assertFalse($this->ed25519->verify($message, $malleated, $publicKey));
        $this->assertFalse($this->ed25519->verify($message, substr($this->fixtures['ed25519']['signature'], 2), $publicKey));
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

    //Secp256k1 section

    public function testGenerateSecp256k1Seed(): void
    {
        $seed = $this->fixtures['secp256k1']['seed'];
        //$test = $this->ed25519->generateSeed(Buffer::from($this->entropy));

        $this->assertEquals(
            $seed,
            $this->secp256k1->generateSeed(Buffer::from($this->entropy))
        );
    }

    public function testGenerateRandomSecp256k1Seed(): void
    {
        $seed = $this->secp256k1->generateSeed();

        $this->assertEquals(
            's',
            substr($seed, 0, 1)
        );
    }

    public function testDeriveSecp256k1KeypairFromSeed(): void //TODO: with validator
    {
        $seed = $this->fixtures['secp256k1']['seed'];
        $keypair = $this->fixtures['secp256k1']['keypair'];

        $this->assertEquals(
            $keypair,
            $this->secp256k1->deriveKeyPair($seed)->toArray()
        );
    }

    public function testDeriveSecp256k1ValidatorKeypairFromSeed(): void //TODO: with validator
    {
        $seed = $this->fixtures['secp256k1']['seed'];
        $keypair = $this->fixtures['secp256k1']['validatorKeypair'];

        $this->assertEquals(
            $keypair,
            $this->secp256k1->deriveKeyPair($seed, true)->toArray()
        );
    }

    public function testSignWithSecp256k1Key(): void
    {
        $privateKey = $this->fixtures['secp256k1']['keypair']['privateKey'];
        $message = $this->fixtures['secp256k1']['message'];
        $signature = $this->fixtures['secp256k1']['signature'];

        $this->assertEquals(
            $signature,
            $this->secp256k1->sign(Buffer::from($message, 'utf-8'), $privateKey)
        );
    }

    public function testVerifyWithSecp256k1Key(): void
    {
        $signature = $this->fixtures['secp256k1']['signature'];
        $message = $this->fixtures['secp256k1']['message'];
        $publicKey = $this->fixtures['secp256k1']['keypair']['publicKey'];

        $this->assertTrue(
            $this->secp256k1->verify(Buffer::from($message, 'utf-8'), $signature, $publicKey)
        );
    }

    /**
     * rippled accepts only fully canonical secp256k1 signatures: strict DER,
     * s no greater than n - s. The high s and the padded r verify in
     * elliptic-php alone; a trailing byte made it throw.
     */
    public function testVerifyRefusesNonCanonicalSecp256k1Signatures(): void
    {
        $publicKey = $this->fixtures['secp256k1']['keypair']['publicKey'];
        $message = Buffer::from($this->fixtures['secp256k1']['message'], 'utf-8');
        $signature = $this->fixtures['secp256k1']['signature'];

        $highS = '30450220583A91C95E54E6A651C47BEC22744E0B101E2C4060E7B08F6341657DAD9BC3EE'
            . '02210082EB7638C6A24FE772C5A956881345AA073F33AF94086EA56A20AA4A32025214';
        $paddedR = '3045022100' . substr($signature, 8);
        $trailingByte = '3045' . substr($signature, 4) . '00';

        $this->assertTrue($this->secp256k1->verify($message, $signature, $publicKey));
        $this->assertFalse($this->secp256k1->verify($message, $highS, $publicKey));
        $this->assertFalse($this->secp256k1->verify($message, $paddedR, $publicKey));
        $this->assertFalse($this->secp256k1->verify($message, $trailingByte, $publicKey));
        $this->assertFalse($this->secp256k1->verify($message, 'not hex', $publicKey));
    }
}
