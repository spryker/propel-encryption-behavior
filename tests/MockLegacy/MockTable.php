<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test\MockLegacy;

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
     * @param string $name
     * @param bool $caseInsensitive Whether the check is case insensitive.
     *
     * @return mixed
     */
    public function getColumn($name, $caseInsensitive = false)
    {
        return $this->columns[$name];
    }

    /**
     * @param string $phpName
     *
     * @return mixed
     */
    public function getColumnByPhpName($phpName)
    {
        return $this->columns[$phpName];
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
