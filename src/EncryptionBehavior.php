<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior;

use Exception;
use Propel\Generator\Model\Behavior;
use Propel\Generator\Model\PropelTypes;

class EncryptionBehavior extends Behavior
{
    /**
     * @var array<string, mixed>
     */
    protected $parameters = [
        'searchable' => false,
    ];

    /**
     * Multiple encrypted columns in the same table is OK.
     *
     * @return bool
     */
    public function allowMultiple(): bool
    {
        return true;
    }

    /**
     * @param string $script
     *
     * @throws \Exception If the schema specifies encryption on fields which are not BLOB/LOB.
     *
     * @return void
     */
    public function tableMapFilter(string &$script): void
    {
        if (static::encryptedColumnsDeclarationExists($script) === false) {
            $offset = strpos($script, 'const TABLE_NAME');
            $offset = $offset === false ? 0 : $offset;

            $insertLocation = strpos($script, ';', $offset) + 1;
            static::insertEncryptedColumnsDeclaration($script, $insertLocation);
            static::insertEncryptedColumnNameAccessMethods($script);
        }

        $table = $this->getTable();
        if ($table === null) {
            throw new Exception('No table existent.');
        }

        $columnNames = $this->getColumnNames();
        $searchableColumnNames = $this->getSearchableColumnNames();

        foreach ($columnNames as $columnName) {
            $column = $table->getColumn($columnName);

            if (!$column) {
                throw new Exception('Encrypted column with the "$columnName" name is not found. Revise your schema.');
            }

            if ($column->isLobType() === false) {
                throw new Exception(
                    'Encrypted columns must be of a binary type. ' .
                    "Encrypted column '{$column->getName()}' of type '{$column->getType()}' found. " .
                    'Revise your schema.',
                );
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
     * @throws \Exception If the column in not found in the columns list of table.
     *
     * @return void
     */
    public function objectFilter(string &$script): void
    {
        $columnNames = $this->getColumnNames();
        $searchableColumnNames = $this->getSearchableColumnNames();
        $table = $this->getTable();
        if ($table === null) {
            throw new Exception('No table existent.');
        }

        foreach ($columnNames as $columnName) {
            $isSearchable = $this->isSearchable($columnName, $searchableColumnNames);

            $column = $table->getColumn($columnName);

            if (!$column) {
                throw new Exception('The column with the "$columnName" name is not found. Revise your schema.');
            }

            $columnPhpName = $column->getPhpName();
            $isBlobTypeColumn = $column->getType() === PropelTypes::BLOB;

            $this->addEncryptionToSetter($script, $columnPhpName, $isSearchable, $isBlobTypeColumn);
            $this->addDecryptionToGetter($script, $columnPhpName, $isBlobTypeColumn);
        }
    }

    /**
     * @param string $script
     *
     * @return void
     */
    public function queryFilter(string &$script): void
    {
        if (strpos($script, 'addUsingOperator') !== false) {
            return;
        }

        $useString = <<<'EOT'

    public function addUsingOperator($p1, $value = null, $operator = null, $preferColumnCondition = true)
    {
        $tableMap = $this->getTableMap();

        /** @var bool $isCriterion */
        $isCriterion = $p1 instanceof \Propel\Runtime\ActiveQuery\Criterion\AbstractCriterion;

        /** @var string $columnName */
        $columnName = $isCriterion ? $p1->getTable()->getName() . $p1->getColumn() : $p1;

        /** @var bool $isEncryptedColumn */
        $isEncryptedColumn = $tableMap->isEncryptedColumnName($columnName);

        /** @var bool $isEncryptedSearchableColumn */
        $isEncryptedSearchableColumn =  $tableMap->isEncryptedSearchableColumnName($columnName);

        if ($isEncryptedColumn) {
            if (
                $isCriterion
                || !$isEncryptedSearchableColumn
                || ($operator !== null && $operator !== Criteria::EQUAL && $operator !== Criteria::NOT_EQUAL)
            ) {
                throw new \Exception("The column $columnName is encrypted, and does not support this form of query.");
            } else {
                $value = \Spryker\PropelEncryptionBehavior\Cipher::getInstance()->deterministicEncrypt((string)$value);
            }
        }

        return parent::addUsingOperator($p1, $value, $operator, $preferColumnCondition);
    }
EOT;
        $script = substr_replace(
            $script,
            $useString,
            strrpos($script, '}') - 1,
            0,
        );
    }

    /**
     * @param string $script
     * @param string $realColumnName
     *
     * @return void
     */
    public static function insertEncryptedColumnName(string &$script, string $realColumnName): void
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
    public static function insertSearchableEncryptedColumnName(string &$script, string $realColumnName): void
    {
        $insertContent = "\n            '$realColumnName', ";

        $insertLocation = strpos($script, '$encryptedSearchableColumns = array(')
            + strlen('$encryptedSearchableColumns = array(');

        $script = substr_replace($script, $insertContent, $insertLocation, 0);
    }

    /**
     * @return array<string>
     */
    protected function getColumnNames(): array
    {
        return $this->getParameterValuesByPrefix('column_name');
    }

    /**
     * @return array<string>
     */
    protected function getSearchableColumnNames(): array
    {
        return $this->getParameterValuesByPrefix('searchable_column_name');
    }

    /**
     * @param string $prefix
     *
     * @return array<mixed>
     */
    protected function getParameterValuesByPrefix(string $prefix): array
    {
        $parameterValues = [];
        foreach ($this->getParameters() as $parameterName => $parameterValue) {
            $prefixPosition = strpos($parameterName, $prefix);

            if ($prefixPosition !== false && $prefixPosition === 0 && $parameterValue) {
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
     * @param array<string> $searchableColumnNames
     *
     * @return bool
     */
    protected function isSearchable(string $columnName, array $searchableColumnNames = []): bool
    {
        $searchableParameter = $this->getParameter('searchable');

        if (
            $searchableParameter === true
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
     * @return bool
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

        $script = substr_replace(
            $script,
            $useString,
            strrpos($script, '}') - 1,
            0,
        );
    }

    /**
     * @param string $script
     * @param int $position
     *
     * @return void
     */
    protected static function insertEncryptedColumnsDeclaration(string &$script, int $position): void
    {
        $content = <<<'EOT'


    /**
     * Those columns encrypted by Spryker/PropelEncryptionBehavior
     */
    protected static $encryptedColumns = array(
        );

    /**
     * Those columns encrypted deterministically by Spryker/PropelEncryptionBehavior
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
     * @param bool $isBlobTypeColumn
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function addEncryptionToSetter(
        string &$script,
        string $columnPhpName,
        bool $isSearchable,
        bool $isBlobTypeColumn
    ): void {
        $setterLocation = $this->getMethodLocation($script, "set$columnPhpName");

        if ($isBlobTypeColumn) {
            $previousMethodBracketLocation = strrpos(substr($script, 0, $setterLocation), '}');

            if ($previousMethodBracketLocation === false) {
                throw new Exception('The bracket of the previous method was not found.');
            }

            $paramAnnotationLocation = (int)strpos($script, 'param resource', $previousMethodBracketLocation);
            $script = substr_replace($script, 'param string', $paramAnnotationLocation, 14);
        }

        $setterLocation = $this->getMethodLocation($script, "set$columnPhpName");
        $start = strpos($script, '(', $setterLocation) + 1;
        $length = strpos($script, ')', $setterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $encryptionMethod = $isSearchable ? 'deterministicEncrypt' : 'encrypt';
        $content = <<<EOT

        // Encrypt the variable, per \Spryker\PropelEncryptionBehavior\EncryptionBehavior.
        $variableName = \Spryker\PropelEncryptionBehavior\Cipher::getInstance()->$encryptionMethod($variableName);

EOT;

        $insertionStart = strpos($script, '{', $setterLocation) + 1;
        $script = substr_replace($script, $content, $insertionStart, 0);
    }

    /**
     * @param string $script
     * @param string $columnPhpName
     * @param bool $isBlobTypeColumn
     *
     * @throws \Exception
     *
     * @return void
     */
    protected function addDecryptionToGetter(
        string &$script,
        string $columnPhpName,
        bool $isBlobTypeColumn
    ): void {
        $getterLocation = $this->getMethodLocation($script, "get$columnPhpName");

        if ($isBlobTypeColumn) {
            $previousMethodBracketLocation = strrpos(substr($script, 0, $getterLocation), '}');

            if ($previousMethodBracketLocation === false) {
                throw new Exception('The bracket of the previous method was not found.');
            }

            $returnAnnotationLocation = (int)strpos($script, 'return resource', $previousMethodBracketLocation);
            $script = substr_replace($script, 'return string', $returnAnnotationLocation, 15);
        }

        $getterLocation = $this->getMethodLocation($script, "get$columnPhpName");

        $start = strpos($script, 'return', $getterLocation) + 7;
        $length = strpos($script, ';', $getterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $insertionStart = strpos($script, 'return', $getterLocation);

        if ($insertionStart === false) {
            throw new Exception(sprintf(
                'The return statement in `%s()` method was not found in the script.',
                "get$columnPhpName",
            ));
        }

        $insertionLength = strpos($script, ';', $insertionStart) - $insertionStart + 1;

        $content = <<<EOT
// Decrypt the variable, per \Spryker\PropelEncryptionBehavior\EncryptionBehavior.
        \$fieldValue = $variableName;
        if (is_resource(\$fieldValue) && get_resource_type(\$fieldValue) === "stream") {
            \$fieldValue = \Spryker\PropelEncryptionBehavior\Cipher::getInstance()->decryptStream(\$fieldValue);
        }
        return \$fieldValue;
EOT;

        $script = substr_replace($script, $content, $insertionStart, $insertionLength);
    }

    /**
     * @param string $script
     * @param string $methodName
     *
     * @throws \Exception
     *
     * @return int
     */
    protected function getMethodLocation(
        string $script,
        string $methodName
    ): int {
        $methodLocation = strpos($script, $methodName);

        if ($methodLocation === false) {
            throw new Exception(sprintf(
                'The `%s()` method was not found in the script.',
                $methodName,
            ));
        }

        return $methodLocation;
    }
}
