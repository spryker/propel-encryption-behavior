<?php

$input = <<<'EOT'
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

$expected = <<<'EOT'
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
    );

    public function getTable() {
        return new MockTable();
    }
}


class BehaviorTest extends PHPUnit_Framework_TestCase
{
    public function testBehavior() {
        global $input, $expected;

        $behavior = new MockEncryptionBehavior();

        $behavior->objectFilter($input);

        $this->assertEquals($expected, $input);
    }

}
