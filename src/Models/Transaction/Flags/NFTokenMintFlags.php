<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Transaction\Flags;

use Hardcastle\XRPL_PHP\Models\Common\FlagSet;

/**
 * Flags of the NFTokenMint transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/nftokenmint.html
 */
final class NFTokenMintFlags
{
    use FlagSet;

    /** The issuer, or its authorized minter, can burn the token. */
    public const tfBurnable = 0x00000001;

    /** The token can only be bought or sold for XRP. */
    public const tfOnlyXRP = 0x00000002;

    /** The token can be transferred to others; otherwise only to and from the issuer. */
    public const tfTransferable = 0x00000008;

    /** The URI can be changed with NFTokenModify (DynamicNFT). */
    public const tfMutable = 0x00000010;
}
