<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XMLSecurity\XMLSecurityDSig;

/**
 * Class \SimpleSAML\XMLSecurity\XML\ds\KeyNameTest
 *
 * @covers \SimpleSAML\XMLSecurity\XML\ds\AbstractDsElement
 * @covers \SimpleSAML\XMLSecurity\XML\ds\KeyName
 *
 * @author Tim van Dijen, <tvdijen@gmail.com>
 * @package simplesamlphp/saml2
 */
final class KeyNameTest extends TestCase
{
    /** @var \DOMDocument */
    private DOMDocument $document;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->document = DOMDocumentFactory::fromFile(
            dirname(dirname(dirname(dirname(__FILE__)))) . '/tests/resources/xml/ds_KeyName.xml'
        );
    }


    /**
     * @return void
     */
    public function testMarshalling(): void
    {
        $keyName = new KeyName('testkey');

        $this->assertEquals('testkey', $keyName->getName());

        $this->assertEquals($this->document->saveXML($this->document->documentElement), strval($keyName));
    }


    /**
     * @return void
     */
    public function testUnmarshalling(): void
    {
        $keyName = KeyName::fromXML($this->document->documentElement);

        $this->assertEquals('testkey', $keyName->getName());
    }


    /**
     * Test serialization / unserialization
     */
    public function testSerialization(): void
    {
        $this->assertEquals(
            $this->document->saveXML($this->document->documentElement),
            strval(unserialize(serialize(KeyName::fromXML($this->document->documentElement))))
        );
    }
}
