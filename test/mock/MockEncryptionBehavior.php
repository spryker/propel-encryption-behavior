<?php

namespace UWDOEM\Encryption\Test\Mock;

use UWDOEM\Encryption\EncryptionBehavior;

class MockEncryptionBehavior extends EncryptionBehavior
{
    protected $table;
    protected $parameters;

    public function __construct($columns, $parameters)
    {
        $this->parameters = $parameters;
        $this->table = new MockTable($columns);
        parent::__construct();
    }

    public function getTable()
    {
        return $this->table;
    }
}
