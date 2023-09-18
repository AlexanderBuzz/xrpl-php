<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core;

use ArrayAccess;
use Brick\Math\BigInteger;
use Exception;
use phpDocumentor\Reflection\Types\Self_;
use SplFixedArray;

/**
 * Concise Transaction Identifier
 */
class Ctid
{
    private const FILLER = 0xc0000000;

    private Buffer $internal;

    /**
     * // https://github.com/XRPLF/XRPL-Standards/discussions/91
     * @param string $ctidAsHex
     * @throws Exception
     */
    public function __construct(string $ctidAsHex)
    {
        $this->internal = Buffer::from($ctidAsHex);
    }

    /**
     * @param int $ledgerIndex
     * @param int $transactionIndex
     * @param int $networkId
     * @return Ctid
     * @throws Exception
     */
    public static function fromRawValues(int $ledgerIndex, int $transactionIndex, int $networkId): Ctid
    {
        // TODO: Create separate "byteFiller method in Buffer"

        $ledgerIndexHex = dechex(self::FILLER + $ledgerIndex);
        $transactionIndexHex = str_pad(dechex($transactionIndex), 4, "0", STR_PAD_LEFT);
        $networkId = str_pad(dechex($networkId), 4, "0", STR_PAD_LEFT);

        return new Ctid($ledgerIndexHex . $transactionIndexHex . $networkId);
    }

    /**
     * @param string $ctidAsHex
     * @return Ctid
     * @throws Exception
     */
    public static function fromCtid(string $ctidAsHex): Ctid
    {
        return new Ctid($ctidAsHex);
    }

    /**
     * @return string
     */
    public function getHex(): string
    {
        return $this->internal->toString();
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getLedgerIndex(): int
    {
        return $this->internal->slice(0, 4)->toInt() - self::FILLER;
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getTransactionIndex(): int
    {
        return $this->internal->slice(4, 6)->toInt();
    }

    /**
     * @return int
     * @throws Exception
     */
    public function getNetworkId(): int
    {
        return $this->internal->slice(6, 8)->toInt();
    }
}