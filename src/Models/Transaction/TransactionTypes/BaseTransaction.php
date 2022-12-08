<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transaction\TransactionTypes;

use ArrayAccess;
use Exception;
use XRPL_PHP\Core\RippleBinaryCodec\Types\AccountId;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Amount;
use XRPL_PHP\Core\RippleBinaryCodec\Types\Blob;
use XRPL_PHP\Core\RippleBinaryCodec\Types\StArray;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt16;
use XRPL_PHP\Core\RippleBinaryCodec\Types\UnsignedInt32;

/**
 * public API Methods / Transaction Methods
 * https://xrpl.org/transaction-common-fields.html
 */

#[AllowDynamicProperties]
abstract class BaseTransaction implements ArrayAccess
{
    protected array $baseProperties = [
        "Account" => AccountId::class,
        "TransactionType" => UnsignedInt16::class,
        "Fee" => Amount::class,
        "Sequence" => UnsignedInt32::class,
        "AccountTxnID" => Hash256::class,
        "Flags" => UnsignedInt32::class,
        "LastLedgerSequence" => UnsignedInt32::class,
        "Memos" => StArray::class,
        "Signers" => StArray::class,
        "SourceTag" => UnsignedInt32::class,
        "SigningPubKey" => Blob::class,
        "TicketSequence" => UnsignedInt32::class,
        "TxnSignature" => Blob::class
    ];

    protected array $transactionTypeProperties = [];

    public function __construct(array $fields) {
        $this->fromArray($fields);
    }

    public function fromArray(array $fields): void
    {
        foreach ($fields as $propertyName => $propertyValue) {
            $classProperties = array_merge($this->baseProperties, $this->transactionTypeProperties);
            if (!in_array($propertyName, array_keys($classProperties))) {
                $className = get_class($this);
                throw new Exception(
                    "Property {$propertyName} does not exist in {$className}",
                );
            } else if (property_exists($this, $propertyName)) {
                throw new Exception(
                    "Property {$propertyName} cannot be declared twice",
                );
            } else if ($propertyName === 'baseProperties' || $propertyName === 'transactionTypeProperties') {
                throw new Exception(
                    "Property {$propertyName} is VERBOTEN!",
                );
            } else {
                $this->{$propertyName} = $propertyValue;
            }
        }
    }

    public function toArray(): array
    {
        $properties = get_object_vars($this);
        unset($properties['baseProperties']);
        unset($properties['transactionTypeProperties']);

        return $properties;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->{$offset});
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->{$offset} = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->{$offset});
    }
}