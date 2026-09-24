<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hardcastle\XRPL_PHP\Models\Ledger\Flags;

use Hardcastle\XRPL_PHP\Models\Common\FlagSet;

/**
 * Flags of the Vault ledger entry.
 *
 * Values are those of rippled 3.4.0 (LedgerFormats.h); combine them with |.
 * Single Asset Vault is not active on Mainnet at the time of writing.
 * https://xrpl.org/vault.html
 */
final class VaultFlags
{
    use FlagSet;

    /** Only accounts holding a credential accepted by the vault's domain can deposit. */
    public const lsfVaultPrivate = 0x00010000;
}
