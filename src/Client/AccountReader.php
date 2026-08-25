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

use Exception;
use Hardcastle\XRPL_PHP\Models\Account\AccountInfoRequest;
use Hardcastle\XRPL_PHP\Models\Account\AccountLinesRequest;
use Hardcastle\XRPL_PHP\Models\Account\AccountTxRequest;
use Hardcastle\XRPL_PHP\Models\ErrorResponse;

use function Hardcastle\XRPL_PHP\Sugar\dropsToXrp;

/**
 * Reads account state from the ledger: balances and transaction history.
 *
 * This is the object form of Sugar\getXrpBalance(), getBalances() and
 * getTransactions(), which stay available as deprecated wrappers.
 */
class AccountReader
{
    public function __construct(private readonly JsonRpcClient $client)
    {
    }

    /**
     * The XRP balance of an account, in XRP rather than drops.
     *
     * @throws Exception
     */
    public function getXrpBalance(
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated'
    ): string {
        $response = $this->client->request(new AccountInfoRequest(
            account: $address,
            ledgerHash: $ledgerHash,
            ledgerIndex: $ledgerIndex
        ))->wait();

        if ($response instanceof ErrorResponse) {
            throw new Exception($response->getError());
        }

        return dropsToXrp($response->getResult()['account_data']['Balance']);
    }

    /**
     * XRP and trust line balances, following the result marker until the
     * ledger has no more pages.
     *
     * @return array<int, array{currency: string, value: string, issuer?: string}>
     * @throws Exception
     */
    public function getBalances(
        string $address,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?string $peer = null,
        ?int $limit = null
    ): array {
        $balances = [];

        // A peer filter asks about one counterparty, where XRP has no meaning
        if (!$peer) {
            try {
                $balances[] = [
                    'currency' => 'XRP',
                    'value' => $this->getXrpBalance($address, $ledgerHash, $ledgerIndex),
                ];
            } catch (Exception) {
                // An account can be gone and still be referenced by trust lines
            }
        }

        $marker = null;
        while (true) {
            $response = $this->client->request(new AccountLinesRequest(
                account: $address,
                ledgerHash: $ledgerHash,
                ledgerIndex: $ledgerIndex,
                peer: $peer,
                limit: $limit,
                marker: $marker
            ))->wait();

            if ($response instanceof ErrorResponse) {
                if ($response->getError() === 'actNotFound' && !empty($balances)) {
                    break;
                }
                throw new Exception($response->getError());
            }

            $result = $response->getResult();
            foreach ($result['lines'] as $line) {
                $balances[] = [
                    'value' => $line['balance'],
                    'currency' => $line['currency'],
                    'issuer' => $line['account'],
                ];
            }

            $marker = $result['marker'] ?? null;
            if (!$marker || ($limit && count($balances) >= $limit)) {
                break;
            }
        }

        return ($limit && count($balances) > $limit)
            ? array_slice($balances, 0, $limit)
            : $balances;
    }

    /**
     * The transaction history of an account, following the result marker.
     *
     * @throws Exception
     */
    public function getTransactions(
        string $address,
        ?int $ledgerIndexMin = null,
        ?int $ledgerIndexMax = null,
        ?string $ledgerHash = null,
        ?string $ledgerIndex = 'validated',
        ?bool $binary = null,
        ?bool $forward = null,
        ?int $limit = null,
        mixed $marker = null
    ): array {
        $transactions = [];

        while (true) {
            $response = $this->client->request(new AccountTxRequest(
                account: $address,
                ledgerIndexMin: $ledgerIndexMin,
                ledgerIndexMax: $ledgerIndexMax,
                ledgerHash: $ledgerHash,
                ledgerIndex: $ledgerIndex,
                binary: $binary,
                forward: $forward,
                limit: $limit,
                marker: $marker
            ))->wait();

            if ($response instanceof ErrorResponse) {
                throw new Exception($response->getError());
            }

            $result = $response->getResult();
            $transactions = array_merge($transactions, $result['transactions']);

            $marker = $result['marker'] ?? null;
            if (!$marker || ($limit && count($transactions) >= $limit)) {
                break;
            }
        }

        return ($limit && count($transactions) > $limit)
            ? array_slice($transactions, 0, $limit)
            : $transactions;
    }
}
