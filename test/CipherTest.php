<?php

namespace Athens\Encryption\Test;

use PHPUnit_Framework_TestCase;

use Athens\Encryption\Cipher;

class CipherTest extends PHPUnit_Framework_TestCase
{

    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #called before initialization.*#
     */
    public function testGetInstanceBeforeCreate()
    {
        Cipher::getInstance();
    }

    public function testCipherCreation()
    {
        Cipher::createInstance("blaksjdfoiuwer");
    }

    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #Only one cipher instance may be created.*#
     */
    public function testCreateInstanceTwice()
    {
        Cipher::createInstance("blaksjdfoiuwer");
    }

    public function testEncrypt()
    {
        $plainText = "plaintext";

        $encryptedText1 = Cipher::getInstance()->encrypt($plainText);
        $encryptedText2 = Cipher::getInstance()->encrypt($plainText);

        // Assert that a given plain text will not encrypt to the same encrypted text every time
        $this->assertNotEquals($encryptedText1, $encryptedText2);
    }

    public function testEncryptDecrypt()
    {

        $plainText = "plaintext";
        $encryptedText = Cipher::getInstance()->encrypt($plainText);

        // Assert that the encrypted text is not the same as the plain text
        $this->assertNotEquals($plainText, $encryptedText);

        // Assert that the encrypted text is equal to the plain text when decrypted
        $this->assertEquals($plainText, Cipher::getInstance()->decrypt($encryptedText));
    }
}
