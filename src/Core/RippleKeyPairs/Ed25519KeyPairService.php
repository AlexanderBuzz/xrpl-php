<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core\RippleKeyPairs;

use Elliptic\EdDSA;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\MathUtilities;

/**
 * Ed25519 signing. The default for new wallets; its public keys are marked by a
 * leading ED byte.
 */
class Ed25519KeyPairService extends AbstractKeyPairService implements KeyPairServiceInterface
{
    private static ?Ed25519KeyPairService $instance = null;

    /**
     * The order L of the Ed25519 base point, as 64 upper case hex characters.
     */
    private const GROUP_ORDER = '1000000000000000000000000000000014DEF9DEA2F79CD65812631A5CF5D3ED';

    private readonly EdDSA $elliptic;

    public function __construct()
    {
        $this->type = AbstractKeyPairService::PREFIX_ED25519;
        $this->elliptic = new EdDSA(KeyPair::EDDSA);

        parent::__construct();
    }

    /**
     * The shared instance.
     */
    public static function getInstance(): Ed25519KeyPairService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * A new random seed for this algorithm.
     */
    public function generateSeed(?Buffer $entropy = null): string
    {
        if (is_null($entropy)) {
            $entropy = Buffer::random(16);
        }

        return $this->addressCodec->encodeSeed($entropy, 'ed25519');
    }

    /**
     * Derive the key pair a seed stands for.
     * The public key is prefixed with ED, which is how the ledger tells the two
     * algorithms apart.
     */
    public function deriveKeyPair(Buffer|string $seed, bool $validator = false, int  $accountIndex = 0): KeyPair
    {
        if (is_string($seed)) {
            $decoded = $this->addressCodec->decodeSeed($seed);
            $seed = Buffer::from($decoded['bytes']);
        }

        $rawPrivateKey = MathUtilities::sha512Half($seed);
        $rawKeyPair = $this->elliptic->keyFromSecret(bin2hex($rawPrivateKey->toUtf8()));

        $publicKey = self::PREFIX_ED25519 . Buffer::from($rawKeyPair->getPublic())->toString();
        $privateKey = self::PREFIX_ED25519 . Buffer::from($rawKeyPair->getSecret())->toString();

        return new KeyPair($publicKey, $privateKey);
    }

    /**
     * Sign a message.
     */
    public function sign(Buffer|string $message, string $privateKey): string
    {
        if ($message instanceof Buffer) {
            $message = bin2hex($message->toUtf8());
        }

        $signed = $this->elliptic->sign($message, substr($privateKey, 2));

        return $signed->toHex();
    }

    /**
     * Check a signature against a message and a public key.
     *
     * Only a canonical signature passes, the form rippled accepts: 64 bytes
     * with S below the group order L. elliptic-php alone also accepts S + L,
     * which is a second signature over the same message - in a transaction, a
     * second blob with another hash.
     */
    public function verify(Buffer|string $message, string $signature, string $publicKey): bool
    {
        if (!self::isCanonical($signature)) {
            return false;
        }

        if ($message instanceof Buffer) {
            $message = bin2hex($message->toUtf8());
        }

        return $this->elliptic->verify($message, $signature, substr($publicKey, 2));
    }

    /**
     * Whether an Ed25519 signature is canonical, as rippled's ed25519Canonical()
     * decides it: R and S, 32 bytes each, S read little-endian below L.
     */
    private static function isCanonical(string $signature): bool
    {
        if (strlen($signature) !== 128 || !ctype_xdigit($signature)) {
            return false;
        }

        $s = strtoupper(bin2hex(strrev((string) hex2bin(substr($signature, 64)))));

        return strcmp($s, self::GROUP_ORDER) < 0;
    }

    /*
    public function deriveNodeAddress(Buffer|string $publicKey): string
    {
        if (is_string($publicKey)) {
            $publicKey = Buffer::from($publicKey);
        }

        $generatorBuffer = $this->addressCodec->decodeNodePublic($publicKey)
        $accountPublicBuffer = ;

    }
    */
}