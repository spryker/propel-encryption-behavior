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
        $table = $this->getTable();

        foreach ($this->getEncryptedColumnNames() as $columnName) {
            $column = $table->getColumn($columnName);
            $columnPhpName = $column->getPhpName();

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
        $table = $this->getTable();

        foreach ($this->getEncryptedColumnNames() as $columnName) {
            $aggregateColumn = $table->getColumn($columnName);
            $columnPhpName = $aggregateColumn->getPhpName();

            $this->modifySetterWithEncryption($script, $columnPhpName);
            $this->modifyGetterWithDecryption($script, $columnPhpName);

        }
    }

    protected function getEncryptedColumnNames() {
        return [$this->getParameter('column_name')];
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
        $variableName = \UWDOEM\Encryption\Cipher::getInstance()->decryptStream($variableName);
EOT;
    }

}