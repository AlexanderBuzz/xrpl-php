<?php declare(strict_types=1);

namespace XRPL_PHP\Core\RippleKeyPairs;

use XRPL_PHP\Core\Buffer;
use XRPL_PHP\Core\MathUtilities;
use XRPL_PHP\Core\CoreUtilities;

interface KeyPairServiceInterface
{
    /**
     * Generate a random 16 byte seed to be used to derive a private key.
     *
     * @param Buffer|null $entropy
     * @param string|null $type
     * @return string
     */
    public function generateSeed(?Buffer $entropy = null): string;

    /**
     * Sign a message using the given private key.
     *
     * @param string $seed
     * @return KeyPair
     */
    public function deriveKeyPair(Buffer|string $seed): KeyPair;

    /**
     * Sign a message using the given private key.
     *
     * @param Buffer|string $message
     * @param string $privateKey
     * @return string
     */
    public function sign(Buffer|string $message, string $privateKey): string;

    /**
     * Verify that the signature is valid, based on the message that was signed and the public key.
     *
     * @param Buffer|string $message
     * @param string $signature
     * @param string $publicKey
     * @return bool
     */
    public  function verify(Buffer|string $message, string $signature, string $publicKey): bool;

    /**
     * Derive an XRPL address from a public key.
     *
     * @param Buffer|string $publicKey
     * @return string
     */
    public function deriveAddress(Buffer|string $publicKey): string;
}