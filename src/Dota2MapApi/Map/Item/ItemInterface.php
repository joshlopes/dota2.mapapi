<?php
namespace Dota2MapApi\Map\Item;


interface ItemInterface
{

    public function getType();
    public function getCordX();
    public function getCordY();
    public function getItemName();

}
