<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Core;

use Exception;
use Hardcastle\Buffer\Buffer;
use Hardcastle\XRPL_PHP\Core\RippleAddressCodec\AddressCodec;

/**
 * Address helpers used across the library: validation, and conversion between
 * classic addresses and X-addresses.
 */
class CoreUtilities
{
    private static ?CoreUtilities $instance = null;

    private readonly AddressCodec $addressCodec;

    /**
     * The shared instance.
     */
    public static function getInstance(): CoreUtilities
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * The classic address of an account, whichever form was given.
     * An X-address carrying a tag is rejected, because the tag would be lost.
     */
    public static function ensureClassicAddress(string $account): string
    {
        $_this = self::getInstance();
        if ($_this->addressCodec->isValidXAddress($account)) {
            [$classicAddress, $tag] = $_this->addressCodec->xAddressToClassicAddress($account);

            /*
             * Except for special cases, X-addresses used for requests
             * must not have an embedded tag. In other words,
             * `tag` should be `false`.
             */
            if ($tag !== false) {
                throw new Exception(
                    'This command does not support the use of a tag. Use an address without a tag.',
                );
            }

            // For rippled requests that use an account, always use a classic address.
            return $classicAddress;
        }

        return $account;
    }
    /**
     * Whether this is a well formed classic address, checksum included.
     *
     * @param null|string $address
     */
    public static function isValidClassicAddress(string|null $address): bool
    {
        $_this = self::getInstance();

        return $_this->addressCodec->isValidClassicAddress($address);
    }

    /**
     * Whether this is a well formed X-address.
     */
    public static function isValidXAddress(string $address): bool
    {
        $_this = self::getInstance();

        return $_this->addressCodec->isValidXAddress($address);
    }

    /**
     * Combine a classic address and a destination tag into one X-address.
     * The point of the format is that the tag can no longer be forgotten.
     */
    public static function classicAddressToXAddress(string $xAddress, mixed $tag, bool $isTestnet = false): string
    {
        $_this = self::getInstance();

        return $_this->addressCodec->classicAddressToXAddress($xAddress, $tag, $isTestnet);
    }

    /**
     * Split an X-address back into its classic address and its tag.
     */
    public static function xAddressToClassicAddress(string $xAddress): array
    {
        $_this = self::getInstance();

        return $_this->addressCodec->xAddressToClassicAddress($xAddress);
    }
    /**
     * The account address a public key belongs to.
     *
     * @param Buffer|string $publicKey
     * @return string
     * @throws Exception Error
     */
    public static function deriveAddress(Buffer|string $publicKey): string
    {
        $_this = self::getInstance();

        if (is_string($publicKey)) {
            $publicKey = Buffer::from($publicKey, 'hex');
        }

        $publicKeyHash = MathUtilities::computePublicKeyHash($publicKey);

        return $_this->addressCodec->encodeAccountId($publicKeyHash);
    }
    /**
     * Encode raw entropy as a seed string, marking which algorithm it is for.
     *
     * @throws Exception Error
     */
    public static function encodeSeed(Buffer $entropy, string $type): string
    {
        $_this = self::getInstance();
        return $_this->addressCodec->encodeSeed($entropy, $type);
    }
    /**
     * Read a seed string back into its entropy and its algorithm.
     *
     * @throws Exception Error
     */
    public static function decodeSeed(string $seed): array
    {
        $_this = self::getInstance();
        return $_this->addressCodec->decodeSeed($seed);
    }

    /**
     * Encode Nonstandard Currency Codes as in https://xrpl.org/currency-formats.html
     *
     * @param string $currencyName
     * @return string
     * @throws Exception
     */
    public static function encodeCustomCurrency(string $currencyName): string
    {
        $hex = str2hex($currencyName);
        $rawHash = str_pad($hex, 40, "00");
        return strtoupper($rawHash);
    }

    /**
     * Decode Nonstandard Currency Codes as in https://xrpl.org/currency-formats.html
     *
     * @param string $currencyHash
     * @return string
     * @throws Exception Error
     */
    public static function decodeCustomCurrency(string $currencyHash): string
    {
        if (!str_starts_with($currencyHash, "00")) {
            return rtrim(hex2str($currencyHash));
        }

        throw new Exception("Invalid currency hash");
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        $this->addressCodec = new AddressCodec();
    }

    /**
     * prevent the instance from being cloned (which would create a second instance of it)
     */
    private function __clone()
    {
    }

    /**
     * prevent from being unserialized (which would create a second instance of it)
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}