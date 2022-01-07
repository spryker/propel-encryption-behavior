<?php

namespace Spryker\PropelEncryptionBehavior\Test\Mock;

use Spryker\PropelEncryptionBehavior\EncryptionBehavior;

class MockEncryptionBehavior extends EncryptionBehavior
{
    protected $table;
    protected $parameters;

    public function __construct($columns, $parameters)
    {
        $this->parameters = $parameters;
        $this->table = new MockTable($columns);
    }

    public function getTable()
    {
        return $this->table;
    }
}
