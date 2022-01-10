<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use Spryker\PropelEncryptionBehavior\Cipher;

class CipherTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetInstanceBeforeCreate(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('called before initialization.');

        Cipher::getInstance();
    }

    /**
     * @return void
     */
    public function testCipherCreation(): void
    {
        Cipher::createInstance('blaksjdfoiuwer');

        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testCreateInstanceTwice(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only one cipher instance may be created.');

        Cipher::createInstance('blaksjdfoiuwer');
    }

    /**
     * @return void
     */
    public function testEncrypt(): void
    {
        $plainText = 'plaintext';

        $encryptedText1 = Cipher::getInstance()->encrypt($plainText);
        $encryptedText2 = Cipher::getInstance()->encrypt($plainText);

        // Assert that a given plain text will not encrypt to the same encrypted text every time
        $this->assertNotEquals($encryptedText1, $encryptedText2);
    }

    /**
     * @return void
     */
    public function testEncryptDecrypt(): void
    {
        $plainText = 'plaintext';
        $encryptedText = Cipher::getInstance()->encrypt($plainText);

        // Assert that the encrypted text is not the same as the plain text
        $this->assertNotEquals($plainText, $encryptedText);

        // Assert that the encrypted text is equal to the plain text when decrypted
        $this->assertEquals($plainText, Cipher::getInstance()->decrypt($encryptedText));
    }

    /**
     * @return void
     */
    public function testResetInstance(): void
    {
        // Arrange
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('called before initialization.');

        // Act
        Cipher::resetInstance();
        Cipher::getInstance();
    }
}
