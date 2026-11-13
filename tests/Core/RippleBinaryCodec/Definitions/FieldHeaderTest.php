<?php declare(strict_types=1);

namespace Hardcastle\XRPL_PHP\Test\Core\RippleBinaryCodec\Definitions;

use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\Definitions;
use Hardcastle\XRPL_PHP\Core\RippleBinaryCodec\Definitions\FieldHeader;
use PHPUnit\Framework\TestCase;

/**
 * A field header is a plain value since 4.0.0: no collection library, its
 * hash() is the array key Definitions uses to map a header back to its field.
 */
final class FieldHeaderTest extends TestCase
{
    public function testHashIsTheArrayKeyOfTypeAndFieldCode(): void
    {
        $this->assertSame('8:1', (new FieldHeader(8, 1))->hash());

        $definitions = Definitions::getInstance();
        $header = $definitions->getFieldHeaderFromName('Account');
        $this->assertSame('8:1', $header->hash());
        $this->assertSame('Account', $definitions->getFieldNameFromHeader($header));
    }

    public function testEqualsComparesTypeAndFieldCode(): void
    {
        $this->assertTrue((new FieldHeader(8, 1))->equals(new FieldHeader(8, 1)));
        $this->assertFalse((new FieldHeader(8, 1))->equals(new FieldHeader(8, 3)));
        $this->assertFalse((new FieldHeader(8, 1))->equals(new FieldHeader(1, 8)));
    }

    public function testEqualsIsFalseForAnythingThatIsNotAFieldHeader(): void
    {
        $this->assertFalse((new FieldHeader(8, 1))->equals('8:1'));
        $this->assertFalse((new FieldHeader(8, 1))->equals(null));
        $this->assertFalse((new FieldHeader(8, 1))->equals(new \stdClass()));
    }

    public function testNoCollectionLibraryIsNeeded(): void
    {
        $this->assertSame([], class_implements(new FieldHeader(8, 1)));
    }
}
