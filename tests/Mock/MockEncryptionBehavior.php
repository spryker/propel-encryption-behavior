<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test\Mock;

use Propel\Generator\Model\Table;
use Spryker\PropelEncryptionBehavior\EncryptionBehavior;
use Spryker\PropelEncryptionBehavior\Test\MockLegacy\MockTable as LegacyMockTable;

class MockEncryptionBehavior extends EncryptionBehavior
{
    /**
     * @var \Spryker\PropelEncryptionBehavior\Test\Mock\MockTable
     */
    protected $table;

    /**
     * @var array<string, mixed>
     */
    protected $parameters;

    /**
     * @param array<mixed> $columns
     * @param array<mixed> $parameters
     */
    public function __construct(array $columns, array $parameters)
    {
        $this->parameters = $parameters;
        if ($this->isPreferLowest()) {
            $this->table = new LegacyMockTable('mock', $columns);
        } else {
            $this->table = new MockTable('mock', $columns);
        }
    }

    /**
     * @return \Propel\Generator\Model\Table|null
     */
    public function getTable(): ?Table
    {
        return $this->table;
    }

    /**
     * @return bool
     */
    protected function isPreferLowest(): bool
    {
        $content = file_get_contents(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'composer.lock');

        return strpos($content, '"version": "2.0.0-beta1",') !== false;
    }
}
