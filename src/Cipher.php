<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior;

use Exception;

class Cipher
{
    /**
     * @var int
     */
    public const INITIALIZATION_VECTOR_SIZE = 16;

    /**
     * @var string
     */
    public const ENCRYPTION_METHOD = 'aes-256-cbc';

    /**
     * @var \Spryker\PropelEncryptionBehavior\Cipher|null
     */
    protected static $instance;

    /**
     * @var string
     */
    protected $passphrase;

    /**
     * @param string $passphrase
     */
    protected function __construct(string $passphrase)
    {
        $this->passphrase = $passphrase;
    }

    /**
     * Converts a plain-text string into an encrypted string
     *
     * @param string|null $string Plain-text to encrypt.
     *
     * @throws \Exception
     *
     * @return string|null The encrypted string.
     */
    public function encrypt(?string $string): ?string
    {
        if ($string === null) {
            return $string;
        }

        if (static::INITIALIZATION_VECTOR_SIZE < 1) {
            throw new Exception('The length of random string should be bigger than 0.');
        }

        $iv = random_bytes(static::INITIALIZATION_VECTOR_SIZE);

        return $this->doEncrypt($string, $iv);
    }

    /**
     * Converts a plain-text string into an encrypted string, deterministically.
     *
     * This method will always return the same encrypted string for a given plaintext. This
     * deterministic encryption poses advantages and disadvantages over non-deterministic
     * encryption:
     *
     * The primary advantage is that it enables plaintext equality search; if a database column is
     * encrypted deterministically, then you can search for a given plaintext by encrypting
     * that plaintext and doing an equality search for the resulting cyphertext.
     *
     * The primary disadvantage is that it opens your data to a chosen-plaintext attack. See
     * the README for further guidance.
     *
     * This method is employed for encrypting Propel columns that are designated as 'searchable'
     * in the included EncryptionBehavior.
     *
     * @param string|null $string Plain-text to encrypt.
     *
     * @return string|null The encrypted string.
     */
    public function deterministicEncrypt(?string $string): ?string
    {
        if ($string === null) {
            return $string;
        }

        $iv = str_repeat('0', static::INITIALIZATION_VECTOR_SIZE);

        // prevent second encryption during ModelCriteria::findOneOrCreate()
        if (strpos($string, $iv) === 0) {
            return $string;
        }

        return $this->doEncrypt($string, $iv);
    }

    /**
     * @param string $string
     * @param string $iv
     *
     * @return string
     */
    protected function doEncrypt(string $string, string $iv): string
    {
        return $iv . openssl_encrypt($string, static::ENCRYPTION_METHOD, $this->passphrase, 0, $iv);
    }

    /**
     * Converts an encrypted string into a plain-text string
     *
     * @param string $encryptedMessage The encrypted string.
     *
     * @return string|bool The decrypted string on success or false on failure.
     */
    public function decrypt(string $encryptedMessage)
    {
        $iv = substr($encryptedMessage, 0, static::INITIALIZATION_VECTOR_SIZE);

        return openssl_decrypt(
            substr($encryptedMessage, static::INITIALIZATION_VECTOR_SIZE),
            static::ENCRYPTION_METHOD,
            $this->passphrase,
            0,
            $iv,
        );
    }

    /**
     * @param resource|null $encryptedStream
     *
     * @return string|null
     */
    public function decryptStream($encryptedStream): ?string
    {
        if ($encryptedStream === null) {
            return null;
        }

        $content = stream_get_contents($encryptedStream, -1, 0);

        if ($content === false) {
            return null;
        }

        $decryptedContent = $this->decrypt($content);

        return is_string($decryptedContent) ? $decryptedContent : null;
    }

    /**
     * @param string $passphrase The passphrase to be used to encrypt/decrypt data.
     *
     * @throws \Exception If you attempt to initialize the cipher more than one time
     *                    in a page-load via ::createInstance.
     *
     * @return void
     */
    public static function createInstance(string $passphrase): void
    {
        if (static::$instance !== null) {
            throw new Exception(
                'Cipher::createInstance() called more than once. ' .
                'Only one cipher instance may be created.',
            );
        }

        static::$instance = new static($passphrase);
    }

    /**
     * @throws \Exception if ::getInstance is called before cipher is initialized via ::createInstance.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (static::$instance === null) {
            throw new Exception(
                'Cipher::getInstance() called before initialization. ' .
                'Call Cipher::createInstance($passphrase) before ::getInstance().',
            );
        }

        return static::$instance;
    }

    /**
     * @return void
     */
    public static function resetInstance(): void
    {
        static::$instance = null;
    }
}
