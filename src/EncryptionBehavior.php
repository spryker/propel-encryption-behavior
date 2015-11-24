<?php

namespace UWDOEM\Encryption;

use Propel\Generator\Model\Behavior;


class EncryptionBehavior extends Behavior {

    // Multiple encrypted columns in the same table is OK.
    public function allowMultiple() {
        return true;
    }

    protected $parameters = array(
        'column_name' => null,
        'searchable' => false,
        'sortable' => false,
    );

    public function tableMapFilter(&$script) {
        $phpColumnNames = $this->getEncryptedColumnPhpNames();
        $this->checkColumnTypes($phpColumnNames);

        foreach ($phpColumnNames as $columnPhpName) {

            $encryptedColumnsDeclarationLocation = strpos($script, "ENCRYPTED_COLUMNS");

            if ($encryptedColumnsDeclarationLocation === False) {
                // If there is not yet an encrypted column declared in this map...

                // Insert after the CLASS_NAME declaration
                $insertLocation = strpos($script, ";", strpos($script, "const CLASS_NAME")) + 1;
                $insertContent = $this->makeEncryptedColumnsDeclaration($columnPhpName);

            } else {
                // If there is already an encrypted column declared in this map...
                $insertLocation = strpos($script, "'", $encryptedColumnsDeclarationLocation) + 1;
                $insertContent = "$columnPhpName ";
            }
            $script = substr_replace($script, $insertContent, $insertLocation, 0);
        }

    }

    public function objectFilter(&$script) {
        $phpColumnNames = $this->getEncryptedColumnPhpNames();
        $this->checkColumnTypes($phpColumnNames);

        foreach ($phpColumnNames as $columnPhpName) {
            $this->modifySetterWithEncryption($script, $columnPhpName);
            $this->modifyGetterWithDecryption($script, $columnPhpName);
        }
    }

    protected function getEncryptedColumnPhpNames() {
        $table = $this->getTable();

        $encryptedColumnPhpNames = [];
        foreach ($this->getParameters() as $key => $columnName) {
            if (strpos($key, "column_name") !== false && $columnName) {
                $column = $table->getColumn($columnName);
                $encryptedColumnPhpNames[] = $column->getPhpName();
            }
        }
        return $encryptedColumnPhpNames;
    }

    protected function checkColumnTypes($phpColumnNames) {
        $table = $this->getTable();

        foreach ($phpColumnNames as $phpColumnName) {
            $column = $table->getColumnByPhpName($phpColumnName);

            if ($column->getType() !== "VARBINARY") {
                throw new \Exception("Encrypted columns must be of type VARBINARY. " .
                "Encrypted column '$phpColumnName' of type '{$column->getType()}' found. " .
                "Revise your schema.");
            }
        }

    }

    protected function makeEncryptedColumnsDeclaration($columnPhpName) {
        return <<<EOT


    /**
     * Those columns encrypted by UWDOEM/Encryption
     */
    const ENCRYPTED_COLUMNS = '$columnPhpName';
EOT;
    }

    protected function modifySetterWithEncryption(&$script, $columnPhpName) {
        $setterLocation = strpos($script, "set$columnPhpName");

        $start = strpos($script, "(", $setterLocation) + 1;
        $length = strpos($script, ")", $setterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $insertionStart = strpos($script, "{", $setterLocation) + 1;
        $script = substr_replace($script, $this->encryptVariable($variableName), $insertionStart, 0);
    }

    protected function encryptVariable($variableName) {
        return <<<EOT

        // Encrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        $variableName = \UWDOEM\Encryption\Cipher::getInstance()->encrypt($variableName);

EOT;
    }

    protected function modifyGetterWithDecryption(&$script, $columnPhpName) {
        $getterLocation = strpos($script, "get$columnPhpName");

        $start = strpos($script, "return", $getterLocation) + 7;
        $length = strpos($script, ";", $getterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $insertionStart = strpos($script, "return", $getterLocation);
        $insertionLength = strpos($script, ";", $insertionStart) - $insertionStart + 1;
        $script = substr_replace($script, $this->decryptVariable($variableName), $insertionStart, $insertionLength);
    }

    protected function decryptVariable($variableName) {
        return <<<EOT
// Decrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        \$fieldValue = $variableName;
        if (is_resource(\$fieldValue) && get_resource_type(\$fieldValue) === "stream") {
            \$fieldValue = \UWDOEM\Encryption\Cipher::getInstance()->decryptStream(\$fieldValue);
        }
        return \$fieldValue;
EOT;
    }

}