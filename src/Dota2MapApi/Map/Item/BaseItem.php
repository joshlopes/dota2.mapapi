<?php
namespace Dota2MapApi\Map\Item;

abstract class BaseItem implements ItemInterface
{

    protected $cord;
    protected $itemName;

    public function __construct($itemName, array $cord)
    {
        $this->itemName = $itemName;
        $this->cord = $cord;
    }

    public function getCordX()
    {
        return $this->cord['x'];
    }

    public function getCordY()
    {
        return $this->cord['y'];
    }

    public function getItemName()
    {
        return $this->itemName;
    }
}
