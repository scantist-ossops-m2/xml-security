<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\Test\XML\ds;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use SimpleSAML\Assert\AssertionFailedException;
use SimpleSAML\XML\DOMDocumentFactory;
use SimpleSAML\XML\TestUtils\SchemaValidationTestTrait;
use SimpleSAML\XML\TestUtils\SerializableElementTestTrait;
use SimpleSAML\XMLSecurity\Test\XML\XMLDumper;
use SimpleSAML\XMLSecurity\TestUtils\PEMCertificatesMock;
use SimpleSAML\XMLSecurity\XML\ds\X509Certificate;

use function dirname;
use function str_replace;
use function strval;
use function substr;

/**
 * Class \SimpleSAML\XMLSecurity\Test\XML\ds\X509CertificateTest
 *
 * @covers \SimpleSAML\XMLSecurity\XML\ds\AbstractDsElement
 * @covers \SimpleSAML\XMLSecurity\XML\ds\X509Certificate
 *
 * @package simplesamlphp/xml-security
 */
final class X509CertificateTest extends TestCase
{
    use SerializableElementTestTrait;

    /** @var string */
    private string $certificate;


    /**
     */
    public function setUp(): void
    {
        $this->testedClass = X509Certificate::class;

        $this->xmlRepresentation = DOMDocumentFactory::fromFile(
            dirname(__FILE__, 3) . '/resources/xml/ds_X509Certificate.xml',
        );

        $this->certificate = str_replace(
            [
                '-----BEGIN CERTIFICATE-----',
                '-----END CERTIFICATE-----',
                '-----BEGIN RSA PUBLIC KEY-----',
                '-----END RSA PUBLIC KEY-----',
                "\r\n",
                "\n",
            ],
            [
                '',
                '',
                '',
                '',
                "\n",
                ''
            ],
            PEMCertificatesMock::getPlainCertificate(PEMCertificatesMock::SELFSIGNED_CERTIFICATE),
        );
    }


    /**
     */
    public function testMarshalling(): void
    {
        $x509cert = new X509Certificate($this->certificate);

        $this->assertEquals(
            XMLDumper::dumpDOMDocumentXMLWithBase64Content($this->xmlRepresentation),
            strval($x509cert),
        );
    }


    /**
     */
    public function testMarshallingInvalidBase64(): void
    {
        $certificate = str_replace(substr($this->certificate, 1), '', $this->certificate);
        $this->expectException(AssertionFailedException::class);
        new X509Certificate($certificate);
    }


    /**
     */
    public function testUnmarshalling(): void
    {
        $x509cert = X509Certificate::fromXML($this->xmlRepresentation->documentElement);

        $this->assertEquals(
            $this->xmlRepresentation->saveXML($this->xmlRepresentation->documentElement),
            strval($x509cert),
        );
    }
}
