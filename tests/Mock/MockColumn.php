<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test\Mock;

use Propel\Generator\Model\Column;
use Propel\Generator\Model\PropelTypes;

class MockColumn extends Column
{
    /**
     * @var string
     */
    protected $phpName;

    /**
     * @var string|null
     */
    protected $type;

    /**
     * @param string $name The column's name
     * @param string|null $type The column's type
     * @param string|int|null $size The column's size
     */
    public function __construct(string $name, ?string $type, $size = null)
    {
        parent::__construct($name, $type, $size);

        $this->phpName = $name;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getPhpName(): string
    {
        return $this->phpName;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return (string)$this->type;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->phpName;
    }

    /**
     * @return bool
     */
    public function isLobType(): bool
    {
        return PropelTypes::isLobType($this->getType());
    }
}
