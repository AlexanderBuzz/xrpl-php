<?php

namespace XRPL_PHP\Utils\Hashes;

use Exception;
use XRPL_PHP\Core\HashPrefix;
use XRPL_PHP\Core\MathUtilities;
use XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;

/**
 *
 */
class HashLedger
{
    private static ?HashLedger $instance = null;

    private BinaryCodec $binaryCodec;

    public static function getInstance(): HashLedger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param array|string $tx
     * @return string
     * @throws Exception
     */
    public static function hashSignedTx(array|string $tx): string
    {
        $_this = self::getInstance();

        if (is_string($tx)) {
            $txBlob = $tx;
            $txObject = $_this->binaryCodec->decode($tx);
        } else {
            $txBlob = $_this->binaryCodec->encode($tx);
            $txObject = $tx;
        }

        if (!isset($txObject['TxnSignature']) && !isset($txObject['Signers'])) {
            throw new Exception('The transaction must be signed to hash it.');
        }

        $prefix = strtoupper(dechex(HashPrefix::TRANSACTION_ID));

        return MathUtilities::sha512Half($prefix . $txBlob)->toString();
    }

    /**
     * is not allowed to call from outside to prevent from creating multiple instances,
     * to use the singleton, you have to obtain the instance from Singleton::getInstance() instead
     */
    private function __construct()
    {
        $this->binaryCodec = new BinaryCodec();
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