<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Client;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\MathException;
use Brick\Math\RoundingMode;
use Exception;
use Hardcastle\XRPL_PHP\Core\CoreUtilities;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\BinaryCodec;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\Account\AccountObjectsRequest;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;
use Hardcastle\XRPL_PHP\Models\ServerInfo\ServerStateRequest;
use Hardcastle\XRPL_PHP\Models\Transaction\TransactionTypes\BaseTransaction as Transaction;

use function Hardcastle\XRPL_PHP\Sugar\xrpToDrops;

/**
 * Fills in the fields a transaction needs before it can be signed: the account
 * sequence, the transaction cost and the ledger it expires in.
 *
 * This is the object form of the Sugar\autofill() family, which stays available
 * as deprecated wrappers.
 */
class Autofiller
{
    /**
     * Transaction types whose cost is one owner reserve rather than the
     * network fee.
     */
    public const OWNER_RESERVE_FEE_TYPES = ['AccountDelete', 'AMMCreate'];

    /**
     * How many ledgers ahead LastLedgerSequence is placed.
     */
    public const LEDGER_OFFSET = 20;

    public function __construct(private readonly JsonRpcClient $client)
    {
    }

    /**
     * @param Transaction|string|array $transaction A model, a tx_blob or an array
     * @param int|null $signersCount Number of signatures a multi-signed transaction will carry
     * @return array
     * @throws Exception
     */
    public function autofill(Transaction|string|array $transaction, ?int $signersCount = null): array
    {
        if (is_string($transaction)) {
            $tx = (new BinaryCodec($this->client->getDefinitions()))->decode($transaction);
        } else if ($transaction instanceof Transaction) {
            $tx = $transaction->toArray();
        } else {
            $tx = $transaction;
        }

        $this->setValidAddresses($tx);

        if (!isset($tx['Sequence'])) {
            $this->setNextValidSequenceNumber($tx);
        }

        if (!isset($tx['Fee'])) {
            $this->calculateFeePerTransactionType($tx, $signersCount);
        }

        if (!isset($tx['LastLedgerSequence'])) {
            $this->setLatestValidatedLedgerSequence($tx);
        }

        if (($tx['TransactionType'] ?? null) === 'AccountDelete') {
            $this->checkAccountDeleteBlockers($tx);
        }

        if (empty($tx['SourceTag'])) {
            unset($tx['SourceTag']);
        }
        if (empty($tx['DestinationTag'])) {
            unset($tx['DestinationTag']);
        }

        return $tx;
    }

    /**
     * Resolve X-addresses into classic addresses and their tags.
     *
     * @param array $tx
     * @return void
     * @throws Exception
     */
    public function setValidAddresses(array &$tx): void
    {
        $this->validateAccountAddress($tx, 'Account', 'SourceTag');

        if (isset($tx['Destination'])) {
            $this->validateAccountAddress($tx, 'Destination', 'DestinationTag');
        }

        // DepositPreauth:
        $this->convertToClassicAddress($tx, 'Authorize');
        $this->convertToClassicAddress($tx, 'Unauthorize');

        // EscrowCancel, EscrowFinish:
        $this->convertToClassicAddress($tx, 'Owner');

        // SetRegularKey:
        $this->convertToClassicAddress($tx, 'RegularKey');
    }

    /**
     * @param array $tx
     * @param string $accountField
     * @param string $tagField
     * @return void
     * @throws Exception
     */
    public function validateAccountAddress(array &$tx, string $accountField, string $tagField): void
    {
        ['classicAccount' => $classicAccount, 'tag' => $tag] = self::getClassicAccountAndTag($tx[$accountField]);

        $tx[$accountField] = $classicAccount;

        if (isset($tag) && $tag !== false) {
            if (isset($tx[$tagField]) && $tx[$tagField] !== $tag) {
                throw new Exception("The {$tagField}, if present, must match the tag of the {$accountField} X-address");
            }

            $tx[$tagField] = $tag;
        }
    }

    /**
     * @param string $account
     * @param int|null $expectedTag
     * @return array
     * @throws Exception
     */
    public static function getClassicAccountAndTag(string $account, ?int $expectedTag = null): array
    {
        if (CoreUtilities::isValidXAddress($account)) {
            $classicAddress = CoreUtilities::xAddressToClassicAddress($account);
            if (!is_null($expectedTag) && $expectedTag !== $classicAddress['tag']) {
                throw new Exception('Address includes a tag that does not match the tag specified in the transaction');
            }

            return [
                'classicAccount' => $classicAddress['classicAddress'],
                'tag' => $classicAddress['tag']
            ];
        }

        return [
            'classicAccount' => $account,
            'tag' => $expectedTag
        ];
    }

