<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_bottegaverde_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.bottegaverde.ru/";

     
    public function __construct($savePath) 
    {
        parent::__construct($savePath); 
        $this->httpClient->setIgnoreBadCodes();
    }
	
	public function loadItems () 
	{
        return null;
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<table cellspacing=0 cellpadding=0 border=0 width=867 height=29>(.+?)</table>#sui', $text, $text);
        preg_match_all('#<td onClick="document.location=\'/(.+?)\'"#sui', $text[1], $collections);
        foreach($collections[1] as $collection_id)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_id;
            $collection->url = $this->shopBaseUrl.$collection_id;

            $text = $this->httpClient->getUrlText($collection->url);
            if(mb_strlen($text) < 100)continue;
            preg_match('#<div id="bigredheader" style="color:\#6b9e0e">(.+?)</div>#sui', $text, $name);
            $collection->name = $name[1];

            
            

            preg_match('#<table cellspacing=0 cellpadding=0 border=0 style="margin-left:5">(.+?)</table>#sui', $text, $text);
            preg_match_all('#<a href="/(.+?)" class="innermenu" style="color:\#6b9e0e">(.+?)</a>#sui', $text[1], $categories, PREG_SET_ORDER);
            foreach($categories as $category)
            {
                $category_name = $category[2];
                $url = $this->shopBaseUrl.$category[1];

                $page = 1;
                while($page < 100)
                {
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]."?page=".$page);
                    preg_match("#<div class='paginator'>.+?<b>(\d+)</b>#sui", $text, $current);
                    if($current && $current[1] != $page)break;
                    if(!$current && $page > 1)break;

                    preg_match_all('#<tr><td colspan=3> <a href="/(.+?\?product=(\d+))" border=0>#sui', $text, $items, PREG_SET_ORDER);
                    foreach($items as $item_value)
                    {
                        $item = new ParserItem();

                        $item->url = $this->shopBaseUrl.$item_value[1];
                       // $item->url = "http://www.bottegaverde.ru/new/newface?product=757";
                        $item->id = $item_value[2];
                        if($category_name != "")$item->categ = array($category_name);

                        $text = $this->httpClient->getUrlText($item->url);

                        preg_match('#<i>код продукта: (.+?)</i><br><b>(.+?)</b>#sui', $text, $info);
                        $item->articul = $this->txt($info[1]);
                        $item->name = $this->txt($info[2]);

                        preg_match('#<span id="id_cur_price"> Цена(.+?)р.</span>#sui', $text, $price);
                        $item->price = $this->txt(str_replace(' ', '', $price[1]));

                        preg_match('#<tr><td valign="top"><img src="/img/info.jpg"></td>(.+?)</td>#sui', $text, $descr);
                        $item->descr = $this->txt($descr[1]);

                        preg_match('#<td width=262 valign=top><img src="/(.+?)"#sui', $text, $image);
                        $item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

                        //print_r($item);exit();
                        $collection->items[] = $item;
                    }
                    

                    
                    $page++;
                }

                
            }
    
            $base[] = $collection;
        }
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText("http://www.bottegaverde.ru/shop");
        if(mb_strlen($text) < 100)return null;
        preg_match('#<p class="noveltiesTop" style="MARGIN-BOTTOM: 10px">(.+?)</div>#sui', $text, $text);
        preg_match_all('#(.+?)</strong>(.+?)(?:<strong>|</p>)#sui', $text[1], $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = str_replace(':','',$this->txt($city[1]));
            if(mb_strpos($city_name, "Скоро открытие") !== false)continue;
            $text = $city[2];

            $info = explode("<br />", $text);
            foreach($info as $text)
            {
                $text = $this->txt($text);
                if(mb_strlen($text) < 5)continue;

                $shop = new ParserPhysical();

                $shop->city = $city_name;
                $shop->address = $text;

                preg_match('#^\d\.\s#sui', $shop->address, $id);
                if($id)$shop->address = str_replace($id[0],'',$shop->address);

                if(mb_strpos($shop->address, "м.")!==false)
                    $shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address, "м."));

                $shop->address = $this->address($shop->address);
                
                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
        return null;
    }
}
