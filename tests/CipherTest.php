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
    public function tearDown(): void
    {
        Cipher::resetInstance();

        parent::tearDown();
    }

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
        // Act
        Cipher::createInstance('blaksjdfoiuwer');

        // Assert
        $this->assertTrue(true);
    }

    /**
     * @return void
     */
    public function testCreateInstanceTwice(): void
    {
        // Arrange
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Only one cipher instance may be created.');

        // Act
        Cipher::createInstance('sadfhgsdfsdsdf');
        Cipher::createInstance('blaksjdfoiuwer');
    }

    /**
     * @return void
     */
    public function testEncrypt(): void
    {
        // Arrange
        $plainText = 'plaintext';
        Cipher::createInstance('sadfhgsdfsdsdf');

        // Act
        $encryptedText1 = Cipher::getInstance()->encrypt($plainText);
        $encryptedText2 = Cipher::getInstance()->encrypt($plainText);

        // Assert
        $this->assertNotEquals($encryptedText1, $encryptedText2);
    }

    /**
     * @return void
     */
    public function testEncryptDecrypt(): void
    {
        // Arrange
        $plainText = 'plaintext';
        Cipher::createInstance('sadfhgsdfsdsdf');

        // Act
        $encryptedText = Cipher::getInstance()->encrypt($plainText);

        // Assert
        $this->assertNotEquals($plainText, $encryptedText);
        $this->assertEquals($plainText, Cipher::getInstance()->decrypt($encryptedText));
    }

    /**
     * @return void
     */
    public function testResetInstance(): void
    {
        // Arrange
        Cipher::createInstance('sadfhgsdfsdsdf');
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('called before initialization.');

        // Act
        Cipher::resetInstance();
        Cipher::getInstance();
    }
}
