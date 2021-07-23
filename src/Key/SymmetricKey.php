<?php

namespace SimpleSAML\XMLSecurity\Key;

use SimpleSAML\XMLSecurity\Exception\RuntimeException;
use SimpleSAML\XMLSecurity\Utils\Random;

use function chr;
use function ord;
use function strlen;

/**
 * A class to model symmetric key secrets.
 *
 * New SymmetricKey objects can be created passing a given secret to the constructor, or using the static method
 * generate(), which will return a SymmetricKey object with a random secret material.
 *
 * Note that random secrets generated by this class will be cryptographically secure.
 *
 * @package SimpleSAML\XMLSecurity\Key
 */
class SymmetricKey extends AbstractKey
{
    /**
     * Generate a random, binary secret key of a given length.
     *
     * If the key is intended to be used with 3DES in CBC mode, pass true in $parityBits.
     *
     * @param int $length The length of the secret we want, in bytes.
     * @param bool $parityBits Whether the key should be suitable for its use in 3DES in CBC mode or not.
     *
     * @return \SimpleSAML\XMLSecurity\Key\SymmetricKey A cryptographically-secure random symmetric key.
     *
     * @throws \SimpleSAML\XMLSecurity\Exception\RuntimeException If no appropriate sources of cryptographically-secure
     *   random material are available.
     */
    public static function generate(int $length, bool $parityBits = false): SymmetricKey
    {
        $key = Random::generateRandomBytes($length);

        if ($parityBits) {
            /*
             * Make sure that the generated key has the proper parity bits set.
             * Mcrypt doesn't care about the parity bits, but others may care.
             */
            for ($i = 0; $i < strlen($key); $i++) {
                $byte = ord($key[$i]) & 0xfe;
                $parity = 1;
                for ($j = 1; $j < 8; $j++) {
                    $parity ^= ($byte >> $j) & 1;
                }
                $byte |= $parity;
                $key[$i] = chr($byte);
            }
        }

        return new self($key);
    }


    /**
     * Get the length of this symmetric key, in bytes.
     *
     * @return int The length of the key.
     */
    public function getLength(): int
    {
        return strlen($this->key_material);
    }
}
