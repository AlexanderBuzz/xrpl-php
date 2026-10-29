<?php

namespace Hardcastle\XRPL_PHP\Utils\Hashes;

use Exception;
use Hardcastle\XRPL_PHP\Core\HashPrefix;
use Hardcastle\XRPL_PHP\Core\MathUtilities;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;

/**
 * Computes the hashes rippled uses, above all the id of a signed transaction.
 */
class HashLedger
{
    private static ?HashLedger $instance = null;

    private readonly BinaryCodec $binaryCodec;

    /**
     * The shared instance.
     */
    public static function getInstance(): HashLedger
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Hash of a signed transaction.
     *
     * When given an array the blob that gets hashed is produced here, so the
     * definitions decide the result - pass the ones the transaction was built
     * with, otherwise the hash is silently wrong for another network.
     *
     * @param array|string $tx
     * @param Definitions|null $definitions
     * @return string
     * @throws Exception
     */
    public static function hashSignedTx(array|string $tx, ?Definitions $definitions = null): string
    {
        $binaryCodec = ($definitions === null)
            ? self::getInstance()->binaryCodec
            : new BinaryCodec($definitions);

        $txObject = is_string($tx) ? $binaryCodec->decode($tx) : $tx;

        if (!isset($txObject['TxnSignature']) && !isset($txObject['Signers'])) {
            throw new Exception('The transaction must be signed to hash it.');
        }

        return self::hashTx($tx, $definitions);
    }

    /**
     * The transaction ID of any transaction, signed or not: SHA-512-half of
     * the 'TXN' prefix and the full serialization.
     *
     * For a signed transaction this is the hash the ledger reports, the same
     * as hashSignedTx(). The unsigned case exists for the inner transactions
     * of a Batch (XLS-56): they carry no signature, and the ID rippled
     * computes for them, with their empty SigningPubKey in the serialization,
     * is what every BatchSigner signs over.
     *
     * @param array|string $tx a transaction array or tx_blob
     * @param Definitions|null $definitions the definitions the transaction was built with
     * @return string upper case hex
     * @throws Exception
     */
    public static function hashTx(array|string $tx, ?Definitions $definitions = null): string
    {
        $binaryCodec = ($definitions === null)
            ? self::getInstance()->binaryCodec
            : new BinaryCodec($definitions);

        $txBlob = is_string($tx) ? strtoupper($tx) : $binaryCodec->encode($tx);

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