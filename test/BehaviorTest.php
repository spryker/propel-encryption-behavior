<?php

$objectFilterInput = <<<'EOT'
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

$objectFilterExpected = <<<'EOT'
    public function getVarBinaryColumn1()
    {
        // Decrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        $fieldValue = $this->test_column;
        if (is_resource($fieldValue) && get_resource_type($fieldValue) === "stream") {
            $fieldValue = \UWDOEM\Encryption\Cipher::getInstance()->decryptStream($fieldValue);
        }
        return $fieldValue;
    }

    public function setVarBinaryColumn1($v)
    {
        // Encrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        $v = \UWDOEM\Encryption\Cipher::getInstance()->encrypt($v);

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

$mapFilterInput = <<<EOT
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

$mapFilterExpected = <<<'EOT'
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;

    /**
     * Those columns encrypted by UWDOEM/Encryption
     */
    protected static $encryptedColumns = array(
            'table_name.VarBinaryColumn1',
        );

    /**
     * Those columns encrypted deterministically by UWDOEM/Encryption
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

$mapFilterExpectedSecond = <<<'EOT'
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;

    /**
     * Those columns encrypted by UWDOEM/Encryption
     */
    protected static $encryptedColumns = array(
            'table_name.VarBinaryColumn1',
            'table_name.VarBinaryColumn1',
        );

    /**
     * Those columns encrypted deterministically by UWDOEM/Encryption
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


class MockColumn
{

    protected $_phpName;
    protected $_type;

    public function __construct($phpName, $type)
    {
        $this->_phpName = $phpName;
        $this->_type = $type;
    }

    public function getPhpName()
    {
        return $this->_phpName;
    }
    
    public function getType()
    {
        return $this->_type;
    }

    public function getName()
    {
        return $this->_phpName;
    }
}

$columns = [
    "VarBinaryColumn1" => new MockColumn("VarBinaryColumn1", "VARBINARY"),
    "VarBinaryColumn2" => new MockColumn("VarBinaryColumn2", "VARBINARY"),
    "NotVarBinaryColumn" => new MockColumn("NotVarBinaryColumn", "NOTVARBINARY")
];

class MockTable
{
    public function getColumn($columnName)
    {
        global $columns;
        return $columns[$columnName];
    }

    public function getColumnByPhpName($columnName)
    {
        global $columns;
        return $columns[$columnName];
    }

    public function getColumns()
    {
        global $columns;
        return $columns;
    }

    public function getName()
    {
        return "table_name";
    }
}

class MockEncryptionBehavior extends \UWDOEM\Encryption\EncryptionBehavior
{
    protected $parameters = array(
        'column_name' => "VarBinaryColumn1",
        'searchable' => false,
    );

    public function getTable()
    {
        return new MockTable();
    }
}

class BadMockEncryptionBehavior extends \UWDOEM\Encryption\EncryptionBehavior
{
    protected $parameters = array(
        'column_name' => "NotVarBinaryColumn",
        'searchable' => "searchable",
    );

    public function getTable()
    {
        return new MockTable();
    }
}


class BehaviorTest extends PHPUnit_Framework_TestCase
{

    protected function normalizeWhitespace($string)
    {
        $string = trim($string);
        $string = str_replace("\r", "", $string);

        $string = join("\n", array_map("rtrim", explode("\n", $string)));

        return $string;
    }

    public function testObjectFilter()
    {
        global $objectFilterInput, $objectFilterExpected;

        $behavior = new MockEncryptionBehavior();

        $behavior->objectFilter($objectFilterInput);

        $this->assertEquals(
            $this->normalizeWhitespace($objectFilterExpected),
            $this->normalizeWhitespace($objectFilterInput)
        );
    }

    public function testMapFilter()
    {
        global $mapFilterInput, $mapFilterExpected, $mapFilterExpectedSecond;

        $behavior = new MockEncryptionBehavior();

        // Run table map filter once, and an encrypted columns declaration is created
        $behavior->tableMapFilter($mapFilterInput);
        $this->assertEquals(
            $this->normalizeWhitespace($mapFilterExpected),
            $this->normalizeWhitespace($mapFilterInput)
        );

        // Run it twice, and the new column name is inserted beside the old
        $behavior->tableMapFilter($mapFilterInput);
        $this->assertEquals(
            $this->normalizeWhitespace($mapFilterExpectedSecond),
            $this->normalizeWhitespace($mapFilterInput)
        );
    }

    /**
     * @expectedException              Exception
     * @expectedExceptionMessageRegExp #Encrypted columns must be of type VARBINARY.*#
     */
    public function testBehaviorExceptionOnNonVarBinaryColumn()
    {
        $behavior = new BadMockEncryptionBehavior();

        // Run table map filter once, and an encrypted columns declaration is created
        $input = "";
        $behavior->tableMapFilter($input);
    }
}
