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
        $aggregateColumn = $table->getColumn($this->getParameter('column_name'));
        $columnPhpName = $aggregateColumn->getPhpName();

        $encryptedColumnsDeclarationLocation = strpos($script, "ENCRYPTED_COLUMNS");

        // If there is not yet an encrypted column declared in this map...
        if ($encryptedColumnsDeclarationLocation === False) {

            // Insert after the CLASS_NAME declaration
            $insertLocation = strpos($script, ";", strpos($script, "const CLASS_NAME")) + 1;

            $insertContent = <<<EOT


    /**
     * Those columns encrypted by UWDOEM/Encryption
     */
    const ENCRYPTED_COLUMNS = '$columnPhpName';
EOT;

            $script = substr_replace($script, $insertContent, $insertLocation, 0);
        // If there is already an encrypted column declared in this map...
        } else {
            $insertLocation = strpos($script, "'", $encryptedColumnsDeclarationLocation) + 1;
            $script = substr_replace($script, "$columnPhpName ", $insertLocation, 0);
        }

    }

    public function objectFilter(&$script) {
        $table = $this->getTable();
        $aggregateColumn = $table->getColumn($this->getParameter('column_name'));
        $columnPhpName = $aggregateColumn->getPhpName();

        // Modify the setter to include encryption
        $setterLocation = strpos($script, "set$columnPhpName");

        $start = strpos($script, "(", $setterLocation) + 1;
        $length = strpos($script, ")", $setterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $insertionStart = strpos($script, "{", $setterLocation) + 1;
        $script = substr_replace($script, $this->encryptVariable($variableName), $insertionStart, 0);

        // Modify the getter to include decryption
        $getterLocation = strpos($script, "get$columnPhpName");

        $start = strpos($script, "return", $getterLocation) + 7;
        $length = strpos($script, ";", $getterLocation) - $start;
        $variableName = substr($script, $start, $length);

        $insertionStart = strpos($script, "return", $getterLocation);
        $insertionLength = strpos($script, ";", $insertionStart) - $insertionStart + 1;
        $script = substr_replace($script, $this->decryptVariable($variableName), $insertionStart, $insertionLength);
    }

    protected function encryptVariable($variableName) {
        return <<<EOT

        // Encrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        $variableName = \UWDOEM\Encryption\Cipher::getInstance()->encrypt($variableName);

EOT;
    }

    protected function decryptVariable($variableName) {
        return <<<EOT
// Decrypt the variable, per \UWDOEM\Encryption\EncryptionBehavior.
        $variableName = \UWDOEM\Encryption\Cipher::getInstance()->decryptStream($variableName);
EOT;
    }

}