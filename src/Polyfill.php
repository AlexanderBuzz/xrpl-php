<?php declare(strict_types=1);

if (!function_exists('bchexdec')) {
    /**
     * hexdec with Uint64 number support
     */
    function bchexdec(string $hex): string
    {
        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd((string)$dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }
}
if (!function_exists('bcdechex')) {
    /**
     * dechex with Uint64 number support
     */
    function bcdechex(string $dec): string
    {
        $last = bcmod($dec, "16");
        $remain = bcdiv(bcsub($dec, $last), "16");
        if($remain == 0) {
            $r = dechex((int)$last);
        } else {
            $r = bcdechex($remain).dechex((int)$last);
        }
        return strtoupper($r);
    }
}
