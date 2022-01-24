<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test\Mock;

use Spryker\PropelEncryptionBehavior\EncryptionBehavior;

class MockEncryptionBehavior extends EncryptionBehavior
{
    /**
     * @var \Spryker\PropelEncryptionBehavior\Test\Mock\MockTable
     */
    protected $table;

    /**
     * @var array<mixed>
     */
    protected $parameters;

    /**
     * @param array<mixed> $columns
     * @param array<mixed> $parameters
     */
    public function __construct(array $columns, array $parameters)
    {
        $this->parameters = $parameters;
        $this->table = new MockTable($columns);
    }

    /**
     * @return \Propel\Generator\Model\Table|\Spryker\PropelEncryptionBehavior\Test\Mock\MockTable
     */
    public function getTable()
    {
        return $this->table;
    }
}
