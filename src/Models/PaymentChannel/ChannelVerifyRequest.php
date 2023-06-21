<?php declare(strict_types=1);

namespace XRPL_PHP\Models\PaymentChannel;

use XRPL_PHP\Models\BaseRequest;

/**
 * public API Methods / Ledger Methods
 * https://xrpl.org/channel_verify.html
 */
class ChannelVerifyRequest extends BaseRequest
{
    protected string $command = "channel_verify";

    public function __construct(
        protected string $channelId,
        protected string $amount,
        protected string $publicKey,
        protected string $signature
    ) {}
}