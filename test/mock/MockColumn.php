<?php
/**
 * Created by PhpStorm.
 * User: jschilz
 * Date: 1/29/2016
 * Time: 2:55 PM
 */

namespace Athens\Encryption\Test\Mock;

use Propel\Generator\Model\PropelTypes;

class MockColumn
{
    protected $phpName;
    protected $type;

    public function __construct($phpName, $type)
    {
        $this->phpName = $phpName;
        $this->type = $type;
    }

    public function getPhpName()
    {
        return $this->phpName;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getName()
    {
        return $this->phpName;
    }

    public function isLobType()
    {
        return PropelTypes::isLobType($this->getType());
    }
}
