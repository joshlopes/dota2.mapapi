<?php
namespace Dota2MapApi\Map;

use Dota2MapApi\Exception\ItemRequiredException;
use Dota2MapApi\Map\Item\ItemInterface;

class MapService
{
    /** @var  ItemInterface[] */
    protected $item;

    public function __construct($configs)
    {
        $this->configs = $configs;
    }

    public function getMapConfigurations()
    {
        return $this->configs['map'][$this->configs['settings']['map']];
    }

    public function addItem(ItemInterface $item)
    {
        $this->item[] = $item;
    }

    public function buildMap()
    {
        $webDir = __DIR__ . '/../../../web/';
        $map = $this->getMapConfigurations();
        if (empty($this->item)) {
            throw new ItemRequiredException();
        }

        $im = imagecreatefrompng($webDir . $map['src']);
        foreach ($this->item as $item) {
            $icon = imagecreatefrompng($webDir . $this->configs['heroes'][$item->getItemName()]['icon']);
            imagecopy($im, $icon, $item->getCordX(), $item->getCordY(), 0, 0, imagesx($icon), imagesy($icon));
        }

        // Output and free memory
        ob_start();
        imagepng($im);
        $image = ob_get_clean();
        imagedestroy($im);

        return $image;
    }

}
