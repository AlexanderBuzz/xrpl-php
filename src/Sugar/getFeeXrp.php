<?php

namespace XRPL_PHP\Sugar;

use Brick\Math\BigDecimal;
use Exception;
use XRPL_PHP\Client\JsonRpcClient;
use XRPL_PHP\Models\Methods\ServerInfoRequest;

if (! function_exists('XRPL_PHP\Sugar\getFeeXrp')) {

    function getFeeXrp(
        JsonRpcClient $client,
        ?int $cushion = null
    ): string
    {
       $feeCushion = $cushion ?? $client->getFeeCushion();

       $serverInfoRequest = new ServerInfoRequest();

       $serverInfoResponse = $client->request($serverInfoRequest)->wait();

       $serverInfo = $serverInfoResponse->getResult()['info'];

       $baseFee = $serverInfo['validated_ledger']['base_fee_xrp'] ?? null;

       if(is_null($baseFee)) {
           throw new Exception('getFeeXrp: Could not get base_fee_xrp from server_info');
       }

       $baseFeeXrp = BigDecimal::of($baseFee);
       if(is_null($serverInfo['load_factor'])) {
           $serverInfo['load_factor'] = 1;
       }

       $fee = $baseFeeXrp->multipliedBy($serverInfo['load_factor'])->multipliedBy($feeCushion);

       $fee = BigDecimal::min($fee, $client->getMaxFeeXrp());

       //TODO: constant NUM_DECIMAL_PLACES
       return $fee->toScale(6);
    }
}