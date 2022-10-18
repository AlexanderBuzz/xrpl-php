<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transactions;

abstract class Transaction
{
    public const JSON_PROPERTY_NAME_TRANSACTION_TYPE = "TransactionType";
    public const JSON_PROPERTY_NAME_ACCOUNT = "Account";
    public const JSON_PROPERTY_NAME_AMOUNT = "Amount";
    public const JSON_PROPERTY_NAME_DESTINATION = "Destination";
    public const JSON_PROPERTY_NAME_FEE = "Fee";
    public const JSON_PROPERTY_NAME_FLAGS = "Flags";
    public const JSON_PROPERTY_NAME_LAST_LEDGER_SEQUENCE = "LastLedgerSequence";
    public const JSON_PROPERTY_NAME_SEQUENCE = "Sequence";
    public const JSON_PROPERTY_SIGNING_PUBLIC_KEY = "SigningPubKey";
    public const JSON_PROPERTY_TRANSACTION_SIGNATURE = "TxnSignature";

    public abstract function getPayload(): array;

    public abstract function toArray(): array;
}