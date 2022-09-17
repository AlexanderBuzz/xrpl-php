<?php declare(strict_types=1);

namespace XRPL_PHP\Models\Transactions;

use XRPL_PHP\Models\Common\Amount;

class Payment extends BaseTransaction
{
    public const PAYMENT_FLAG_TF_NO_DIRECT_RIPPLE = 0x00010000;

    public const PAYMENT_FLAG_TF_PARTIAL_PAYMENT = 0x00020000;

    public const PAYMENT_FLAG_TF_LIMIT_QUALITY = 0x00040000;

    private string $transactionType = 'Payment';

    public function __construct(
        protected Amount|string $amount,
        protected string $destination,
        protected ?string $destinationTag,
        protected ?string $invoiceId,
        protected ?string $paths, //TODO: check type -> Path
        protected ?string $sendMax, //TODO: check type -> Amount
        protected ?string $deliverMin //TODO: check type -> Amount
    ) {}

    public function getPayload(): array
    {
        return $this->autofill();
    }

    public function validatPayment(): void
    {

    }

    public function checkPartialPayment(): void
    {

    }

    public function autofill(): array
    {
        return [
            BaseTransaction::JSON_PROPERTY_NAME_TRANSACTION_TYPE => $this->transactionType,
            BaseTransaction::JSON_PROPERTY_NAME_ACCOUNT => $this->getAccount(),
            BaseTransaction::JSON_PROPERTY_NAME_AMOUNT => $this->getAmount(),
            BaseTransaction::JSON_PROPERTY_NAME_DESTINATION => $this->getDestination(),
            BaseTransaction::JSON_PROPERTY_NAME_FEE => "12",
            BaseTransaction::JSON_PROPERTY_NAME_FLAGS => 0,
            BaseTransaction::JSON_PROPERTY_NAME_LAST_LEDGER_SEQUENCE => 26465005,
            BaseTransaction::JSON_PROPERTY_NAME_SEQUENCE => 26366450,
        ];
    }

    /**
     * @return string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return string
     */
    public function getDestination(): string
    {
        return $this->destination;
    }

    /**
     * @param string $destination
     */
    public function setDestination(string $destination): void
    {
        $this->destination = $destination;
    }

    /**
     * @return string|null
     */
    public function getDestinationTag(): ?string
    {
        return $this->destinationTag;
    }

    /**
     * @param string|null $destinationTag
     */
    public function setDestinationTag(?string $destinationTag): void
    {
        $this->destinationTag = $destinationTag;
    }

    /**
     * @return string|null
     */
    public function getInvoiceId(): ?string
    {
        return $this->invoiceId;
    }

    /**
     * @param string|null $invoiceId
     */
    public function setInvoiceId(?string $invoiceId): void
    {
        $this->invoiceId = $invoiceId;
    }

    /**
     * @return string|null
     */
    public function getPaths(): ?string
    {
        return $this->paths;
    }

    /**
     * @param string|null $paths
     */
    public function setPaths(?string $paths): void
    {
        $this->paths = $paths;
    }

    /**
     * @return string|null
     */
    public function getSendMax(): ?string
    {
        return $this->sendMax;
    }

    /**
     * @param string|null $sendMax
     */
    public function setSendMax(?string $sendMax): void
    {
        $this->sendMax = $sendMax;
    }

    /**
     * @return string|null
     */
    public function getDeliverMin(): ?string
    {
        return $this->deliverMin;
    }

    /**
     * @param string|null $deliverMin
     */
    public function setDeliverMin(?string $deliverMin): void
    {
        $this->deliverMin = $deliverMin;
    }
}