<?php

namespace UWDOEM\Encryption;

/**
 * Class Cipher
 *
 * Singleton class encapsulating encryption/decryption of data fields
 *
 * @package UWDOEM\Encryption
 */
class Cipher
{

    const IV_SIZE = 16;
    const ENCRYPTION_METHOD = "aes-256-cbc";

    protected static $instance;

    protected $passphrase;

    protected function __construct($passphrase)
    {
        $this->passphrase = $passphrase;
    }

    /**
     * Converts a plain-text string into an encrypted string
     *
     * @param string $string plain-text to encrypt
     * @return string the encrypted string
     */
    public function encrypt($string)
    {
        $iv = mcrypt_create_iv(self::IV_SIZE, MCRYPT_RAND);
        return $iv.openssl_encrypt($string, self::ENCRYPTION_METHOD, $this->passphrase, 0, $iv);
    }

    /**
     * Converts an encrypted string into a plain-text string
     *
     * @param string $encryptedMessage the encrypted string
     * @return string the plaint-text string
     */
    public function decrypt($encryptedMessage)
    {
        $iv = substr($encryptedMessage, 0, self::IV_SIZE);
        return openssl_decrypt(
            substr($encryptedMessage, self::IV_SIZE),
            self::ENCRYPTION_METHOD,
            $this->passphrase,
            0,
            $iv
        );

    }

    public function decryptStream($encryptedStream)
    {
        if (is_null($encryptedStream)) {
            return null;
        } else {
            return self::decrypt(stream_get_contents($encryptedStream, -1, 0));
        }
    }

    /**
     * @param string $passphrase The passphrase to be used to encrypt/decrypt data
     * @throws \Exception if you attempt to initialize the cipher more than one time
     *                    in a page-load via ::createInstance
     */
    public static function createInstance($passphrase)
    {
        if (!empty(self::$instance)) {
            throw new \Exception(
                'Cipher::createInstance() called more than once. ' .
                'Only one cipher instance may be created. '
            );
        }
        self::$instance = new static($passphrase);
    }

    /**
     * @return Cipher
     * @throws \Exception if ::getInstance is called before cipher is initialized via ::createInstance
     */
    public static function getInstance()
    {
        if (empty(self::$instance)) {
            throw new \Exception(
                'Cipher::getInstance() called before initialization. ' .
                'Call Cipher::createInstance($passphrase) before ::getInstance().'
            );
        }
        return self::$instance;
    }
}
