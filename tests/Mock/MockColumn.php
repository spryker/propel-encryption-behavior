<?php
/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\PropelEncryptionBehavior\Test\Mock;

use Propel\Generator\Model\PropelTypes;

class MockColumn
{
    /**
     * @var string
     */
    protected $phpName;

    /**
     * @var string
     */
    protected $type;

    /**
     * @param string $phpName
     * @param string $type
     */
    public function __construct(string $phpName, string $type)
    {
        $this->phpName = $phpName;
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
        return $this->type;
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
