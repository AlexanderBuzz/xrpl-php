<?php declare(strict_types = 1);

namespace XRPL_PHP\Models\Transactions;

abstract class BaseTransaction
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

    public const TRANSACTION_TYPE_PAYMENT = "Payment";

    private string $transactionType;

    private string $account;

    public abstract function getPayload(): array;

    /**
     * @return string
     */
    public function getAccount(): string
    {
        return $this->account;
    }

    /**
     * @param string $account
     */
    public function setAccount(string $account): void
    {
        $this->account = $account;
    }
}