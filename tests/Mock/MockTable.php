<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test\Mock;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\Table;

class MockTable extends Table
{
    /**
     * @var array<string, mixed>
     */
    protected $columns;

    /**
     * @param string $name
     * @param array<string, mixed> $columns
     */
    public function __construct(string $name, array $columns)
    {
        parent::__construct($name);

        $this->columns = $columns;
    }

    /**
     * @param string|null $name
     * @param bool $caseInsensitive
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getColumn(?string $name, bool $caseInsensitive = false): ?Column
    {
        return $this->columns[$name];
    }

    /**
     * @param string $phpName
     *
     * @return \Propel\Generator\Model\Column|null
     */
    public function getColumnByPhpName(string $phpName): ?Column
    {
        return $this->columns[$phpName];
    }

    /**
     * @return array<string, mixed>
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
