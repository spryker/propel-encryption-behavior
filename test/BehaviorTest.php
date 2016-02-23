<?php

namespace Athens\Encryption\Test;

use PHPUnit_Framework_TestCase;

use Athens\Encryption\Test\Mock\MockColumn;
use Athens\Encryption\Test\Mock\MockEncryptionBehavior;

class BehaviorTest extends PHPUnit_Framework_TestCase
{
    /** @var array */
    protected $columns;

    public function __construct()
    {
        $this->columns = [
            "VarBinaryColumn1" => new MockColumn("VarBinaryColumn1", "VARBINARY"),
            "VarBinaryColumn2" => new MockColumn("VarBinaryColumn2", "VARBINARY"),
            "NotVarBinaryColumn" => new MockColumn("NotVarBinaryColumn", "NOTVARBINARY")
        ];

        parent::__construct();
    }

    protected function normalizeWhitespace($string)
    {
        $string = trim($string);
        $string = str_replace("\r", "", $string);

        $string = join("\n", array_map("rtrim", explode("\n", $string)));

        return $string;
    }

    public function testObjectFilter()
    {
        $behavior = new MockEncryptionBehavior(
            $this->columns,
            [
                'column_name' => "VarBinaryColumn1",
                'searchable' => false
            ]
        );

        $behavior->objectFilter($this->objectFilterInput);

        $this->assertEquals(
            $this->normalizeWhitespace($this->objectFilterExpected),
            $this->normalizeWhitespace($this->objectFilterInput)
        );
    }

    public function testMapFilter()
    {
        $behavior = new MockEncryptionBehavior(
            $this->columns,
            [
                'column_name' => "VarBinaryColumn1",
                'searchable' => false
            ]
        );

        // Run table map filter once, and an encrypted columns declaration is created
        $behavior->tableMapFilter($this->mapFilterInput);
        $this->assertEquals(
            $this->normalizeWhitespace($this->mapFilterExpected),
            $this->normalizeWhitespace($this->mapFilterInput)
        );

        // Run it twice, and the new column name is inserted beside the old
        $behavior->tableMapFilter($this->mapFilterInput);
        $this->assertEquals(
            $this->normalizeWhitespace($this->mapFilterExpectedSecond),
            $this->normalizeWhitespace($this->mapFilterInput)
        );
    }

    /**
     * @expectedException              \Exception
     * @expectedExceptionMessageRegExp #Encrypted columns must be of type VARBINARY.*#
     */
    public function testBehaviorExceptionOnNonVarBinaryColumn()
    {
        $behavior = new MockEncryptionBehavior(
            $this->columns,
            [
                'column_name' => "NotVarBinaryColumn",
                'searchable' => false,
            ]
        );

        // Run table map filter once, and an encrypted columns declaration is created
        $input = "";
        $behavior->tableMapFilter($input);
    }

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

    protected $objectFilterExpected = <<<'EOT'
    public function getVarBinaryColumn1()
    {
        // Decrypt the variable, per \Athens\Encryption\EncryptionBehavior.
        $fieldValue = $this->test_column;
        if (is_resource($fieldValue) && get_resource_type($fieldValue) === "stream") {
            $fieldValue = \Athens\Encryption\Cipher::getInstance()->decryptStream($fieldValue);
        }
        return $fieldValue;
    }

    public function setVarBinaryColumn1($v)
    {
        // Encrypt the variable, per \Athens\Encryption\EncryptionBehavior.
        $v = \Athens\Encryption\Cipher::getInstance()->encrypt($v);

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

    protected $mapFilterExpected = <<<'EOT'
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;

    /**
     * Those columns encrypted by Athens/Encryption
     */
    protected static $encryptedColumns = array(
            'table_name.VarBinaryColumn1',
        );

    /**
     * Those columns encrypted deterministically by Athens/Encryption
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
     * @return boolean
     */
    public static function isEncryptedColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedColumns) !== false;
    }

    /**
     * @param $columnName
     * @return boolean
     */
    public static function isEncryptedSearchableColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedSearchableColumns) !== false;
    }

EOT;

    protected $mapFilterExpectedSecond = <<<'EOT'
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;

    /**
     * Those columns encrypted by Athens/Encryption
     */
    protected static $encryptedColumns = array(
            'table_name.VarBinaryColumn1',
            'table_name.VarBinaryColumn1',
        );

    /**
     * Those columns encrypted deterministically by Athens/Encryption
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
     * @return boolean
     */
    public static function isEncryptedColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedColumns) !== false;
    }

    /**
     * @param $columnName
     * @return boolean
     */
    public static function isEncryptedSearchableColumnName($columnName)
    {
        return array_search($columnName, static::$encryptedSearchableColumns) !== false;
    }

EOT;
}
