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
 * Flags of the AccountSet transaction.
 *
 * Values are those of rippled 3.3.0 (TxFlags.h); combine them with |.
 * https://xrpl.org/accountset.html
 */
final class AccountSetFlags
{
    use FlagSet;

    /** Same as SetFlag asfRequireDest. */
    public const tfRequireDestTag = 0x00010000;

    /** Same as ClearFlag asfRequireDest. */
    public const tfOptionalDestTag = 0x00020000;

    /** Same as SetFlag asfRequireAuth. */
    public const tfRequireAuth = 0x00040000;

    /** Same as ClearFlag asfRequireAuth. */
    public const tfOptionalAuth = 0x00080000;

    /** Same as SetFlag asfDisallowXRP. */
    public const tfDisallowXRP = 0x00100000;

    /** Same as ClearFlag asfDisallowXRP. */
    public const tfAllowXRP = 0x00200000;
}
