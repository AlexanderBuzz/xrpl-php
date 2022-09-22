<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleKeyPairs;

use Elliptic\EdDSA;
use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;

class Ed25519KeyPairService extends AbstractKeyPairService implements KeyPairServiceInterface
{
    private static ?Ed25519KeyPairService $instance = null;

    private EdDSA $elliptic;

    public function __construct()
    {
        $this->type = AbstractKeyPairService::PREFIX_ED25519;
        $this->elliptic = new EdDSA(KeyPair::EDDSA);

        parent::__construct();
    }

    public static function getInstance(): Ed25519KeyPairService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function generateSeed(?Buffer $entropy = null): string
    {
        if (is_null($entropy)) {
            $entropy = Buffer::random(16);
        }

        return $this->addressCodec->encodeSeed($entropy, 'ed25519');
    }

    public function deriveKeyPair(Buffer|string $seed, bool $validator = false, int  $accountIndex = 0): KeyPair
    {
        if (is_string($seed)) {
            $decoded = $this->addressCodec->decodeSeed($seed);
            $seed = Buffer::from($decoded['bytes']);
        }

        $rawPrivateKey = MathUtilities::sha512Half($seed);
        $rawKeyPair = $this->elliptic->keyFromSecret($rawPrivateKey->toString());

        $publicKey = self::PREFIX_ED25519 . Buffer::from($rawKeyPair->getPublic())->toString();
        $privateKey = self::PREFIX_ED25519 . Buffer::from($rawKeyPair->getSecret())->toString();

        return new KeyPair($publicKey, $privateKey);
    }

    public function sign(Buffer|string $message, string $privateKey): string
    {
        if (!is_string($message)) {
            $message = $message->toString();
        }

        $signed = $this->elliptic->sign($message, substr($privateKey, 2));

        return $signed->toHex();
    }

    public function verify(Buffer|string $message, string $signature, string $publicKey): bool
    {
        if (!is_string($message)) {
            $message = $message->toString();
        }

        return $this->elliptic->verify($message, $signature, substr($publicKey, 2));
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