<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\ds;

use DOMElement;
use SimpleSAML\Assert\Assert;
use SimpleSAML\XML\Exception\InvalidDOMElementException;
use SimpleSAML\XML\Exception\SchemaViolationException;
use SimpleSAML\XML\Exception\TooManyElementsException;
use SimpleSAML\XMLSecurity\Constants as C;
use SimpleSAML\XMLSecurity\XML\ec\InclusiveNamespaces;

use function array_pop;

/**
 * Class representing transforms.
 *
 * @package simplesamlphp/xml-security
 */
class Transform extends AbstractDsElement
{
    /**
     * The algorithm used for this transform.
     *
     * @var string
     */
    protected string $algorithm;

    /**
     * An XPath object.
     *
     * @var \SimpleSAML\XMLSecurity\XML\ds\XPath|null
     */
    protected ?XPath $xpath = null;

    /**
     * An InclusiveNamespaces object.
     *
     * @var \SimpleSAML\XMLSecurity\XML\ec\InclusiveNamespaces|null
     */
    protected ?InclusiveNamespaces $inclusiveNamespaces = null;


    /**
     * Initialize the Transform element.
     *
     * @param string $algorithm
     * @param \SimpleSAML\XMLSecurity\XML\ds\XPath|null $xpath
     * @param \SimpleSAML\XMLSecurity\XML\ec\InclusiveNamespaces|null $prefixes
     */
    final public function __construct(
        string $algorithm,
        ?XPath $xpath = null,
        ?InclusiveNamespaces $inclusiveNamespaces = null,
    ) {
        $this->setAlgorithm($algorithm);
        $this->setXPath($xpath);
        $this->setInclusiveNamespaces($inclusiveNamespaces);
    }


    /**
     * Get the algorithm associated with this transform.
     *
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->algorithm;
    }


    /**
     * Set the value of the algorithm property.
     *
     * @param string $algorithm
     */
    private function setAlgorithm(string $algorithm): void
    {
        Assert::validURI($algorithm, SchemaViolationException::class);
        $this->algorithm = $algorithm;
    }


    /**
     * Get the XPath associated with this transform.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ds\XPath|null
     */
    public function getXPath(): ?XPath
    {
        return $this->xpath;
    }


    /**
     * Set and validate the XPath object.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ds\XPath|null $XPath
     */
    private function setXPath(?XPath $xpath): void
    {
        if ($xpath === null) {
            return;
        }

        Assert::nullOrEq(
            $this->algorithm,
            C::XPATH_URI,
            sprintf('Transform algorithm "%s" required if XPath provided.', C::XPATH_URI),
        );
        $this->xpath = $xpath;
    }


    /**
     * Get the InclusiveNamespaces associated with this transform.
     *
     * @return \SimpleSAML\XMLSecurity\XML\ec\InclusiveNamespaces|null
     */
    public function getInclusiveNamespaces(): ?InclusiveNamespaces
    {
        return $this->inclusiveNamespaces;
    }


    /**
     * Set and validate the InclusiveNamespaces object.
     *
     * @param \SimpleSAML\XMLSecurity\XML\ec\InclusiveNamespaces|null $inclusiveNamespaces
     */
    private function setInclusiveNamespaces(?InclusiveNamespaces $inclusiveNamespaces): void
    {
        if ($inclusiveNamespaces === null) {
            return;
        }

        Assert::oneOf(
            $this->algorithm,
            [
                C::C14N_INCLUSIVE_WITH_COMMENTS,
                C::C14N_EXCLUSIVE_WITHOUT_COMMENTS,
            ],
            sprintf(
                'Transform algorithm "%s" or "%s" required if InclusiveNamespaces provided.',
                C::C14N_EXCLUSIVE_WITH_COMMENTS,
                C::C14N_EXCLUSIVE_WITHOUT_COMMENTS
            ),
        );

        $this->inclusiveNamespaces = $inclusiveNamespaces;
    }


    /**
     * Convert XML into a Transform element.
     *
     * @param \DOMElement $xml The XML element we should load.
     * @return static
     */
    public static function fromXML(DOMElement $xml): static
    {
        Assert::same($xml->localName, 'Transform', InvalidDOMElementException::class);
        Assert::same($xml->namespaceURI, Transform::NS, InvalidDOMElementException::class);

        /** @psalm-var string $alg */
        $alg = self::getAttribute($xml, 'Algorithm');

        $xpath = XPath::getChildrenOfClass($xml);
        Assert::maxCount($xpath, 1, 'Only one XPath element supported per Transform.', TooManyElementsException::class);

        $prefixes = InclusiveNamespaces::getChildrenOfClass($xml);
        Assert::maxCount(
            $prefixes,
            1,
            'Only one InclusiveNamespaces element supported per Transform.',
            TooManyElementsException::class,
        );

        return new static($alg, array_pop($xpath), array_pop($prefixes));
    }


    /**
     * Convert this Transform element to XML.
     *
     * @param \DOMElement|null $parent The element we should append this Transform element to.
     * @return \DOMElement
     */
    public function toXML(DOMElement $parent = null): DOMElement
    {
        $e = $this->instantiateParentElement($parent);

        $algorithm = $this->getAlgorithm();
        $e->setAttribute('Algorithm', $algorithm);

        switch ($algorithm) {
            case C::XPATH_URI:
                $this->getXpath()?->toXML($e);
                break;
            case C::C14N_EXCLUSIVE_WITH_COMMENTS:
            case C::C14N_EXCLUSIVE_WITHOUT_COMMENTS:
                $this->getInclusiveNamespaces()?->toXML($e);
                break;
        }

        return $e;
    }
}
