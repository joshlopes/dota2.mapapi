<?php
namespace Dota2MapApi\Map\Item;

use Dota2MapApi\Map\Enum\ItemTypeEnum;

class HeroItem extends BaseItem implements ItemInterface
{

    public function getType()
    {
        return ItemTypeEnum::HERO;
    }
}
