<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\Constants;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XMLSecurity\Exception\InvalidArgumentException;

/**
 * Class representing a ds:Object element.
 *
 * @package simplesamlphp/xml-security
 */
final class DsObject extends AbstractDsElement
{
    use ExtendableElementTrait;

    /** @var string */
    public const LOCALNAME = 'Object';

    /** @var string */
    public const NAMESPACE = Constants::XS_ANY_NS_ANY;


    /**
     * Initialize a ds:Object element.
     *
     * @param string|null $Id
     * @param string|null $MimeType
     * @param string|null $Encoding
     * @param \SimpleSAML\XML\ElementInterface[] $elements
     */
    public function __construct(
        protected ?string $Id = null,
        protected ?string $MimeType = null,
        protected ?string $Encoding = null,
        array $elements = [],
    ) {
        Assert::nullOrValidNCName($Id);
        Assert::nullOrValidURI($Encoding);

        $this->setElements($elements);
    }


    /**
     * Collect the value of the Id-property
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->Id;
    }


    /**
     * Collect the value of the MimeType-property
     *
     * @return string|null
     */
    public function getMimeType(): ?string
    {
        return $this->MimeType;
    }


    /**
     * Collect the value of the Encoding-property
     *
     * @return string|null
     */
    public function getEncoding(): ?string
    {
        return $this->Encoding;
    }


    /**
     * Test if an object, at the state it's in, would produce an empty XML-element
     *
     * @return bool
     */
    public function isEmptyElement(): bool
    {
        return (
            empty($this->elements)
            && empty($this->Id)
            && empty($this->MimeType)
            && empty($this->Encoding)
        );
    }


    /**
     * Convert XML into a ds:Object
     *
     * @param \DOMElement $xml The XML element we should load
     * @return static
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   If the qualified name of the supplied element is wrong
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Object', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, DsObject::NS, InvalidDOMElementException::class);

        $Id = DsObject::getAttribute($xml, 'Id', null);
        $MimeType = DsObject::getAttribute($xml, 'MimeType', null);
        $Encoding = DsObject::getAttribute($xml, 'Encoding', null);

        $elements = [];
        foreach ($xml->childNodes as $elt) {
            if (!($elt instanceof DOMElement)) {
                // @TODO: support mixed content
                continue;
            } elseif ($elt->namespaceURI === self::NS) {
                $elements[] = match ($elt->localName) {
                    'SignatureProperties' => SignatureProperties::fromXML($elt),
                    'Manifest' => Manifest::fromXML($elt),
                    default => new Chunk($elt),
                };
            }

            $elements[] = new Chunk($elt);
        }

        return new static($Id, $MimeType, $Encoding, $elements);
    }


    /**
     * Convert this ds:Object element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this ds:Object element to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        if ($this->Id !== null) {
            $e->setAttribute('Id', $this->getId());
        }

        if ($this->MimeType !== null) {
            $e->setAttribute('MimeType', $this->getMimeType());
        }

        if ($this->Encoding !== null) {
            $e->setAttribute('Encoding', $this->getEncoding());
        }

        /** @psalm-var \SimpleSAML\XML\SerializableElementInterface[] $this->elements */
        foreach ($this->elements as $elt) {
            if (!$elt->isEmptyElement()) {
                $elt->toXML($e);
            }
        }

        return $e;
    }
}
