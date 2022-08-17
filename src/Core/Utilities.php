<?php declare(strict_types=1);

namespace XRPL_PHP\Core;

use XRPL_PHP\Core\RippleAddressCodec\AddressCodec;
use XRPL_PHP\Core\RippleKeyPairs\KeyPairServiceInterface;

class Utilities
{
    private static ?Utilities $instance = null;

    private AddressCodec $addressCodec;

    public static function getInstance(): Utilities
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
                throw new \Exception(
                    'This command does not support the use of a tag. Use an address without a tag.',
                );
            }

            // For rippled requests that use an account, always use a classic address.
            return $classicAddress;
        }

        return $account;
    }

    /**
     * @param Buffer|string $address Account
     * @return string
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

    public static function encodeSeed(Buffer $entropy, string $type): string
    {
        $_this = self::getInstance();
        return $_this->addressCodec->encodeSeed($entropy, $type);
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