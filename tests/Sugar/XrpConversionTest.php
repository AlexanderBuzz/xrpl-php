<?php

namespace XRPL_PHP\Test\Sugar;

use PHPUnit\Framework\TestCase;
use function XRPL_PHP\Sugar\dropsToXrp;
use function XRPL_PHP\Sugar\xrpToDrops;

/**
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/utils/dropsToXrp.ts
 * https://github.com/XRPLF/xrpl.js/blob/main/packages/xrpl/test/utils/xrpToDrops.ts
 */
class XrpConversionTest  extends TestCase
{
    public function testDropsToXrp(): void
    {
        //Test typical amount
        $this->assertEquals(
            '2',
            dropsToXrp('2000000')
        );

        //Test fractions
        $this->assertEquals(
            '3.456789',
            dropsToXrp('3456789')
        );

        $this->assertEquals(
            '3.4',
            dropsToXrp('3400000')
        );

        $this->assertEquals(
            '0.000001',
            dropsToXrp('1')
        );

        $this->assertEquals(
            '0.000001',
            dropsToXrp('1')
        );

        $this->assertEquals(
            '0.000001',
            dropsToXrp('1.00')
        );

        //Test zero
        $this->assertEquals(
            '0',
            dropsToXrp('0')
        );

        $this->assertEquals(
            '0',
            dropsToXrp('-0')
        );

        $this->assertEquals(
            '0',
            dropsToXrp('0.00')
        );

        $this->assertEquals(
            '0',
            dropsToXrp('00000000')
        );

        //Test negative values
        $this->assertEquals(
            '-2',
            dropsToXrp('-2000000')
        );

        //Test decimal points
        $this->assertEquals(
            '2',
            dropsToXrp('2000000.')
        );

        $this->assertEquals(
            '-2',
            dropsToXrp('-2000000.')
        );

        //Test BigNumber

        //Test number/int

        //Test scientific notation
    }

    public function testXrpToDrops(): void
    {
        //Test typical amount
        $this->assertEquals(
            '2000000',
            xrpToDrops('2')
        );

        //Test fractions
        $this->assertEquals(
            '3456789',
            xrpToDrops('3.456789')
        );

        $this->assertEquals(
            '3400000',
            xrpToDrops('3.400000')
        );

        $this->assertEquals(
            '1',
            xrpToDrops('0.000001')
        );

        $this->assertEquals(
            '1',
            xrpToDrops('0.0000010')
        );

        //Test zero
        $this->assertEquals(
            '0',
            xrpToDrops('0')
        );

        $this->assertEquals(
            '0',
            xrpToDrops('-0')
        );

        $this->assertEquals(
            '0',
            xrpToDrops('0.000000')
        );

        $this->assertEquals(
            '0',
            xrpToDrops('0.0000000')
        );

        //Test negative values
        $this->assertEquals(
            '-2000000',
            xrpToDrops('-2')
        );

        //Test "works with a value ending with a decimal point"
        $this->assertEquals(
            '2000000',
            xrpToDrops('2.')
        );

        $this->assertEquals(
            '-2000000',
            xrpToDrops('-2.')
        );

        //Test BigNumber

        //Test number/int

        //Test scientific notation

    }
}