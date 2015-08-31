<?php

$objectFilterInput = <<<'EOT'
    public function getTestColumn()
    {
        return $this->test_column;
    }

    public function setTestColumn($v)
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
    } // setTestColumn()
EOT;

$objectFilterExpected = <<<'EOT'
    public function getTestColumn()
    {
        // Decrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        $this->test_column = \UWDOEM\Encryption\Cipher::getInstance()->decryptStream($this->test_column);
    }

    public function setTestColumn($v)
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
    } // setTestColumn()
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

$mapFilterExpected = <<<EOT
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'scholarshipApplication.Map.ApplicationTableMap';

    /**
     * Those columns encrypted by UWDOEM/Encryption
     */
    const ENCRYPTED_COLUMNS = 'TestColumn';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'scholarship_application';

EOT;

$mapFilterExpectedSecond = <<<EOT
class ApplicationTableMap extends TableMap
{
    use InstancePoolTrait;
    use TableMapTrait;

    /**
     * The (dot-path) name of this class
     */
    const CLASS_NAME = 'scholarshipApplication.Map.ApplicationTableMap';

    /**
     * Those columns encrypted by UWDOEM/Encryption
     */
    const ENCRYPTED_COLUMNS = 'TestColumn TestColumn';

    /**
     * The default database name for this class
     */
    const DATABASE_NAME = 'scholarship_application';

EOT;



class MockColumn {
    public function getPhpName() {
        return "TestColumn";
    }
}

class MockTable {
    public function getColumn($columnName) {
        if ($columnName == "test_column") {
            return new MockColumn();
        }
        return null;
    }
}

class MockEncryptionBehavior extends \UWDOEM\Encryption\EncryptionBehavior {
    protected $parameters = array(
        'column_name' => "test_column",
        'searchable' => "test_value_searchable",
        'sortable' => "test_value_sortable",
    );

    public function getTable() {
        return new MockTable();
    }

}


class BehaviorTest extends PHPUnit_Framework_TestCase {

    public function testObjectFilter() {
        global $objectFilterInput, $objectFilterExpected;

        $behavior = new MockEncryptionBehavior();

        $behavior->objectFilter($objectFilterInput);

        $this->assertEquals($objectFilterExpected, $objectFilterInput);
    }

    public function testMapFilter() {
        global $mapFilterInput, $mapFilterExpected, $mapFilterExpectedSecond;

        $behavior = new MockEncryptionBehavior();

        // Run table map filter once, and an encrypted columns declaration is created
        $behavior->tableMapFilter($mapFilterInput);
        $this->assertEquals(trim($mapFilterExpected), trim($mapFilterInput));

        // Run it twice, and the new column name is inserted beside the old
        $behavior->tableMapFilter($mapFilterInput);
        $this->assertEquals(trim($mapFilterExpectedSecond), trim($mapFilterInput));
    }

}
