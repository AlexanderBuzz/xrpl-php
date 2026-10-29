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
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\BatchFlags;
use Hardcastle\XRPL_PHP\Models\Transaction\Flags\GlobalFlags;
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

    /**
     * How many inner transactions a Batch may carry (XLS-56).
     */
    public const BATCH_MIN_INNER = 2;

    public const BATCH_MAX_INNER = 8;

    public function __construct(private readonly JsonRpcClient $client)
    {
    }

    /**
     * Fill in what a transaction needs before it can be signed.
     *
     * Only fields the transaction does not carry already are touched, so a
     * caller can pin any of them by setting it beforehand. Empty SourceTag and
     * DestinationTag entries are removed, because rippled treats a present tag
     * of 0 differently from an absent one.
     *
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

        if (($tx['TransactionType'] ?? null) === 'Batch') {
            $this->autofillBatch($tx);
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
     * Replace one X-address field with its classic address, and write the tag
     * it carries into the matching tag field.
     *
     * An X-address encodes the destination tag, so a tag given separately has
     * to agree with it.
     *
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
     * Split an address into its classic form and its tag.
     *
     * A classic address is returned unchanged together with the tag that was
     * passed in, so callers do not have to know which form they hold.
     *
     * @param string $account A classic address or an X-address
     * @param int|null $expectedTag Has to match the tag inside an X-address
     * @return array{classicAccount: string, tag: int|false|null}
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
     * Rewrite one address field to its classic form, ignoring any tag.
     *
     * Used for the fields that name an account without addressing a payment to
     * it, such as Authorize, Owner and RegularKey.
     *
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
     * Set Sequence to the account's next one.
     *
     * Read from the current ledger rather than the validated one, so that
     * transactions submitted back to back do not reuse a sequence.
     *
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
     * Fill in what the inner transactions of a Batch need (XLS-56).
     *
     * Each inner transaction gets the tfInnerBatchTxn flag, Fee "0" and an
     * empty SigningPubKey, which is what the ledger requires of it (the fee
     * is paid by the outer transaction, the signatures are the outer one and
     * BatchSigners). An inner transaction without Sequence and
     * TicketSequence gets the next sequence of its account: the outer
     * Account's inner transactions start one above the outer Sequence,
     * every other account's at its current sequence, counting up per
     * account in order of appearance. Mirrors autofillBatchTxn in xrpl.js.
     *
     * Refused, because the ledger would refuse them: fewer than two or more
     * than eight inner transactions, a nested Batch, an inner Fee other than
     * "0", a non-empty inner SigningPubKey, an inner TxnSignature or Signers,
     * and outer Flags that do not name exactly one batch mode.
     *
     * @param array $tx the Batch, with its outer Sequence set
     * @return void
     * @throws Exception
     */
    public function autofillBatch(array &$tx): void
    {
        $modes = BatchFlags::tfAllOrNothing | BatchFlags::tfOnlyOne | BatchFlags::tfUntilFailure | BatchFlags::tfIndependent;
        $mode = ((int) ($tx['Flags'] ?? 0)) & $modes;
        if ($mode === 0 || ($mode & ($mode - 1)) !== 0) {
            throw new Exception('A Batch needs exactly one of the BatchFlags modes in Flags');
        }

        $rawTransactions = $tx['RawTransactions'] ?? [];
        $count = is_array($rawTransactions) ? count($rawTransactions) : 0;
        if ($count < self::BATCH_MIN_INNER || $count > self::BATCH_MAX_INNER) {
            throw new Exception(sprintf(
                'A Batch carries %d to %d inner transactions, this one has %d',
                self::BATCH_MIN_INNER,
                self::BATCH_MAX_INNER,
                $count
            ));
        }

        $sequences = [];
        foreach ($tx['RawTransactions'] as $index => &$wrapper) {
            if (!is_array($wrapper) || !isset($wrapper['RawTransaction']) || !is_array($wrapper['RawTransaction'])) {
                throw new Exception("RawTransactions[{$index}] is not a RawTransaction wrapper");
            }
            $inner = &$wrapper['RawTransaction'];

            if (($inner['TransactionType'] ?? null) === 'Batch') {
                throw new Exception("RawTransactions[{$index}] is a Batch; a Batch cannot nest");
            }
            if (isset($inner['TxnSignature']) || isset($inner['Signers'])) {
                throw new Exception("RawTransactions[{$index}] must not be signed; the Batch is signed as a whole");
            }
            if (isset($inner['Fee']) && (string) $inner['Fee'] !== '0') {
                throw new Exception("RawTransactions[{$index}] must have Fee \"0\"; the Batch pays the fees");
            }
            if (isset($inner['SigningPubKey']) && $inner['SigningPubKey'] !== '') {
                throw new Exception("RawTransactions[{$index}] must have an empty SigningPubKey");
            }

            $this->setValidAddresses($inner);

            $inner['Flags'] = ((int) ($inner['Flags'] ?? 0)) | GlobalFlags::tfInnerBatchTxn;
            $inner['Fee'] = '0';
            $inner['SigningPubKey'] = '';

            if (!isset($inner['Sequence']) && !isset($inner['TicketSequence'])) {
                $account = (string) $inner['Account'];
                if (!isset($sequences[$account])) {
                    if ($account === (string) $tx['Account']) {
                        $sequences[$account] = (int) $tx['Sequence'] + 1;
                    } else {
                        $probe = ['Account' => $account];
                        $this->setNextValidSequenceNumber($probe);
                        $sequences[$account] = (int) $probe['Sequence'];
                    }
                }
                $inner['Sequence'] = $sequences[$account]++;
            }

            unset($inner);
        }
        unset($wrapper);
    }

    /**
     * The fee a Batch has to carry (XLS-56 §2.2): two base fees for the
     * outer transaction, one more per signature in BatchSigners, plus what
     * each inner transaction would cost on its own.
     *
     * Computed on copies, so the inner transactions keep their Fee "0". The
     * signature count comes from BatchSigners when the Batch carries them
     * already, otherwise from $batchSignatures, the number the caller will
     * collect; a Batch of one account has none.
     *
     * @param array $tx the Batch
     * @param int $batchSignatures signatures BatchSigners will carry, when not present yet
     * @return BigDecimal drops, before the multisign and maximum fee rules
     * @throws MathException
     */
    public function calculateBatchFee(array $tx, int $batchSignatures = 0): BigDecimal
    {
        $netFeeDrops = xrpToDrops($this->client->getFeeCalculator()->getFeeXrp());

        $signatures = $batchSignatures;
        if (!empty($tx['BatchSigners']) && is_array($tx['BatchSigners'])) {
            $signatures = 0;
            foreach ($tx['BatchSigners'] as $entry) {
                $signer = $entry['BatchSigner'] ?? [];
                $signatures += isset($signer['Signers']) && is_array($signer['Signers'])
                    ? count($signer['Signers'])
                    : 1;
            }
        }

        $fee = self::scaleValue($netFeeDrops, 2 + $signatures);

        foreach ($tx['RawTransactions'] ?? [] as $wrapper) {
            $inner = $wrapper['RawTransaction'] ?? null;
            if (!is_array($inner)) {
                continue;
            }
            unset($inner['Fee']);
            $this->calculateFeePerTransactionType($inner);
            $fee = BigDecimal::sum($fee, BigDecimal::of($inner['Fee']));
        }

        return $fee;
    }

    /**
     * Set Fee to what this transaction type costs.
     *
     * Usually the network fee, but AccountDelete and AMMCreate burn one owner
     * reserve instead, EscrowFinish pays a surcharge that grows with the
     * size of its fulfillment, and a Batch pays for itself, its BatchSigners
     * and its inner transactions (calculateBatchFee()). Multi-signing adds
     * one network fee per signature.
     *
     * @param array $tx
     * @param int|null $signersCount Number of signatures the transaction will carry
     * @return void
     * @throws MathException
     */
    public function calculateFeePerTransactionType(array &$tx, ?int $signersCount = 0): void
    {
        $netFeeXrp = $this->client->getFeeCalculator()->getFeeXrp();
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

        // A Batch pays for itself, its BatchSigners and every inner transaction.
        // BatchSignatures is a hint for the autofill only, never a field of the transaction.
        if ($tx['TransactionType'] === 'Batch') {
            $baseFee = $this->calculateBatchFee($tx, (int) ($tx['BatchSignatures'] ?? 0));
            unset($tx['BatchSignatures']);
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
     * Multiply a decimal string by a factor.
     *
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
     * Set LastLedgerSequence, the ledger after which the transaction can no
     * longer be included.
     *
     * Without it a transaction could sit in the queue indefinitely, and
     * reliable submission would have no point at which to stop waiting.
     *
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
