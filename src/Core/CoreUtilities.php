<?php declare(strict_types=1);

namespace XRPL_PHP\Core;

use Exception;
use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;

class CoreUtilities
{
    private static ?CoreUtilities $instance = null;

    private AddressCodec $addressCodec;

    public static function getInstance(): CoreUtilities
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function ensureClassicAddress(string $account): string
    {
        $_this = self::getInstance();
        if ($_this->addressCodec->isValidXAddress($account)) {
            list($classicAddress, $tag) = $_this->addressCodec->xAddressToClassicAddress($account);

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
     * @param null|string $address
     */
    public static function isValidClassicAddress(string|null $address): bool
    {
        $_this = self::getInstance();

        return $_this->addressCodec->isValidClassicAddress($address);
    }

    public static function isValidXAddress(string $address): bool
    {
        $_this = self::getInstance();

        return $_this->addressCodec->isValidXAddress($address);
    }

    public static function classicAddressToXAddress(string $xAddress, mixed $tag, bool $isTestnet = false): string
    {
        $_this = self::getInstance();

        return $_this->addressCodec->classicAddressToXAddress($xAddress, $tag, $isTestnet);
    }

    public static function xAddressToClassicAddress(string $xAddress): array
    {
        $_this = self::getInstance();

        return $_this->addressCodec->xAddressToClassicAddress($xAddress);
    }

    /**
     * @param Buffer|string $publicKey
     * @return string
     * @throws Exception Error
     */
    public static function deriveAddress(Buffer|string $publicKey): string
    {
        $_this = self::getInstance();

        if (is_string($publicKey)) {
            $publicKey = Buffer::from($publicKey);
        }

        $publicKeyHash = MathUtilities::computePublicKeyHash($publicKey);

        return $_this->addressCodec->encodeAccountId($publicKeyHash);
    }

    /**
     * @throws Exception Error
     */
    public static function encodeSeed(Buffer $entropy, string $type): string
    {
        $_this = self::getInstance();
        return $_this->addressCodec->encodeSeed($entropy, $type);
    }

    /**
     * @throws Exception Error
     */
    public static function decodeSeed(string $seed): array
    {
        $_this = self::getInstance();
        return $_this->addressCodec->decodeSeed($seed);
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