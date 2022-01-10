<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test\Mock;

class MockTable
{
    /**
     * @var array<mixed>
     */
    protected $columns;

    /**
     * @param array<mixed> $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @param string $columnName
     *
     * @return mixed
     */
    public function getColumn(string $columnName)
    {
        return $this->columns[$columnName];
    }

    /**
     * @param string $columnName
     *
     * @return mixed
     */
    public function getColumnByPhpName(string $columnName)
    {
        return $this->columns[$columnName];
    }

    /**
     * @return array<mixed>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'table_name';
    }
}
