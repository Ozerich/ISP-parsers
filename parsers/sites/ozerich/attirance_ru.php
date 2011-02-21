<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_attirance_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.attirance.ru/";
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlBinary($this->shopBaseUrl."ru/produkcia/");
        preg_match('#<ul class="categories">(.+?)</div>#si', $text, $text);
        preg_match_all('#<li.*?><a href="/(ru/produkcia/(.+?)/)">(.+?)</a>(?:<ul>.+?</ul>)*#si', $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->id = $collection_value[2];
            $collection_item->url = $this->shopBaseUrl.$collection_value[1];
            $collection_item->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlBinary($collection_item->url);
            preg_match('#<ul class="categories">(.+?)</div>#si', $text, $text);
            preg_match('#<ul>(.+?)</ul>#sui', $text[1], $c_text);
            if($c_text)
                preg_match_all('#<li><a href="/(.+?)">(.+?)</a></li>#sui', $c_text[1], $categories,PREG_SET_ORDER);
            else
                $categories = array(array("1"=>$collection_value[1],"2"=>""));


            foreach($categories as $category_item)
            {
                $category_name = $category_item[2];
                $text = $this->httpClient->getUrlBinary($this->shopBaseUrl.$category_item[1]);

                preg_match('#<ul class="products-list">(.+?)</ul>#si', $text, $text);
                preg_match_all('#<a href="/(lv/produkta-info/\?id=(\d+)).+?class="thickbox" rel="nofollow">(.+?)</a>#si', $text[1], $items, PREG_SET_ORDER);

                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->url = $this->shopBaseUrl.$item_value[1];
                    $item->id = $item_value[2];
                    $item->name = $this->txt($item_value[3]);
                    if($category_name != "")$item->categ[] = $category_name;

                    $text = $this->httpClient->getUrlBinary($item->url);
                    preg_match('#<div id="layout">(.+?)</div>#sui',$text,$text);

                    preg_match('#<img src="/(.+?)"#sui', $text[1], $image);
                    $item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

                    preg_match('#</h3>(.+)#sui', $text[1], $descr);
                    $item->descr = $this->txt($descr[1]);
    
                    $collection_item->items[] = $item;
                }
            }
    
            $base[] = $collection_item;
        }
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlBinary($this->shopBaseUrl."ru/magazini/rosija/");
        preg_match_all('#<TABLE class=wbm_table style="WIDTH: 100%">(.+?)</TABLE>#si', $text, $text);
        $text = $text[1][2];

        preg_match_all('#<TD valign="top">(.+?)</TD>#si', $text, $items);
        $city = "Москва";
        foreach($items[1] as $text)
        {
            $text = trim($text);
            preg_match('#(<h2>(.+?)</h2>)*(.+)#si', $text, $info);
            if($info[2] != "")
                $city = $this->txt($info[2]);
            $text = $info[3];

            $shop = new ParserPhysical();

            $shop->city = trim(str_replace('г.','',$city));
                $info = explode("<br />", $text);
            if($city == "Москва" || count($info) == 2)
                $shop->address = $this->txt(trim($info[1]).", ".trim($info[0]));
            else
                $shop->address = $this->txt($info[2].", ".$info[0].", ".$info[1]);


            $base[] = $shop;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();
		
		return $this->saveNewsResult($base);
	}
}
