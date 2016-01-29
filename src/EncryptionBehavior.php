<?php

namespace UWDOEM\Encryption;

use Propel\Generator\Model\Behavior;

class EncryptionBehavior extends Behavior
{

    protected $parameters = [
        'searchable' => false
    ];

    // Multiple encrypted columns in the same table is OK.
    public function allowMultiple()
    {
        return true;
    }

    public function tableMapFilter(&$script)
    {
        $table = $this->getTable();

        foreach ($this->getColumnNames() as $columnName) {
            $column = $table->getColumn($columnName);

            $columnIsVarbinary = $column->getType() === "VARBINARY";

            if ($columnIsVarbinary === false) {
                throw new \Exception("Encrypted columns must be of type VARBINARY. " .
                    "Encrypted column '{$column->getName()}' of type '{$column->getType()}' found. " .
                    "Revise your schema.");
            }
        }

        if (static::encryptedColumnsDeclarationExists($script) === false) {
            $insertLocation = strpos($script, ";", strpos($script, "const TABLE_NAME")) + 1;
            static::insertEncryptedColumnsDeclaration($script, $insertLocation);
            static::insertEncryptedColumnNameAccessMethods($script);
        }

        foreach ($this->getColumnRealNames() as $realColumnName) {
            static::insertEncryptedColumnName($script, $realColumnName);

            if ($this->isSearchable()) {
                static::insertSearchableEncryptedColumnName($script, $realColumnName);
            }
        }
    }

    public function objectFilter(&$script)
    {
        $phpColumnNames = $this->getColumnPhpNames();

        foreach ($phpColumnNames as $columnPhpName) {
            $this->addEncryptionToSetter($script, $columnPhpName);
            $this->addDecryptionToGetter($script, $columnPhpName);
        }
    }

    public function queryFilter(&$script)
    {
        if (strpos($script, "addUsingOperator") !== false) {
            return;
        }

        $useString = <<<'EOT'

    public function addUsingOperator($p1, $value = null, $operator = null, $preferColumnCondition = true)
    {
        /** @var StudentTableMap $tableMap */
        $tableMap = $this->getTableMap();

        /** @var boolean $isCriterion */
        $isCriterion = $p1 instanceof \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;

        /** @var string $columnName */
        $columnName = $isCriterion ? $p1->getTable()->getName() . $p1->getColumn() : $p1;

        /** @var boolean $isEncryptedColumn */
        $isEncryptedColumn = $tableMap->isEncryptedColumnName($columnName);

        /** @var boolean $isEncryptedSearchableColumn */
        $isEncryptedSearchableColumn =  $tableMap->isEncryptedSearchableColumnName($columnName);

        if ($isEncryptedColumn) {
            if (
                $isCriterion
                || !$isEncryptedSearchableColumn
                || ($operator !== null && $operator !== Criteria::EQUAL && $operator !== Criteria::NOT_EQUAL)
            ) {
                throw new \Exception("The column $columnName is encrypted, and does not support this form of query.");
            } else {
                $value = \UWDOEM\Encryption\Cipher::getInstance()->deterministicEncrypt((string)$value);
            }
        }

        return parent::addUsingOperator($p1, $value, $operator, $preferColumnCondition);
    }
EOT;
        $script = substr_replace(
            $script,
            $useString,
            strrpos($script, '}') - 1,
            0
        );
    }


    protected function getColumnNames()
    {
        $columnNames = [];
        foreach ($this->getParameters() as $key => $columnName) {
            if (strpos($key, "column_name") !== false && $columnName) {
                $columnNames[] = $columnName;
            }
        }
        return $columnNames;
    }

    protected function getColumnPhpNames()
    {
        $table = $this->getTable();

        return array_map(
            function ($columnName) use ($table) {
                return $table->getColumn($columnName)->getPhpName();
            },
            $this->getColumnNames()
        );
    }

    protected function getColumnRealNames()
    {
        $tableName = $this->getTable()->getName();

        return array_map(
            function ($columnName) use ($tableName) {
                return "$tableName.$columnName";
            },
            $this->getColumnNames()
        );
    }

    protected function isSearchable()
    {
        return $this->getParameter('searchable');
    }

    protected static function encryptedColumnsDeclarationExists($script)
    {
        return strpos($script, 'protected static $encryptedColumns') !== false;
    }

    protected static function insertEncryptedColumnNameAccessMethods(&$script)
    {
        $useString = <<<'EOT'

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

        $script = substr_replace(
            $script,
            $useString,
            strrpos($script, '}') - 1,
            0
        );

    }

    protected static function insertEncryptedColumnsDeclaration(&$script, $position)
    {

        $content = <<<'EOT'


    /**
     * Those columns encrypted by UWDOEM/Encryption
     */
    protected static $encryptedColumns = array(
        );

    /**
     * Those columns encrypted deterministically by UWDOEM/Encryption
     */
    protected static $encryptedSearchableColumns = array(
        );
EOT;

        $script = substr_replace($script, $content, $position, 0);
    }

    public static function insertEncryptedColumnName(&$script, $realColumnName)
    {
        $insertContent = "\n            '$realColumnName', ";

        $insertLocation = strpos($script, '$encryptedColumns = array(') + strlen('$encryptedColumns = array(');
        $script = substr_replace($script, $insertContent, $insertLocation, 0);
    }

    public static function insertSearchableEncryptedColumnName(&$script, $realColumnName)
    {
        $insertContent = "\n            '$realColumnName', ";

        $insertLocation = strpos(
            $script,
            '$encryptedSearchableColumns = array('
        ) + strlen('$encryptedSearchableColumns = array(');
                $script = substr_replace($script, $insertContent, $insertLocation, 0);
    }

    protected function addEncryptionToSetter(&$script, $columnPhpName)
    {
        $setterLocation = strpos($script, "set$columnPhpName");

        $start = strpos($script, "(", $setterLocation) + 1;
        $length = strpos($script, ")", $setterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $encryptionMethod = $this->isSearchable() ? "deterministicEncrypt" : "encrypt";
        $content = <<<EOT

        // Encrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        $variableName = \UWDOEM\Encryption\Cipher::getInstance()->$encryptionMethod($variableName);

EOT;

        $insertionStart = strpos($script, "{", $setterLocation) + 1;
        $script = substr_replace($script, $content, $insertionStart, 0);
    }

    protected function addDecryptionToGetter(&$script, $columnPhpName)
    {
        $getterLocation = strpos($script, "get$columnPhpName");

        $start = strpos($script, "return", $getterLocation) + 7;
        $length = strpos($script, ";", $getterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $insertionStart = strpos($script, "return", $getterLocation);
        $insertionLength = strpos($script, ";", $insertionStart) - $insertionStart + 1;


        $content = <<<EOT
// Decrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        \$fieldValue = $variableName;
        if (is_resource(\$fieldValue) && get_resource_type(\$fieldValue) === "stream") {
            \$fieldValue = \UWDOEM\Encryption\Cipher::getInstance()->decryptStream(\$fieldValue);
        }
        return \$fieldValue;
EOT;

        $script = substr_replace($script, $content, $insertionStart, $insertionLength);
    }
}
