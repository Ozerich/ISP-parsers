<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ardi_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ardi.ru/";

    private function parse_item($url)
    {
                $item = new ParserItem();

                $item->url = $url;
                $item->id = mb_substr($item->url, mb_strrpos($item->url, '=') + 1);

                $text = $this->httpClient->getUrlText($item->url);

                preg_match('#<td rowspan="2" colspan="2">.+?<img src="(.+?)"#sui', $text, $image);
                if($image)
                {
                    $image = $this->loadImage($this->shopBaseUrl.$image[1]);
                    if($image)
                        $item->images[] = $image;
                }

                preg_match("#<td class=descr valign=top>Цвета:</td><td width=100% valign=middle>(.+?)</td>#sui", $text, $colors);
                if($colors)
                {
                    $colors = explode(',',$colors[1]);
                    foreach($colors as $color)
                        if($this->txt($color) != "")
                            $item->colors[] = $color;
                }

                preg_match("#<td class=descr valign=top>Размеры:</td><td width=100% valign=middle>(.+?)</td>#sui", $text, $sizes);
                if($sizes)
                {
                    $sizes = explode(',',$sizes[1]);
                    foreach($sizes as $size)
                        if($this->txt($size) != "")
                            $item->sizes[] = $size;
                }

                preg_match('#<td class=descr>Состав:</td><td class=\'pre\'>(.+?)</td>#sui', $text, $structure);
                if($structure)
                    $item->structure = $this->txt($structure[1]);

                preg_match('#<div class=stext align=justify>(.+?)</div>#sui', $text, $descr);
                if($descr)
                    $item->descr = $this->txt($descr[1]);
        return $item;
    }

	public function loadItems () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all("#<td class=descr height=34><a href='\./(.+?)'>(.+?)</a></td>#sui",
            $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->name = $this->txt($collection_value[2]);
            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->id = mb_substr($collection->url, mb_strrpos($collection->url, '=') + 1);

            $page = 1;
            while($page < 100)
            {
                $text = $this->httpClient->getUrlText($collection->url."&page=".$page);
                preg_match_all("#<tr><td colspan=2 height='15' class=price>(.+?)</td></tr>.+?<a href=./(.+?)>#sui",
                               $text, $items, PREG_SET_ORDER);
                if(!$items)break;

                foreach($items as $item_value)
                {
                    $item = $this->parse_item($this->shopBaseUrl.$item_value[2]);
                    $item->name = $this->txt($item_value[1]);

                    $collection->items[] = $item;
                }

                $page++;
            }
            $base[] = $collection;
        }


		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."index.php?act=retail");
        preg_match('#ФИРМЕННЫЕ МАГАЗИНЫ.+?<td align=left><b>Телефон</td>(.+?)$#sui', $text, $text);
        preg_match_all('#<tr.+?><td></td><td align=left>(.*?)</td><td align=left>(.*?)</td><td></td><td align=left>(.*?)</td><td align=left nowrap>(.*?)</td><td align=right>(.*?)</td>#sui', $text[1], $shops, PREG_SET_ORDER);

        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $shop->city = $this->txt($shop_value[1]);
            $shop->address = $this->txt($shop_value[2].', '.$shop_value[3]);
            $shop->phone = $this->txt($shop_value[4]);

            $shop->address = $this->address($shop->address);

            $base[] = $shop;
        }

        return $this->savePhysicalResult($base);
    }
	
	public function loadNews ()
	{
        return null; 
	}
}
