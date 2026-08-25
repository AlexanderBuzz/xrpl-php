<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Core\RippleKeyPairs;

use Exception;

/**
 * A public and private key pair, and the entry point for choosing an algorithm.
 */
class KeyPair
{
    public const EDDSA = 'ed25519';

    public const EC = 'secp256k1';

    public function __construct(private string $publicKey, private string $privateKey)
    {
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * @param string $publicKey
     */
    public function setPublicKey(string $publicKey): void
    {
        $this->publicKey = $publicKey;
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    /**
     * @param string $privateKey
     */
    public function setPrivateKey(string $privateKey): void
    {
        $this->privateKey = $privateKey;
    }

    public function toArray(): array
    {
        return [
            'publicKey' => $this->getPublicKey(),
            'privateKey' => $this->getPrivateKey(),
        ];
    }

    /**
     * @throws Exception Error
     */
    public static function getKeyPairServiceByType(string $type = self::EDDSA): KeyPairServiceInterface
    {
        if ($type === self::EDDSA) {
            return Ed25519KeyPairService::getInstance();
        }

        if ($type === self::EC) {
            return Secp256k1KeyPairService::getInstance();
        }

        throw new Exception('No KeyPairService for type: ' . $type);
    }
}
