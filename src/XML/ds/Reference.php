<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\MissingElementException;
use SimpleSAML\XML\Exception\TooManyElementsException;

use function array_pop;

/**
 * Class representing a ds:Reference element.
 *
 * @package simplesamlphp/xml-security
 */
final class Reference extends AbstractDsElement
{
    /** @var \SimpleSAML\XMLSecurity\XML\ds\Transforms|null */
    protected ?Transforms $transforms;

    /** @var \SimpleSAML\XMLSecurity\XML\ds\DigestMethod */
    protected DigestMethod $digestMethod;

    /** @var \SimpleSAML\XMLSecurity\XML\ds\DigestValue */
    protected DigestValue $digestValue;

    /** @var string|null $Id */
    protected ?string $Id;

    /** @var string|null $type */
    protected ?string $Type;

    /** @var string|null $URI */
    protected ?string $URI;


    /**
     * Initialize a ds:Reference
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\DigestMethod $digestMethod
     * @param \SimpleSAML\XMLSecurity\XML\ds\DigestValue $digestValue
     * @param \SimpleSAML\XMLSecurity\XML\ds\Transforms|null $transforms
     * @param string|null $Id
     * @param string|null $Type
     * @param string|null $URI
     */
    public function __construct(
        DigestMethod $digestMethod,
        DigestValue $digestValue,
        ?Transforms $transforms = null,
        ?string $Id = null,
        ?string $Type = null,
        ?string $URI = null,
    ) {
        $this->setTransforms($transforms);
        $this->setDigestMethod($digestMethod);
        $this->setDigestValue($digestValue);
        $this->setId($Id);
        $this->setType($Type);
        $this->setURI($URI);
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\Transforms|null
     */
    public function getTransforms(): ?Transforms
    {
        return $this->transforms;
    }


    /**
     * @param \SimpleSAML\XMLSecurity\XML\ds\Transforms|null $transforms
     */
    protected function setTransforms(?Transforms $transforms): void
    {
        $this->transforms = $transforms;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\DigestMethod
     */
    public function getDigestMethod(): DigestMethod
    {
        return $this->digestMethod;
    }


    /**
     * @param \SimpleSAML\XMLSecurity\XML\ds\DigestMethod $digestMethod
     */
    private function setDigestMethod(DigestMethod $digestMethod): void
    {
        $this->digestMethod = $digestMethod;
    }


    /**
     * @return \SimpleSAML\XMLSecurity\XML\ds\DigestValue
     */
    public function getDigestValue(): DigestValue
    {
        return $this->digestValue;
    }


    /**
     * @param \SimpleSAML\XMLSecurity\XML\ds\DigestValue $digestValue
     */
    private function setDigestValue(DigestValue $digestValue): void
    {
        $this->digestValue = $digestValue;
    }


    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * @param string|null $Id
     */
    private function setId(?string $Id): void
    {
        Assert::nullOrValidNCName($Id);
        $this->Id = $Id;
    }


    /**
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->Type;
    }


    /**
     * @param string|null $Type
     */
    private function setType(?string $Type): void
    {
        Assert::nullOrValidURI($Type);
        $this->Type = $Type;
    }


    /**
     * @return string|null
     */
    public function getURI(): ?string
    {
        return $this->URI;
    }


    /**
     * @param string|null $URI
     */
    private function setURI(?string $URI): void
    {
        Assert::nullOrValidURI($URI);
        $this->URI = $URI;
    }


    /**
     * Determine whether this is an xpointer reference.
     *
     * @return bool
     */
    public function isXPointer(): bool
    {
        return !empty($this->URI) && str_starts_with($this->URI, '#xpointer');
    }


    /**
     * Convert XML into a Reference element
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Reference', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Reference::NS, InvalidDOMElementException::class);

        $Id = self::getAttribute($xml, 'Id', null);
        $Type = self::getAttribute($xml, 'Type', null);
        $URI = self::getAttribute($xml, 'URI', null);

        $transforms = Transforms::getChildrenOfClass($xml);
        Assert::maxCount(
            $transforms,
            1,
            'A <ds:Reference> may contain just one <ds:Transforms>.',
            TooManyElementsException::class,
        );

        $digestMethod = DigestMethod::getChildrenOfClass($xml);
        Assert::count(
            $digestMethod,
            1,
            'A <ds:Reference> must contain a <ds:DigestMethod>.',
            MissingElementException::class,
        );

        $digestValue = DigestValue::getChildrenOfClass($xml);
        Assert::count(
            $digestValue,
            1,
            'A <ds:Reference> must contain a <ds:DigestValue>.',
            MissingElementException::class,
        );

        return new static(
            array_pop($digestMethod),
            array_pop($digestValue),
            empty($transforms) ? null : array_pop($transforms),
            $Id,
            $Type,
            $URI,
        );
    }


    /**
     * Convert this Reference element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this Reference element to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        if ($this->getId() !== null) {
            $e->setAttribute('Id', $this->getId());
        }
        if ($this->getType() !== null) {
            $e->setAttribute('Type', $this->getType());
        }
        if ($this->getURI() !== null) {
            $e->setAttribute('URI', $this->getURI());
        }

        if ($this->getTransforms() !== null) {
            $this->getTransforms()->toXML($e);
        }

        $this->getDigestMethod()->toXML($e);
        $this->getDigestValue()->toXML($e);

        return $e;
    }
}
