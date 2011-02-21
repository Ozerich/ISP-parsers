<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_rikki_tikki_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.shop.rikki-tikki.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }

    private function parse_item($url, $category_name)
    {
        $item = new ParserItem;

        $item->url = $url;
        $text = $this->httpClient->getUrlText($url);

        preg_match('#product_id=(\d+)#sui', $text, $id);
        $item->id = $id[1];

        preg_match('#<td colspan="2"><h1>(.+?)</h1>#sui', $text, $name);
        $item->name = $this->txt($name[1]);

        preg_match('#<strong>Цена: </strong>(.+?)</div>#sui', $text, $price_text);
        if($price_text)
        {
            $old_price = $new_price = '';
            preg_match('#<strike>(.+?)&nbsp;руб. </strike>#sui', $price_text[1], $old_price_);
            if($old_price_)
                $old_price = $this->txt($old_price_[1]);
            preg_match('#<span class="productPrice">(.+?)\.#sui', $price_text[1], $new_price_);
            if($new_price_)
                $new_price = str_replace('`','',$this->txt($new_price_[1]));
            if($old_price != '')
            {
                $item->price = $old_price;
                $item->discount = $this->discount($old_price, $new_price);
            }
            else if($new_price != '')
                $item->price = $new_price;
        }

        preg_match('#Артикул:(.+?)<br />#sui', $text, $articul);
        if($articul)$item->articul = $this->txt($articul[1]);

        preg_match('#Торговая марка:(.+?)<br />#sui', $text, $brand);
        if($brand)$item->brand = $this->txt($brand[1]);

        preg_match('#Состав:(.+?)<br />#sui', $text, $structure);
        if($structure)$item->structure = $this->txt($structure[1]);

        preg_match('#<select class="inputboxattrib" id="Цвет_field"(.+?)</select>#sui', $text, $color_text);
        if($color_text)
        {
            preg_match_all('#<option value="(.+?)">#sui', $color_text[1], $colors);
            foreach($colors[1] as $color)
                $item->colors[] = $this->txt($color);
        }

        preg_match('#<select class="inputboxattrib" id="Размер_field"(.+?)</select>#sui', $text, $size_text);
        if($size_text)
        {
            preg_match_all('#<option value="(.+?)">#sui', $size_text[1], $sizes);
            foreach($sizes[1] as $size)
                $item->sizes[] = $this->txt($size);
        }

        $item->categ = $category_name;

        preg_match('#Артикул:.+?<br />(.+?)<br /><br />#sui', $text, $descr);
        if($descr)$item->descr = $this->txt($descr[1]);

        preg_match('#<td valign="top" width="180" height="500">\s*<a href=\'(.+?)\'#sui', $text, $image);
        if($image)$item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

        

        return $item;
    }
   
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<li><a href="/(.+?)" class="mainlevel-top">(.+?)</a><ul >(.+?)</ul>#sui', $text, $collections,PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();


            $collection->url = $this->shopBaseUrl.$this->txt($collection_value[1]);
            $collection->name = $this->txt($collection_value[2]);
            $collection->id = mb_substr($collection->url, mb_strrpos($collection->url, '=') + 1);

            $text = $collection_value[3];

            preg_match_all('#<a href="/(.+?)" class="sublevel-top">(.+?)</a>#sui', $text, $categories, PREG_SET_ORDER);
            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$this->txt($category[1]));
                preg_match('#<select class="inputbox" name="limit".+?value="(.+?)=\d+"#sui',$text, $url);
                if($url)
                    $text = $this->httpClient->getUrlText($url[1]."=10000");
                preg_match_all('#<a style="font-size: 16px; font-weight: bold;" href="/(.+?)">.+?</a>#sui', $text, $items, PREG_SET_ORDER);
                foreach($items as $item)
                    $collection->items[] = $this->parse_item($this->shopBaseUrl.$item[1], $category_name);
            }


            $base[] = $collection;
        }


		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		return null;
	}
	
	public function loadNews ()
	{
		return null;
	}
}
