<?php

declare(strict_types=1);

namespace SimpleSAML\XMLSecurity\XML\xenc;

/**
 * Class representing the <xenc:CipherReference> element.
 *
 * @author Tim van Dijen, <tvdijen@gmail.com>
 * @package simplesamlphp/saml2
 */
class CipherReference extends AbstractReference
{
    /**
     * CipherReference constructor.
     *
     * @param string $uri
     * @param \SimpleSAML\XML\Chunk[] $references
     */
    public function __construct(string $uri, array $references = [])
    {
        parent::__construct($uri, $references);
    }
}
