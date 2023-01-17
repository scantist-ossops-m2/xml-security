<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Chunk;
use SimpleSAML\XML\Constants as C;
use SimpleSAML\XML\ElementInterface;
use SimpleSAML\XML\ExtendableElementTrait;
use SimpleSAML\XMLSecurity\XML\xenc\Transforms;

/**
 * Abstract class representing references. No custom elements are allowed.
 *
 * @package simplesamlphp/xml-security
 */
abstract class AbstractReference extends AbstractXencElement
{
    use ExtendableElementTrait;

    /** The namespace-attribute for the xs:any element */
    public const NAMESPACE = C::XS_ANY_NS_OTHER;


    /**
     * AbstractReference constructor.
     *
     * @param string $uri
     * @param \SimpleSAML\XML\ElementInterface[] $elements
     */
    final public function __construct(
        protected string $uri,
        array $elements = [],
    ) {
        Assert::validURI($uri, SchemaViolationException::class); // Covers the empty string

        $this->setElements($elements);
    }


    /**
     * Get the value of the URI attribute of this reference.
     *
     * @return string
     */
    public function getURI(): string
    {
        return $this->uri;
    }


    /**
     * @inheritDoc
     *
     * @throws \SimpleSAML\XML\Exception\InvalidDOMElementException
     *   if the qualified name of the supplied element is wrong
     * @throws \SimpleSAML\XML\Exception\MissingAttributeException
     *   if the supplied element is missing one of the mandatory attributes
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, static::getClassName(static::class), InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, static::NS, InvalidDOMElementException::class);

        /** @psalm-var string $URI */
        $URI = self::getAttribute($xml, 'URI');

        $elements = [];
        foreach ($xml->childNodes as $element) {
            if ($element instanceof DOMElement) {
                $elements[] = new Chunk($element);
            }
        }

        return new static($URI, $elements);
    }


    /**
     * @inheritDoc
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);
        $e->setAttribute('URI', $this->getUri());

        /** @psalm-var \SimpleSAML\XML\SerializableElementInterface $elt */
        foreach ($this->getElements() as $elt) {
            $elt->toXML($e);
        }

        return $e;
    }
}