    /**
     * @param array $tx
     * @param string $fieldName
     * @return void
     * @throws Exception
     */
    public function convertToClassicAddress(array &$tx, string $fieldName): void
    {
        $account = $tx[$fieldName] ?? null;

        if (is_string($account)) {
            ['classicAccount' => $classicAccount] = self::getClassicAccountAndTag($account);
            $tx[$fieldName] = $classicAccount;
        }
    }

    /**
     * @param array $tx
     * @return void
     * @throws Exception
     */
    public function setNextValidSequenceNumber(array &$tx): void
    {
        $accountInfoRequest = new AccountInfoRequest(
            account: $tx['Account'],
            ledgerIndex: 'current'
        );

        $accountInfoResponse = $this->client->syncRequest($accountInfoRequest);
        if ($accountInfoResponse instanceof ErrorResponse) {
            throw new Exception($accountInfoResponse->getError());
        }

        $tx['Sequence'] = $accountInfoResponse->getResult()['account_data']['Sequence'];
    }

    /**
     * The owner reserve, which AccountDelete and AMMCreate pay as their
     * transaction cost instead of the ordinary network fee.
     *
     * @return BigDecimal
     * @throws MathException
     */
    public function fetchOwnerReserveFee(): BigDecimal
    {
        $serverStateResponse = $this->client->request(new ServerStateRequest())->wait();

        $fee = $serverStateResponse->getResult()['state']['validated_ledger']['reserve_inc'] ?? null;

        if (is_null($fee)) {
            throw new Exception('Could not read the owner reserve from server_state');
        }

        return BigDecimal::of($fee);
    }

    /**
     * @param array $tx
     * @param int|null $signersCount
     * @return void
     * @throws MathException
     */
    public function calculateFeePerTransactionType(array &$tx, ?int $signersCount = 0): void
    {
        $netFeeXrp = (new FeeCalculator($this->client))->getFeeXrp();
        $netFeeDrops = xrpToDrops($netFeeXrp);
        $baseFee = BigDecimal::of($netFeeDrops);

        if ($tx['TransactionType'] === 'EscrowFinish' && isset($tx['Fulfillment']) && !is_null($tx['Fulfillment'])) {
            // net fee x (33 + fulfillment size in bytes / 16)
            $fulfillmentBytesSize = ceil(strlen($tx['Fulfillment']) / 2);
            $product = self::scaleValue($netFeeDrops, 33 + $fulfillmentBytesSize / 16);
            $baseFee = $product->toScale(0, RoundingMode::CEILING);
        }

        // Both burn one owner reserve instead of paying the ordinary network fee
        $paysOwnerReserve = in_array($tx['TransactionType'], self::OWNER_RESERVE_FEE_TYPES, true);
        if ($paysOwnerReserve) {
            $baseFee = $this->fetchOwnerReserveFee();
        }

        /*
         * Multi-signed Transaction
         * 10 drops x (1 + Number of Signatures Provided)
         */
        if ($signersCount > 0) {
            $baseFee = BigDecimal::sum($baseFee, self::scaleValue($netFeeDrops, 1 + $signersCount));
        }

        // The owner reserve is a protocol requirement, so maxFeeXrp must not cap it
        $maxFeeDrops = xrpToDrops($this->client->getMaxFeeXrp());
        $totalFee = $paysOwnerReserve ? $baseFee : BigDecimal::min($baseFee, $maxFeeDrops);

        $tx['Fee'] = (string)$totalFee->toScale(0, RoundingMode::CEILING);
    }

    /**
     * @param string $value
     * @param int|float $multiplier
     * @return BigDecimal
     * @throws MathException
     */
    public static function scaleValue(string $value, int|float $multiplier): BigDecimal
    {
        // brick/math deprecates passing floats; the multipliers here are exact
        // binary fractions, so the string form is lossless.
        return BigDecimal::of($value)->multipliedBy((string)$multiplier);
    }

    /**
     * @param array $tx
     * @return void
     */
    public function setLatestValidatedLedgerSequence(array &$tx): void
    {
        $tx['LastLedgerSequence'] = $this->client->getLedgerIndex() + self::LEDGER_OFFSET;
    }

    /**
     * An account holding Escrows, PayChannels, RippleStates or Checks cannot
     * be deleted.
     *
     * @param array $tx
     * @return void
     * @throws Exception
     */
    public function checkAccountDeleteBlockers(array &$tx): void
    {
        $accountObjectsRequest = new AccountObjectsRequest(
            account: $tx['Account'],
            ledgerIndex: 'validated',
            deletionBlockersOnly: true
        );

        $accountObjectsResponse = $this->client->request($accountObjectsRequest)->wait();

        $blockers = $accountObjectsResponse->getResult()['account_objects'] ?? [];

        if (count($blockers) > 0) {
            throw new Exception("Account {$tx['Account']} cannot be deleted; there are Escrows, PayChannels, RippleStates, or Checks associated with the account.");
        }
    }
}
