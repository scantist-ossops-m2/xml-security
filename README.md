# xml-security

[![Build Status](https://github.com/simplesamlphp/xml-security/workflows/CI/badge.svg?branch=master)](https://github.com/simplesamlphp/xml-security/actions)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/simplesamlphp/xml-security/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/simplesamlphp/xml-security/?branch=master)
[![Coverage Status](https://codecov.io/gh/simplesamlphp/xml-security/branch/master/graph/badge.svg)](https://codecov.io/gh/simplesamlphp/xml-security)
[![Type coverage](https://shepherd.dev/github/simplesamlphp/xml-security/coverage.svg)](https://shepherd.dev/github/simplesamlphp/xml-security)
[![Psalm Level](https://shepherd.dev/github/simplesamlphp/xml-security/level.svg)](https://shepherd.dev/github/simplesamlphp/xml-security)

This library implements XML signatures and encryption. It provides an extensible interface that
allows you to use your own signature and encryption implementations, and deals with everything
else to sign, verify, encrypt and decrypt your XML objects. It is built on top of the
[xml-common](https://github.com/simplesamlphp/xml-common) library, which provides you with a
standard API to create PHP objects from their XML representation, as well as producing XML from
your objects. The aim of the library is to provide a secure, yet flexible implementation of the
xmldsig and xmlenc standards in PHP.

The library provides two main ways to use it, one API for signed XML documents, and another for
encrypted ones. Additionally, the lower level APIs are available to implement those operations
yourself if needed, although we highly recommend using the main interfaces.

## Signature API

The XML signature API consists mainly of two interfaces:

- `SimpleSAML\XMLSecurity\XML\SignableElementInterface`
- `SimpleSAML\XMLSecurity\XML\SignedElementInterface`

In general, both should be used together. The former signals that an object can be signed (and
as such mandates the implementation of a `sign()` method in the object), while the latter indicates
that an object is already signed and allows the verification of its signature by means of 
`verify()` method.

Since the signature API is provided via PHP interfaces, your objects need to implement those
interfaces. For your convenience, each interface is accompanied by two traits with the actual
implementation for the PHP interfaces:

- `SimpleSAML\XMLSecurity\XML\SignableElementTrait`
- `SimpleSAML\XMLSecurity\XML\SignedElementTrait`

Both declare an abstract `getId()` method that you will have to implement, since only you know 
what attribute is declared in your XML objects to act as an `xml:id`.

The two interfaces mentioned extend from a third one, 
`SimpleSAML\XMLSecurity\XML\CanonicalizableElementInterface`. This interface ensures that your
XML objects can be properly canonicalized, so that if they were created from an actual XML 
document, it will be possible to restore that original XML document from your object. Again, a
`SimpleSAML\XMLSecurity\XML\CanonicalizableElementTrait` is provided for your convenience. This 
trait implements the canonicalization for you, and ensures that your object can be serialized and
later unserialized, but in exchange requires you to implement a `getOriginalXML()` method. This
means you will have to keep the original XML that created your object, if any.

In general, your code should implement both main interfaces and use the traits. The bare minimum
you will need to do to add XML signature capabilities to your objects will look like the following:

```php
namespace MyNamespace;

use DOMElement;
use SimpleSAML\XMLSecurity\XML\SignableElementInterface;
use SimpleSAML\XMLSecurity\XML\SignableElementTrait;
use SimpleSAML\XMLSecurity\XML\SignedElementInterface;
use SimpleSAML\XMLSecurity\XML\SignedElementTrait;

class MyObject implements SignableElementInterface, SignedElementInterface
{
    use SignableElementTrait;
    use SignedElementTrait;
    
    ...
    
    public function getId(): ?string
    {
        // return the ID of your object
    }
    
    
    protected function getOriginalXML(): DOMElement
    {
        // return the original XML, if any, or the XML generated by your object
    }
}
```

However, we strongly recommend your XML objects to build on top of the API provided by
[xml-common](https://github.com/simplesamlphp/xml-common). That way, you should probably have an
abstract class to declare your namespace and namespace prefix:

```php
namespace MyNamespace;

use SimpleSAML\XML\AbstractXMLElement;

abstract class AbstractMyNSElement extends AbstractXMLElement
{
    public const NS = 'my:namespace';
    
    public const NS_PREFIX = 'prefix';
}
```

Then your object can extend from that:

```php
namespace MyNamespace;

use DOMElement;
use SimpleSAML\XMLSecurity\XML\SignableElementInterface;
use SimpleSAML\XMLSecurity\XML\SignableElementTrait;
use SimpleSAML\XMLSecurity\XML\SignedElementInterface;
use SimpleSAML\XMLSecurity\XML\SignedElementTrait;

class MyObject extends AbstractMyNSElement implements SignableElementInterface, SignedElementInterface
{
    use SignableElementTrait;
    use SignedElementTrait;
    
    ...
    
    public function getId(): ?string
    {
        // return the ID of your object
    }
    
    
    protected function getOriginalXML(): DOMElement
    {
        // return the original XML, if any, or the XML generated by your object
    }
    
    
    public static function fromXML(DOMElement $xml): object
    {
        // build an instance of your object based on an XML document representing it
    }
    
    
    public function toXML(DOMElement $parent = null): DOMElement
    {
        // build an XML representation of your object
    }
}
```

Have a look at the `CustomSignable` class provided with the tests in this repository to get an 
idea of how a working implementation could look like.

When dealing with XML signatures, you typically need to provide two things: the signature algorithm 
you want to use and a key. Depending on the algorithm, one type of key or another would be suitable.
For that reason, this library introduces the concept of a `SignatureAlgorithm`, which is a given
instance of an algorithm with a key associated. `SignatureAlgorithm`s can be used then as _signers_
(when signing an object) and _verifiers_ (when used to verify a signature). This interface, together
with the ones provided for key material and signature backends, will allow you to sign and verify
signatures without much effort.

### Signing

If you want to sign an object representing an XML document, the `SignableElementTrait` provides 
you with a `doSign()` method that you can use for your convenience. This method takes the XML 
document you want to sign, and returns another document result of applying all signature 
transforms to the input. The _signer_ implementation to use will be obtained from the `$signer` 
property of the trait, which in turn will be set by the `sign()` method it provides as well. 
After the XML is signed successfully, `doSign()` will not only return the signed version of it, but
also populate the `$signature` property with a `Signature` object.

If you are using the API provided by [xml-common](https://github.com/simplesamlphp/xml-common), you
would typically implement support for signing your objects like this:

```php
    public function toXML(DOMElement $parent = null): DOMElement
    {
        if ($this->signer !== null) {
            $signedXML = $this->doSign($this->getMyXML());
            $signedXML->insertBefore($this->signature->toXML($signedXML), $signedXML->firstChild);
            return $signedXML;
        }

        return $this->getMyXML();
    }
```

Note that you will need to implement a mechanism to obtain the actual `DOMElement` to sign. It 
could be a method itself, as depicted in this example, or it could be stored in a class property.

At this point, your object is ready to be signed. You just need to create a _signer_, pass it to
`sign()`, and create the XML representation (which will do the actual signing) by calling `toXML()`:

```php
use SimpleSAML\XMLSecurity\Constants;
use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\PrivateKey;

$key = PrivateKey::fromFile('/path/to/key.pem');
$signer = (new SignatureAlgorithmFactory())->getAlgorithm(Constants::SIG_RSA_SHA256, $key);
$myObject->sign($signer);
$signedXML = $myObject->toXML();
```

That's it, you have signed your first object!

Now, you can customize your signatures as much as you want. For example, you can add the X509 
certificate corresponding your private key to it, and specify the canonicalization algorithm to use:

```php
use SimpleSAML\XMLSecurity\XML\ds\KeyInfo;
use SimpleSAML\XMLSecurity\XML\ds\X509Certificate;
use SimpleSAML\XMLSecurity\XML\ds\X509Data

...

$keyInfo = new KeyInfo(
    [
        new X509Data(
            [
                new X509Certificate($base64EncodedCertificateData)
            ]
        )
    ]
);
$customSignable->sign($signer, Constants::C14N_EXCLUSIVE_WITHOUT_COMMENTS, $keyInfo);

...
```

If you are planning on **embedding your signed object inside a larger XML document**, make sure to 
**give it an unique identifier**. Your object will need to generate an XML with an _ID_ attribute 
(of type `xml:id`) holding the identifier of the element, and the `getId()` method **must** return
that very same identifier.

### Verifying

In order to verify signed objects, the `SignedElementInterface` provides you with the following 
methods: 

- `getId()`: retrieves the unique identifier of the object.
- `getSignature()`: retrieves the signature of the object as a 
  `SimpleSAML\XMLSecurity\XML\ds\Signature` object.
- `getValidatingKey()`: retrieves the key the signature has been verified with.
- `isSigned()`: tells whether the object is in fact signed or not.
- `verify()`: verifies the signature of the object.

If your class has implemented support for signing its objects, and you are implementing the
`SignedElementInterface` and using the `SignedElementTrait`, support for verifying the signatures
comes out of the box.

The process for verifying a signature is similar to the one of creating one. You will need to 
instantiate a signature verifier with some key material and a signature algorithm, and use it to
verify the signature itself:

```php
use SimpleSAML\XMLSecurity\Constants;
use SimpleSAML\XMLSecurity\Alg\Signature\SignatureAlgorithmFactory;
use SimpleSAML\XMLSecurity\XML\ds\X509Certificate;

$verifier = (new SignatureAlgorithmFactory())->getAlgorithm(
    $myObject->getSignature()->getSignedInfo()->getSignatureMethod()->getAlgorithm(),
    new X509Certificate($pemEncodedCertificate) 
);
$verified = $myObject->verify($verifier);
```

> #### :warning: WARNING
>
> Note the `$verified` variable returned by `verify()`. The method does not return a `boolean` value
to tell you if the signature was verified or not. Instead, if it fails to verify, an exception will
be thrown. Its return value then is an object of the same class of your original object 
(`$myObject`), only that it is built based on the XML document whose signature has been verified.
**It is very important that you use only objects built based on a verified signature**. Otherwise,
any possible issue during the signature process could leave you with a tampered object whose 
signature doesn't really verify.

There is one alternative way to verify signatures. If the signature itself contains the key we can
use to verify it (namely, an X509 certificate), then we can call `verify()` without passing a 
verifier to it, and check that the key used to verify the signature matches the one we expect:

```php
use SimpleSAML\XMLSecurity\XML\ds\X509Certificate;

$trustedCertificate = new X509Certificate($pemEncodedCertificate);
$verified = $myObject->verify();

if ($verified->getValidatingKey() === $trustedCertificate) {
    // signature verified with a trusted certificate
}
```

This last usage pattern is more convenient since you don't have to create a _verifier_, although it
forces you to **remember that you need to check the key used to verify the signature**.

## Encryption API

The XML encryption API is similar to its signature counterpart, and also consists of two main
interfaces:

- `SimpleSAML\XMLSecurity\XML\EncryptableElementInterface`
- `SimpleSAML\XMLSecurity\XML\EncryptedElementInterface`

Just like in the signature API, the former signals that an object can be encrypted (and as such
requires the implementation of an `encrypt()` method), while the latter means an object is already
encrypted (and therefore requires a `decrypt()` method to be implemented). There is a substantial
difference with the signature API though: you need to implement two different classes, one for your
objects themselves, and another for your encrypted objects. The former will then implement 
`EncryptableElementInterface`, while the latter will be the one implementing 
`EncryptedElementInterface`.

Again, the library provides a couple of traits for your convenience, in order to minimise the amount
of code you have to write. Those traits are:

- `SimpleSAML\XMLSecurity\XML\EncryptableElementTrait`
- `SimpleSAML\XMLSecurity\XML\EncryptedElementTrait`

Both traits are somewhat asymmetrical, in the sense that while `EncryptableElementTrait` does
implement the `encrypt()` method, the `EncryptedElementTrait` does not implement its `decrypt()`
counterpart. This is because the way objects are encrypted may vary a lot, and the application 
itself will be the only one that knows exactly how that should be done. A basic default 
implementation that should cover most use cases is provided, though.

As with digital signatures, we provide classes that demonstrate the encryption functionality. You
may have a look at the `CustomSignable` class provided with the tests in order to see how 
encryption can be added to your objects, and the `EncryptedCustom` class will then demonstrate how
to deal with objects that are already encrypted.

### Decrypting objects

In XML encryption, when you have an encrypted object, you typically wrap that inside a specific
element that signals that the object represents an encrypted version of another object. You may 
have your own elements and logic in that encrypted object, but the absolute minimum would be an
`xenc:EncryptedData` element inside. This means you will have to create classes for your encrypted
objects, and they will have to implement the `EncryptedElementInterface`.

The simplest approach is then to take advantage of `EncryptedElementTrait` and again, we recommend
taking advantage of the XML object framework provided by `simplesamlphp/xml-common`. The only 
thing you will then have to implement is the `decrypt()` method, and a couple of getters required
by the trait:

```php

use SimpleSAML\XML\AbstractXMLElement;
use SimpleSAML\XML\XMLElementInterface;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmInterface;
use SimpleSAML\XMLSecurity\Backend\EncryptionBackend;
use SimpleSAML\XMLSecurity\XML\EncryptedElementInterface;

class MyEncryptedObject extends AbstractXMLElement implements EncryptedElementInterface
{
    use EncryptedElementTrait;
    
    
    public function getBlacklistedAlgorithms(): ?array
    {
        // return an array with the algorithms you don't want to allow to be used
    }
    
    
    public function getEncryptionBackend(): ?EncryptionBackend
    {
        // return the encryption backend you want to use, or null if you are fine with the default
    }
    
    
    public function decrypt(EncryptionAlgorithmInterface $decryptor): MyObject 
    {
        // implement the actual decryption here with help from the library
    }
}
```

Note that the value returned by `decrypt()` here is your own `MyObject` class. This means 
`MyObject` needs to extend `SimpleSAML\XML\XMLElementInterface`, but it is also one of the reasons
why the implementation of `decrypt()` is left to the application.

Now, the aim of this library is of course to make your life easier so that you don't actually have
to implement decryption yourself. The following implementation of `decrypt()` will be suitable 
for most use cases:

```php
    public function decrypt(EncryptionAlgorithmInterface $decryptor): MyObject
    {
        return MyObject::fromXML(
            \SimpleSAML\XML\DOMDocumentFactory::fromString(
                $this->decryptData($decryptor)
            )->documentElement
        );
    }
```

So what did just happen here? `MyObject` is supposed to implement `XMLElementInterface`, right? That
means it must implement a `fromXML()` static method that creates a new instance of the class based
on what's passed to it as a `DOMElement` object. The `DOMElement` itself was created with help from
the `DOMDocumentFactory` class, which in turn took the `string` result of calling the 
`decryptData()` method provided by the trait. And that's it, that might be all you need to decrypt
your encrypted objects!

Bear in mind though that this is the most basic use case. Your encrypted objects will need to 
look like this:

```xml
<MyEncryptedObject>
  <xenc:EncryptedData xmlns:xenc="http://www.w3.org/2001/04/xmlenc#">
    <xenc:EncryptionMethod Algorithm="..."/>
    <xenc:CipherData>
      <xenc:CipherValue>...</xenc:CipherValue>
    </xenc:CipherData>
  </xenc:EncryptedData>
</MyEncryptedObject>
```

If you need any more elements inside, attributes in the root element or anything else, you will have
to adjust the implementation for that. In that case, you may need a different constructor for your
encrypted objects than the one provided by the trait. You can define your own constructor while 
taking advantage of the one in the trait by renaming the latter:

```php

use SimpleSAML\XML\AbstractXMLElement;
use SimpleSAML\XMLSecurity\XML\EncryptedElementInterface;
use SimpleSAML\XMLSecurity\XML\xenc\EncryptedData;

class MyEncryptedObject extends AbstractXMLElement implements EncryptedElementInterface
{
    use EncryptedElementTrait {
        __construct as constructor;
    }
    
    
    public function __construct(EncryptedData $encryptedData, ...)
    {
        $this->constructor($encryptedData);
        
        ...
    }
}
```

Similarly, if your encryption scheme does not fit with any of the two supported by default, you 
will also need to implement it yourself. The two encryption schemes supported are:

- **Shared key encryption**: both parties share a secret key and use it to encrypt and decrypt the
  objects, respectively. This means the `<xenc:EncryptionMethod>` element will have a block 
  cipher specified in the `Algorithm` attribute. The `$decryptor` object passed to the `decrypt()`
  method will then be created for that block cipher in particular, and the key used will be a
  `SimpleSAML\XMLSecurity\Key\SymmetricKey` object with the shared secret as the key material.
- **Asymmetric encryption**: in this case, public key cryptography is used to encrypt the objects.
  However, public key cryptography is extremely costly in computational terms, so in a similar 
  fashion to digital signatures, what we do is to generate a random secret or _session key_, which
  will be used to encrypt the object itself with a block cipher, and in turn we will encrypt 
  that key with the recipient's public key.

  In this case, the `$decryptor` will implement a _key transport_ algorithm (which in turn is just
  an assymetric encryption algorithm like RSA), and the key attached to it will be a
  `SimpleSAML\XMLSecurity\Key\PrivateKey` object with the recipient's private key.

  When using asymmetric encryption, your encrypted XML objects will look similar to this:

  ```xml
  <MyEncryptedObject>
    <xenc:EncryptedData xmlns:xenc="http://www.w3.org/2001/04/xmlenc#">
      <xenc:EncryptionMethod Algorithm="BLOCK CIPHER ALGORITHM IDENTIFIER"/>
      <dsig:KeyInfo xmlns:dsig="http://www.w3.org/2000/09/xmldsig#">
        <xenc:EncryptedKey>
          <xenc:EncryptionMethod Algorithm="KEY TRANSPORT ALGORITHM IDENTIFIER"/>
          <xenc:CipherData>
            <xenc:CipherValue>...</xenc:CipherValue>
          </xenc:CipherData>
        </xenc:EncryptedKey>
      </dsig:KeyInfo>
      <xenc:CipherData>
        <xenc:CipherValue>...</xenc:CipherValue>
      </xenc:CipherData>
    </xenc:EncryptedData>
  </MyEncryptedObject> 
  ```
  
  The innermost `<xenc:CipherValue>` will contain the encrypted session key, while the outermost
  will contain the encrypted object itself.

The `SimpleSAML\XMLSecurity\XML\EncryptedElementTrait::decryptData()` method is capable of handling
both encryption schemes. If your application uses any of those, you can just use the method by
passing the appropriate _decryptor_ as explained earlier. If you are using _shared key encryption_,
you can then just do the following:

```php
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;

$decryptor = (new EncryptionAlgorithmFactory())->getAlgorithm(
    $myEncryptedObject->getEncryptedData()->getEncryptionMethod()->getAlgorithm(),
    new SymmetricKey('MY SHARED SECRET')
);
$myObject = $myEncryptedObject->decrypt($decryptor);
```

> #### :warning: WARNING
>
> Always make sure that the algorithm specified in the `<xenc:EncryptionMethod>` element is a block
> cipher algorithm. Only in that case the library will attempt to decrypt using the shared secret
> encryption scheme. The `SimpleSAML\XMLSecurity\Constants::$BLOCK_CIPHER_ALGORITHMS` associative
> array contains as keys all the identifiers of block ciphers supported by this library.

Alternatively, if your application uses asymmetric encryption, you will have to use an appropriate
decryptor instantiated with your private key in order to decrypt your objects:

```php
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\PrivateKey;

$decryptor = (new EncryptionAlgorithmFactory())->getAlgorithm(
    $myEncryptedObject->getEncryptedKey()->getEncryptionMethod()->getAlgorithm(),
    PrivateKey::fromFile('/path/to/private-key.pem')
);
$myObject = $myEncryptedObject->decrypt($decryptor);
```

One last note: you may have noticed the `getBlacklistedAlgorithms()` and `getEncryptionBackend()`
methods that you are required to implement when using `EncryptedElementTrait`. These methods are 
needed because of asymmetric encryption support. Since the library will have to create a block
cipher decryptor with the session key, the user does not control that decryptor and therefore won't
be able to specify directly neither the algorithms to forbid nor the encryption backend to use.
Hence the need of these two methods, which will allow the trait to modify any of those parameters
for the decryptor it will build. If you just want to use the default values, just implement them to
return `null`. However, if you want to customise the algorithms you accept and/or the backend to 
use, then you will have to return the desired values in those methods.

### Encrypting objects

If you want to support decrypting objects, it is likely that you also want to encrypt them in the
first place. Doing so is as simple as implementing the 
`SimpleSAML\XMLSecurity\XML\EncryptableElementInterface`:

```php
use SimpleSAML\XML\AbstractXMLElement;
use SimpleSAML\XMLSecurity\XML\EncryptableElementInterface;
use SimpleSAML\XMLSecurity\XML\EncryptableElementTrait;

class MyObject extends AbstractXMLElement implements EncryptableElementInterface
{
    use EncryptableElementTrait;


    public function getBlacklistedAlgorithms(): ?array
    {
        // return an array with the algorithms you don't want to allow to be used
    }
    
    
    public function getEncryptionBackend(): ?EncryptionBackend
    {
        // return the encryption backend you want to use, or null if you are fine with the default
    }
}
```

That's it. Easy, isn't it? In this case, the `encrypt()` method is provided directly by
`SimpleSAML\XMLSecurity\XML\EncryptableElementTrait`, since its return value will always be
a `SimpleSAML\XMLSecurity\XML\xenc\EncryptedData` object. Again, you have to implement a couple of
abstract methods required by the trait in order to tell it what algorithms are supported and what
backend it should use in case of asymmetric encryption.

Now, we just need to actually encrypt our objects. If our application uses shared key encryption,
we just need to create an appropriate encryptor with a symmetric key:

```php
use SimpleSAML\XMLSecurity\Constants;
use SimpleSAML\XMLSecurity\Alg\Encryption\EncryptionAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\SymmetricKey;

$encryptor = (new EncryptionAlgorithmFactory())->getAlgorithm(
    Constants::BLOCK_ENC_...,
    new SymmetricKey('MY SHARED SECRET')
);
$myEncryptedObject = $myObject->encrypt($encryptor)
```

If, on the contrary, we want to use an asymmetric encryption scheme, our encryptor will need to
implement a _key transport_ algorithm, and use a public key:

```php
use SimpleSAML\XMLSecurity\Constants;
use SimpleSAML\XMLSecurity\Alg\KeyTransport\KeyTransportAlgorithmFactory;
use SimpleSAML\XMLSecurity\Key\PublicKey;

$encryptor = (new KeyTransportAlgorithmFactory())->getAlgorithm(
    Constants::KEY_TRANSPORT_...,
    PublicKey::fromFile('/path/to/public-key.pem')
);
$myEncryptedObject = $myObject->encrypt($encryptor);
```

That will cover most needs. In general, **asymmetric encryption** will be preferred for most 
applications, as secret management is a difficult problem to tackle. If you need to implement a 
different encryption scheme than the two supported here, you will have to implement the `encrypt()`
method yourself.

## Extending the library

Not available yet.

## Keys for testing purposes
All encrypted keys use '1234' as passphrase.

The following keys are available:
  - signed      - A CA-signed certificate
  - other       - Another CA-signed certificate
  - selfsigned  - A self-signed certificate
  - broken      - A file with a broken PEM-structure (all spaces are removed from the headers)
  - corrupted   - This looks like a proper certificate (every first & last character of every line has been swapped)
  - expired     - This CA-signed certificate expires the moment it is generated
