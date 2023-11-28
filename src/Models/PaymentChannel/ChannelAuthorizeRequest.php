<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Models\PaymentChannel;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/channel_authorize.html
 */
class ChannelAuthorizeRequest extends BaseRequest
{
    protected string $command = "channel_authorize";

    public function __construct(
        protected string $channelId,
        protected string $amount,
        protected ?string $seed,
        protected ?string $seedHex,
        protected ?string $passphrase,
        protected ?string $keyType,
    ) {}
}