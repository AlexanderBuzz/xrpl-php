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
 * Flags of the VaultCreate transaction.
 *
 * Values are those of rippled 3.4.0 (TxFlags.h); combine them with |.
 * Single Asset Vault is not active on Mainnet at the time of writing.
 * https://xrpl.org/vaultcreate.html
 */
final class VaultCreateFlags
{
    use FlagSet;

    /** Only accounts holding a credential accepted by the vault's domain can deposit. */
    public const tfVaultPrivate = 0x00010000;

    /** Vault shares cannot be transferred. */
    public const tfVaultShareNonTransferable = 0x00020000;
}
