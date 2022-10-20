<?php

namespace XRPL_PHP\Sugar;

use Brick\Math\BigDecimal;
use Brick\Math\BigInteger;
use Exception;

const DROPS_PER_XRP = 1000000.0;
const MAX_FRACTION_LENGTH = 6;
const SANITY_CHECK = "/^-?[0-9.]+$/u";

if (! function_exists('XRPL_PHP\Sugar\dropsToXrp')) {
    /**
     * Create a new queued Closure event listener.
     *
     * @param string $dropsToConvert
     * @return string
     * @throws Exception
     */
    function dropsToXrp(mixed $dropsToConvert): string
    {
        $drops = (string) BigInteger::of($dropsToConvert);

        if (str_contains($drops, '.')) {
            throw new Exception("dropsToXrp: value \"{$drops}\" has too many decimal places.");
        }

        if (!preg_match(SANITY_CHECK, $drops)) {
            throw new Exception("dropsToXrp: failed sanity check - value \"{$drops}\" does not match (^-?[0-9]+$).");
        }

        return (string) BigDecimal::of($drops)->exactlyDividedBy(DROPS_PER_XRP);
    }
}

if (! function_exists('XRPL_PHP\Sugar\xrpToDrops')) {
    /**
     * Create a new queued Closure event listener.
     *
     * @param  string $xrpToConvert
     * @return string
     */
    function xrpToDrops(string $xrpToConvert): string
    {
        $xrp = (string) BigDecimal::of($xrpToConvert);

        if (!preg_match(SANITY_CHECK, $xrp)) {
            throw new Exception("xrpToDrops: failed sanity check - value \"{$xrp}\" does not match (^-?[0-9]+$).");
        }

        $components = explode('.', $xrp);
        if(count($components) > 2) {
            throw new Exception("xrpToDrops: failed sanity check - value \"{$xrp}\" has too many decimal points.");
        }

        if(isset($components[1]) && strlen(rtrim($components[1], '0')) > MAX_FRACTION_LENGTH) {
            throw new Exception("xrpToDrops: value  \"{$xrp}\" has too many decimal places.");
        }

        return BigDecimal::of($xrp)->multipliedBy(DROPS_PER_XRP)->toBigInteger()->toBase(10);
    }
}