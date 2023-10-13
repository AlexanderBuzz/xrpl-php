<?php declare(strict_types=1);
/**
 * XRPL-PHP
 *
 * Copyright (c) Alexander Busse | Hardcastle Technologies
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace XRPL_PHP\Core\RippleAddressCodec;

use XRPL_PHP\Core\Buffer;
use SplFixedArray;

class BaseX
{
    private const SIZE = 256;

    private string $alphabet;

    private SplFixedArray $baseMap;

    private int $base;

    private string $leader;

    private float $factor;

    private float $inverseFactor;

    public function __construct(string $alphabet)
    {
        if (strlen($alphabet) > self::SIZE) {
            throw new \Exception('Alphabet too long');
        }

        $this->alphabet = $alphabet;

        $this->baseMap = new SplFixedArray(self::SIZE);

        for ($i = 0; $i < self::SIZE; $i++) {
            $this->baseMap[$i] = 255;
        }

        for ($i = 0; $i < strlen($this->alphabet); $i++) {
            $charCode = ord($this->alphabet[$i]);
            if ($this->baseMap[$charCode] !== 255) {
                throw new \Exception($this->alphabet[$i] . ' is ambiguious');
            }
            $this->baseMap[$charCode] = $i;
        }

        $this->base = strlen($this->alphabet);
        $this->leader = $this->alphabet[0];
        $this->factor = log($this->base) / log(256);            //0.7322476243909465
        $this->inverseFactor = log(256) / log($this->base);     //1.365658237309761
    }

    public function encode(Buffer $bytes): string
    {
        $zeroes = 0;
        $length = 0;
        $pbegin = 0;
        $pend = $bytes->getLength();
        $_bytes = $bytes->toArray();
        while ($pbegin !== $pend && $_bytes[$pbegin] == 0) {
            $pbegin++;
            $zeroes++;
        }

        $size = $this->unsignedRightShift((int)abs(($pend - $pbegin) * $this->inverseFactor + 1), 0);
        $b58 = array_fill(0, $size, 0);

        while ($pbegin !== $pend) {
            $carry = $_bytes[$pbegin];
            $i = 0;
            for ($it1 = $size - 1; ($carry !== 0 || $i < $length) && ($it1 !== -1); $it1--, $i++) {
                $carry += $this->unsignedRightShift((int)abs(256 * $b58[$it1]), 0);
                $b58[$it1] = $this->unsignedRightShift((int)abs($carry % $this->base), 0);
                $carry = $this->unsignedRightShift((int)abs($carry / $this->base), 0);
            }
            if ($carry !== 0) {
                throw new \Exception('Non-zero carry');
            }
            $length = $i;
            $pbegin++;
        }

        $it2 = $size - $length;
        while ($it2 !== $size && $b58[$it2] === 0) {
            $it2++;
        }

        $str = str_repeat($this->leader, $zeroes);
        for (; $it2 < $size; ++$it2) {
            $str .= $this->alphabet[$b58[$it2]];
        }
        return $str;
    }

    public function decode(string $string): Buffer
    {
        $buffer = $this->decodeUnsafe($string);
        if (!$buffer) {
            throw new \Exception('No ' . $this->base . ' character');
        }

        return $buffer;
    }

    public function decodeUnsafe(string $source): ?Buffer
    {
        if (strlen($source) === 0) {
            return new Buffer();
        }

        $psz = 0;
        if ($source[$psz] === ' ') {
            return new Buffer();
        }

        //Skip and count leading '1's
        $zeroes = 0;
        $length = 0;
        while ($source[$psz] === $this->leader) {
            $zeroes++;
            $psz++;
        }

        $size = $this->unsignedRightShift((int)abs(((strlen($source) - $psz) * $this->factor) + 1), 0); // log(58) / log(256), rounded up.
        $b256 = array_fill(0, $size, 0);

        while (isset($source[$psz])) {

            $carry = $this->baseMap[ord($source[$psz])];

            if ($carry === 255) {
                return new Buffer();
            }

            $i = 0;
            for ($it3 = $size - 1; ($carry !== 0 || $i < $length) && ($it3 !== -1); $it3--, $i++) {
                $carry += $this->unsignedRightShift((int)abs($this->base * $b256[$it3]), 0);
                $b256[$it3] = $this->unsignedRightShift((int)abs($carry % 256), 0);
                $carry = $this->unsignedRightShift((int)abs($carry / 256), 0);
            }

            if ($carry !== 0) {
                throw new \Exception('Non-zero carry');
            }
            $length = $i;
            $psz++;
        }

        if (isset($source[$psz]) && $source[$psz] === ' ') {
            return new Buffer();
        }

        $it4 = $size - $length;
        while ($it4 !== $size && $b256[$it4] === 0) {
            $it4++;
        }

        $vch = Buffer::from(str_repeat('00', $zeroes + ($size - $it4)));
        $vch = $vch->toArray();
        $j = $zeroes;

        while ($it4 !== $size) {
            $vch[$j++] = $b256[$it4++];
        }

        $hexStr = join(array_map(function ($item) {
            return sprintf('%02X', $item);
        }, $vch));

        //decimalArrayToHexStr
        return Buffer::from($hexStr);
    }

    /**
     * This function replaces JavaScripts unsigned right shift operator (>>>)
     *
     * @param int $value
     * @param int $steps
     * @return int
     */
    private function unsignedRightShift(int $value, int $steps): int
    {
        if ($steps === 0) {
            return $value;
        }

        return ($value >> $steps) & ~(1 << (8 * PHP_INT_SIZE - 1) >> ($steps - 1));
    }


}