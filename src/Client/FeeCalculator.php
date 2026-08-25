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
use Brick\Math\RoundingMode;
use Exception;
use Hardcastle\XRPL_PHP\Models\ServerInfo\ServerInfoRequest;

/**
 * The current network fee.
 *
 * This is the object form of Sugar\getFeeXrp(), which stays available as a
 * deprecated wrapper.
 */
class FeeCalculator
{
    public function __construct(private readonly JsonRpcClient $client)
    {
    }

    /**
     * The base fee, scaled by the server's load factor and a cushion, capped
     * at the client's maxFeeXrp. In XRP, not drops.
     *
     * @param float|null $cushion Overrides the client's fee cushion
     * @return string
     * @throws Exception
     */
    public function getFeeXrp(?float $cushion = null): string
    {
        $feeCushion = $cushion ?? $this->client->getFeeCushion();

        $serverInfo = $this->client->request(new ServerInfoRequest())->wait()->getResult()['info'];

        $baseFee = $serverInfo['validated_ledger']['base_fee_xrp'] ?? null;
        if (is_null($baseFee)) {
            throw new Exception('getFeeXrp: Could not get base_fee_xrp from server_info');
        }

        $loadFactor = $serverInfo['load_factor'] ?? 1;

        // rippled sends base_fee_xrp as a JSON number, so it arrives as a
        // float. brick/math wants it as a string.
        $fee = BigDecimal::of((string)$baseFee)
            ->multipliedBy((string)$loadFactor)
            ->multipliedBy((string)$feeCushion);

        $fee = BigDecimal::min($fee, $this->client->getMaxFeeXrp());

        return (string)$fee->toScale(6, RoundingMode::UP);
    }
}
