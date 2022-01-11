<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test;

use Exception;
use PHPUnit\Framework\TestCase;
use Spryker\PropelEncryptionBehavior\Test\Mock\MockColumn;
use Spryker\PropelEncryptionBehavior\Test\Mock\MockEncryptionBehavior;

class BehaviorTest extends TestCase
{
    /**
     * @var array
     */
    protected $columns;

    /**
     * @var string
     */
    protected $objectFilterInput = <<<'EOT'
    public function getVarBinaryColumn1()
    {
        return $this->test_column;
    }

    public function setVarBinaryColumn1($v)
    {
        // Because BLOB columns are streams in PDO we have to assume that they are
        // always modified when a new value is passed in.  For example, the contents
        // of the stream itself may have changed externally.
        if (!is_resource($v) && $v !== null) {
            $this->test_column = fopen('php://memory', 'r+');
            fwrite($this->test_column, $v);
            rewind($this->test_column);
        } else { // it's already a stream
            $this->test_column = $v;
        }
        $this->modifiedColumns[StudentTableMap::COL_TEST_COLUMN] = true;

        return $this;
    } // setVarBinaryColumn1()
EOT;

    /**
     * @var string
     */
    protected $objectFilterExpected = <<<'EOT'
    public function getVarBinaryColumn1()
    {
        // Decrypt the variable, per \Spryker\PropelEncryptionBehavior\EncryptionBehavior.
        $fieldValue = $this->test_column;
        if (is_resource($fieldValue) && get_resource_type($fieldValue) === "stream") {
            $fieldValue = \Spryker\PropelEncryptionBehavior\Cipher::getInstance()->decryptStream($fieldValue);
        }
        return $fieldValue;
    }

    public function setVarBinaryColumn1($v)
    {
        // Encrypt the variable, per \Spryker\PropelEncryptionBehavior\EncryptionBehavior.
        $v = \Spryker\PropelEncryptionBehavior\Cipher::getInstance()->encrypt($v);

        // Because BLOB columns are streams in PDO we have to assume that they are
        // always modified when a new value is passed in.  For example, the contents
        // of the stream itself may have changed externally.
        if (!is_resource($v) && $v !== null) {
            $this->test_column = fopen('php://memory', 'r+');
            fwrite($this->test_column, $v);
            rewind($this->test_column);
        } else { // it's already a stream
            $this->test_column = $v;
        }
        $this->modifiedColumns[StudentTableMap::COL_TEST_COLUMN] = true;

        return $this;
    } // setVarBinaryColumn1()
EOT;

    /**
     * @var string
     */
    protected $mapFilterInput = <<<EOT
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'scholarshipApplication.Map.ApplicationTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'scholarship_application';

EOT;

    /**
     * @var string
     */
    protected $mapFilterExpected = <<<'EOT'
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;

    /**
     * Those columns encrypted by Spryker/PropelEncryptionBehavior
     */
    protected static $encryptedColumns = array(
            'table_name.VarBinaryColumn1',
        );

    /**
     * Those columns encrypted deterministically by Spryker/PropelEncryptionBehavior
     */
    protected static $encryptedSearchableColumns = array(
        );
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'scholarshipApplication.Map.ApplicationTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'scholarship_application';
    /**
     * @param $columnName
     * @return bool
     */
    public static function isEncryptedColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedColumns) !== false;
    }

    /**
     * @param $columnName
     * @return bool
     */
    public static function isEncryptedSearchableColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedSearchableColumns) !== false;
    }

EOT;

    /**
     * @var string
     */
    protected $mapFilterExpectedSecond = <<<'EOT'
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;

    /**
     * Those columns encrypted by Spryker/PropelEncryptionBehavior
     */
    protected static $encryptedColumns = array(
            'table_name.VarBinaryColumn1',
            'table_name.VarBinaryColumn1',
        );

    /**
     * Those columns encrypted deterministically by Spryker/PropelEncryptionBehavior
     */
    protected static $encryptedSearchableColumns = array(
        );
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'scholarshipApplication.Map.ApplicationTableMap';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'scholarship_application';
    /**
     * @param $columnName
     * @return bool
     */
    public static function isEncryptedColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedColumns) !== false;
    }

    /**
     * @param $columnName
     * @return bool
     */
    public static function isEncryptedSearchableColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedSearchableColumns) !== false;
    }

EOT;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->columns = [
            'VarBinaryColumn1' => new MockColumn('VarBinaryColumn1', 'VARBINARY'),
            'VarBinaryColumn2' => new MockColumn('VarBinaryColumn2', 'VARBINARY'),
            'BlobColumn' => new MockColumn('BlobColumn', 'BLOB'),
            'LongVarBinaryColumn' => new MockColumn('BlobColumn', 'LONGVARBINARY'),
            'NotVarBinaryColumn' => new MockColumn('NotVarBinaryColumn', 'NOTVARBINARY'),
        ];

        parent::setUp();
    }

    /**
     * @return void
     */
    public function testObjectFilter(): void
    {
        // Arrange
        $behavior = new MockEncryptionBehavior(
            $this->columns,
            [
                'column_name' => 'VarBinaryColumn1',
                'searchable' => false,
            ],
        );

        // Act
        $behavior->objectFilter($this->objectFilterInput);

        // Assert
        $this->assertEquals(
            $this->normalizeWhitespace($this->objectFilterExpected),
            $this->normalizeWhitespace($this->objectFilterInput),
        );
    }

    /**
     * @return void
     */
    public function testMapFilter(): void
    {
        // Arrange
        $behavior = new MockEncryptionBehavior(
            $this->columns,
            [
                'column_name' => 'VarBinaryColumn1',
                'searchable' => false,
            ],
        );

        // Act
        // Run table map filter once, and an encrypted columns declaration is created
        $behavior->tableMapFilter($this->mapFilterInput);
        // Assert
        $this->assertEquals(
            $this->normalizeWhitespace($this->mapFilterExpected),
            $this->normalizeWhitespace($this->mapFilterInput),
        );

        // Act
        // Run it twice, and the new column name is inserted beside the old
        $behavior->tableMapFilter($this->mapFilterInput);
        // Assert
        $this->assertEquals(
            $this->normalizeWhitespace($this->mapFilterExpectedSecond),
            $this->normalizeWhitespace($this->mapFilterInput),
        );
    }

    /**
     * @return void
     */
    public function testBehaviorExceptionOnNonVarBinaryColumn(): void
    {
        // Arrange
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Encrypted columns must be of a binary type. Encrypted column \'NotVarBinaryColumn\' of type \'NOTVARBINARY\' found. Revise your schema.');

        $behavior = new MockEncryptionBehavior(
            $this->columns,
            [
                'column_name' => 'NotVarBinaryColumn',
                'searchable' => false,
            ],
        );

        // Act
        // Run table map filter once, and an encrypted columns declaration is created
        $input = '';
        $behavior->tableMapFilter($input);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function normalizeWhitespace(string $string): string
    {
        $string = trim($string);
        $string = str_replace("\r", '', $string);

        $string = implode("\n", array_map('rtrim', explode("\n", $string)));

        return $string;
    }
}
