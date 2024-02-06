<?php declare(strict_types=1);

if (extension_loaded('bcmath') && !function_exists('bchexdec')) {
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
if (extension_loaded('bcmath') && !function_exists('bcdechex')) {
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

/**
 * @param string $hex
 * @return string
 */
function hex2str(string $hex): string
{
    return pack('H*', $hex);
}

/**
 * @param string $str
 * @return string
 */
function str2hex(string $str): string
{
    $unpacked = unpack('H*', $str);
    return array_shift($unpacked);
}