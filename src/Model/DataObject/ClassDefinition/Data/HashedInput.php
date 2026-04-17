<?php

declare(strict_types=1);

namespace Torq\PimcoreHelpersBundle\Model\DataObject\ClassDefinition\Data;

use Pimcore\Model\DataObject\ClassDefinition\Data\Input;
use Pimcore\Model\DataObject\Concrete;

/**
 * One-way hashed string field using Argon2id with an HMAC-derived deterministic salt.
 *
 * The server secret (`%secret%`) is used as the HMAC key, so the salt — and therefore
 * the hash — is reproducible without storing any salt material. This enables indexed
 * lookups and exact-match queries against sensitive identifying fields (e.g. SINs) without
 * ever storing or exposing the plaintext.
 *
 * Storage format: "ha2id:<64-char lowercase hex>" (~70 chars, fits varchar(190)).
 */
class HashedInput extends Input
{
    private const string HASH_PREFIX = 'ha2id:';
    private const string EDIT_PLACEHOLDER = '••••••••';

    public function getDataForResource(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        $data = $this->handleDefaultValue($data, $object, $params);

        if ($data === null || $data === '') {
            return null;
        }

        if (str_starts_with((string) $data, self::HASH_PREFIX)) {
            return $data;
        }

        return $this->computeHash((string) $data);
    }

    public function getDataForEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        return $data !== null ? self::EDIT_PLACEHOLDER : null;
    }

    public function getDataFromEditmode(mixed $data, ?Concrete $object = null, array $params = []): ?string
    {
        if (empty($data) || $data === self::EDIT_PLACEHOLDER) {
            if ($object !== null) {
                $getter = 'get' . ucfirst($this->getName());
                return method_exists($object, $getter) ? $object->$getter() : null;
            }
            return null;
        }

        return $data;
    }

    public function getDataForSearchIndex(mixed $object, array $params = []): string
    {
        return '';
    }

    public function getVersionPreview(mixed $data, ?Concrete $object = null, array $params = []): string
    {
        return $data !== null ? self::EDIT_PLACEHOLDER : '';
    }

    public function getFieldType(): string
    {
        return 'hashedInput';
    }

    private function computeHash(string $plaintext): string
    {
        // FIXME: Any alternatives to coupling field definition to container?
        $secret = \Pimcore::getContainer()->getParameter('secret');

        $salt = substr(hash_hmac('sha256', $plaintext, $secret, binary: true), 0, SODIUM_CRYPTO_PWHASH_SALTBYTES);

        $hash = sodium_crypto_pwhash(
            length: 32,
            password: $plaintext,
            salt: $salt,
            opslimit: SODIUM_CRYPTO_PWHASH_OPSLIMIT_MODERATE,
            memlimit: SODIUM_CRYPTO_PWHASH_MEMLIMIT_MODERATE,
            algo: SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13
        );

        return self::HASH_PREFIX . bin2hex($hash);
    }
}
