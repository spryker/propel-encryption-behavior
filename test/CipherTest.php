<?php

namespace Athens\Encryption\Test;

use PHPUnit\Framework\TestCase;
use Athens\Encryption\Cipher;

class CipherTest extends TestCase
{
    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testGetInstanceBeforeCreate():void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('called before initialization.');

        Cipher::getInstance();
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testCipherCreation(): void
    {
        Cipher::createInstance("blaksjdfoiuwer");

        $this->assertTrue(true);
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testCreateInstanceTwice(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only one cipher instance may be created.');

        Cipher::createInstance("blaksjdfoiuwer");
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testEncrypt(): void
    {
        $plainText = "plaintext";

        $encryptedText1 = Cipher::getInstance()->encrypt($plainText);
        $encryptedText2 = Cipher::getInstance()->encrypt($plainText);

        // Assert that a given plain text will not encrypt to the same encrypted text every time
        $this->assertNotEquals($encryptedText1, $encryptedText2);
    }

    /**
     * @throws \Exception
     *
     * @return void
     */
    public function testEncryptDecrypt(): void
    {

        $plainText = "plaintext";
        $encryptedText = Cipher::getInstance()->encrypt($plainText);

        // Assert that the encrypted text is not the same as the plain text
        $this->assertNotEquals($plainText, $encryptedText);

        // Assert that the encrypted text is equal to the plain text when decrypted
        $this->assertEquals($plainText, Cipher::getInstance()->decrypt($encryptedText));
    }
}
