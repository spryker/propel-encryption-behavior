<?php

namespace Athens\Encryption;

use Propel\Generator\Model\Behavior;

/**
 * Class EncryptionBehavior
 *
 * @package Athens\Encryption
 */
class EncryptionBehavior extends Behavior
{
    /**
     * @var array
     */
    protected $parameters = [
        'searchable' => false,
    ];

    /**
     * Multiple encrypted columns in the same table is OK.
     *
     * @return boolean
     */
    public function allowMultiple()
    {
        return true;
    }

    /**
     * @param string $script
     *
     * @return void
     *
     * @throws \Exception If the schema specifies encryption on fields which are not
     *                    VARBINARY.
     */
    public function tableMapFilter(&$script)
    {
        if (static::encryptedColumnsDeclarationExists($script) === false) {
            $insertLocation = strpos($script, ";", strpos($script, "const TABLE_NAME")) + 1;
            static::insertEncryptedColumnsDeclaration($script, $insertLocation);
            static::insertEncryptedColumnNameAccessMethods($script);
        }

        $table = $this->getTable();
        $columnNames = $this->getColumnNames();
        $searchableColumnNames = $this->getSearchableColumnNames();

        foreach ($columnNames as $columnName) {
            $column = $table->getColumn($columnName);

            if ($column->isLobType() === false) {
                throw new \Exception("Encrypted columns must be of a binary type. " .
                    "Encrypted column '{$column->getName()}' of type '{$column->getType()}' found. " .
                    "Revise your schema.");
            }

            $realColumnName = $this->createRealColumnName($columnName, $table->getName());
            static::insertEncryptedColumnName($script, $realColumnName);

            if ($this->isSearchable($columnName, $searchableColumnNames)) {
                static::insertSearchableEncryptedColumnName($script, $realColumnName);
            }
        }
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function objectFilter(&$script)
    {
        $columnNames = $this->getColumnNames();
        $searchableColumnNames = $this->getSearchableColumnNames();
        $table = $this->getTable();

        foreach ($columnNames as $columnName) {
            $isSearchable = $this->isSearchable($columnName, $searchableColumnNames);
            $columnPhpName = $table->getColumn($columnName)->getPhpName();

            $this->addEncryptionToSetter($script, $columnPhpName, $isSearchable);
            $this->addDecryptionToGetter($script, $columnPhpName);
        }
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function queryFilter(&$script)
    {
        if (strpos($script, "addUsingOperator") !== false) {
            return;
        }

        $useString = <<<'EOT'

    public function addUsingOperator($p1, $value = null, $operator = null, $preferColumnCondition = true)
    {
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
                $value = \Athens\Encryption\Cipher::getInstance()->deterministicEncrypt((string)$value);
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

    /**
     * @param string $script
     * @param string $realColumnName
     *
     * @return void
     */
    public static function insertEncryptedColumnName(&$script, $realColumnName)
    {
        $insertContent = "\n            '$realColumnName', ";

        $insertLocation = strpos($script, '$encryptedColumns = array(') + strlen('$encryptedColumns = array(');
        $script = substr_replace($script, $insertContent, $insertLocation, 0);
    }

    /**
     * @param string $script
     * @param string $realColumnName
     *
     * @return void
     */
    public static function insertSearchableEncryptedColumnName(&$script, $realColumnName)
    {
        $insertContent = "\n            '$realColumnName', ";

        $insertLocation = strpos($script, '$encryptedSearchableColumns = array(')
            + strlen('$encryptedSearchableColumns = array(');

        $script = substr_replace($script, $insertContent, $insertLocation, 0);
    }

    /**
     * @return string[]
     */
    protected function getColumnNames(): array
    {
        return $this->getParameterValuesByPrefix('column_name');
    }

    /**
     * @return string[]
     */
    protected function getSearchableColumnNames(): array
    {
        return $this->getParameterValuesByPrefix('column_name_searchable');
    }

    /**
     * @param string $prefix
     *
     * @return array
     */
    protected function getParameterValuesByPrefix(string $prefix): array
    {
        $parameterValues = [];
        foreach ($this->getParameters() as $parameterName => $parameterValue) {
            if (strpos($parameterName, $prefix) !== false && !empty($parameterValue)) {
                $parameterValues[] = $parameterValue;
            }
        }
        return $parameterValues;
    }

    /**
     * @param string $columnName
     * @param string $tableName
     *
     * @return string
     */
    protected function createRealColumnName(string $columnName, string $tableName): string
    {
        return sprintf('%s.%s', $tableName, $columnName);
    }

    /**
     * @param string $columnName
     * @param array $searchableColumnNames
     *
     * @return boolean
     */
    protected function isSearchable(string $columnName, array $searchableColumnNames = []): bool
    {
        $searchableParameter = $this->getParameter('searchable');

        if ($searchableParameter === true
            || strtolower($searchableParameter) === 'true'
            || array_search($columnName, $searchableColumnNames) !== false
        ) {
            return true;
        }

        return false;
    }

    /**
     * @param string $script
     *
     * @return boolean
     */
    protected static function encryptedColumnsDeclarationExists(string $script): bool
    {
        return strpos($script, 'protected static $encryptedColumns') !== false;
    }

    /**
     * @param string $script
     *
     * @return void
     */
    protected static function insertEncryptedColumnNameAccessMethods(string &$script): void
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

    /**
     * @param string  $script
     * @param integer $position
     *
     * @return void
     */
    protected static function insertEncryptedColumnsDeclaration(string &$script, int $position): void
    {
        $content = <<<'EOT'


    /**
     * Those columns encrypted by Athens/Encryption
     */
    protected static $encryptedColumns = array(
        );

    /**
     * Those columns encrypted deterministically by Athens/Encryption
     */
    protected static $encryptedSearchableColumns = array(
        );
EOT;

        $script = substr_replace($script, $content, $position, 0);
    }

    /**
     * @param string $script
     * @param string $columnPhpName
     * @param bool $isSearchable
     *
     * @return void
     */
    protected function addEncryptionToSetter(string &$script, string $columnPhpName, bool $isSearchable): void
    {
        $setterLocation = strpos($script, "set$columnPhpName");

        $start = strpos($script, "(", $setterLocation) + 1;
        $length = strpos($script, ")", $setterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $encryptionMethod = $isSearchable ? "deterministicEncrypt" : "encrypt";
        $content = <<<EOT

        // Encrypt the variable, per \Athens\Encryption\EncryptionBehavior.
        $variableName = \Athens\Encryption\Cipher::getInstance()->$encryptionMethod($variableName);

EOT;

        $insertionStart = strpos($script, "{", $setterLocation) + 1;
        $script = substr_replace($script, $content, $insertionStart, 0);
    }

    /**
     * @param string $script
     * @param string $columnPhpName
     *
     * @return void
     */
    protected function addDecryptionToGetter(string &$script, string $columnPhpName): void
    {
        $getterLocation = strpos($script, "get$columnPhpName");

        $start = strpos($script, "return", $getterLocation) + 7;
        $length = strpos($script, ";", $getterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $insertionStart = strpos($script, "return", $getterLocation);
        $insertionLength = strpos($script, ";", $insertionStart) - $insertionStart + 1;


        $content = <<<EOT
// Decrypt the variable, per \Athens\Encryption\EncryptionBehavior.
        \$fieldValue = $variableName;
        if (is_resource(\$fieldValue) && get_resource_type(\$fieldValue) === "stream") {
            \$fieldValue = \Athens\Encryption\Cipher::getInstance()->decryptStream(\$fieldValue);
        }
        return \$fieldValue;
EOT;

        $script = substr_replace($script, $content, $insertionStart, $insertionLength);
    }
}
