<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_olgood_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.olgood.ru/";


    private function parse_item($url, $categ)
    {
        print_r($url);
        $text = $this->httpClient->getUrlText($url);

        $item = new ParserItem();

        $item->url = $url;

        $id = mb_substr($item->url, 0, -1);
        $item->id = mb_substr($id, mb_strrpos($id, "/") + 1);

        preg_match('#<h1 class="header">(.+?)</h1>#sui', $text, $name);
        $item->name = $this->txt($name[1]);


        preg_match('#<div class="description">(.+?)</div>#sui', $text, $descr);
        if($descr)$item->descr = $this->txt($descr[1]);

        preg_match('#<span class="price.+?"><span>(.+?)руб.</span>#sui', $text, $price);
        if($price)$item->price = str_replace(' ','',$price[1]);

        preg_match('#<td class="big_image">\s*<img src="/(.+?)"#sui', $text, $image);
        if($image && mb_strpos($image[1], "default_") == false)
            $item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

        if($categ != "")$item->categ = $categ;

        return $item;
    }
	
	public function loadItems () 
	{
        $base = array();

        $this->shopBaseUrl = "http://shop.ol-gud.ru/";
        $text = $this->httpClient->getUrlText($this->shopBaseUrl);

        preg_match_all('#<div><a href="/((\d+)/)" >(.+?)</a> </div>#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->id = $collection_value[2];
            $collection->name = $collection_value[3];

            $text = $this->httpClient->getUrlText($collection->url);
            preg_match('#<div class="level2">(.+?)</div>\s*</div>#sui', $text, $text);
            if($text)
                preg_match_all('#<div><a href="/(.+?)" >(.+?)</a> (?:</div>|$)#sui', $text[1], $categories, PREG_SET_ORDER);
            else
                $categories = array(array("1"=>$collection_value[1],"2"=>""));
            foreach($categories as $category)
            {
                $category_name = $category[2];
                $url = $this->shopBaseUrl.$category[1];
                while(true)
                {
                    $text = $this->httpClient->getUrlText($url);

                    preg_match_all('#<div class="product_block">\s*<div class="image"><div><a href="/(.+?)">#sui', $text, $items);
                    foreach($items[1] as $item)
                        $collection->items[] = $this->parse_item($this->shopBaseUrl.$item, $category_name);
                    preg_match('#<a class="next" href="/(.+?)">Вперед</a>#sui', $text, $url);
                    if(!$url)break;
                    $url = $this->shopBaseUrl.$url[1];
                }
            }
    
            $base[]= $collection;
        }

        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText("http://corp.ol-gud.ru/cgi-bin/show.pl?tpl=shop");
        preg_match('#<table width=100%>(.+?)ПОДМОСКОВЬЕ(.+?)</table>#sui', $text, $text);
        $moscow_text = $text[1];
        $other_text = $text[2];

        preg_match_all('#<tr><td colspan=4>(.+?)</td><td><a href="/(.+?)".+?</td><td>(.+?)</td><TD width=100>(.+?)</TD>\s*</tr>#sui', $moscow_text, $shops, PREG_SET_ORDER);
        for($i = 2; $i < count($shops); $i++)
        {
            $shop_value = $shops[$i];
            $shop = new ParserPhysical();

            $shop->city = "Москва";
            $shop->address = $this->txt($shop_value[3]);
            $shop->timetable = $this->txt($shop_value[4]);

            $text = $this->httpClient->getUrlText("http://corp.ol-gud.ru/".$shop_value[2]);
            preg_match('#<b>Режим работы:</b></span><br>(.+?)\s*<BR>\s*<BR>(.+?)<#sui', $text, $phone);
            if($phone)$shop->phone = $this->txt($phone[2]);

            $shop->address = $this->fix_address($shop->address);
            
            $base[] = $shop;
        }

        preg_match_all('#<tr><td colspan=4>(.+?)</td><td><a href="/(.+?)".+?>(.+?)</td><td>(.+?)</td><TD width=100>(.+?)</TD>\s*</tr>#sui', $other_text, $shops, PREG_SET_ORDER);
        for($i = 0; $i < count($shops); $i++)
        {
            $shop_value = $shops[$i];
            $shop = new ParserPhysical();

            $shop->city = str_replace("NEW!", "",$this->txt($shop_value[3]));
            if($shop->city == "РЕГИОНЫ")continue;
            $shop->address = $this->txt($shop_value[4]);
            $shop->timetable = $this->txt($shop_value[5]);

            $text = $this->httpClient->getUrlText("http://corp.ol-gud.ru/".$shop_value[2]);
            preg_match('#<b>Режим работы:</b></span><br>(.+?)\s*<BR>\s*<BR>(.+?)<#sui', $text, $phone);
            if($phone)$shop->phone = $this->txt($phone[2]);

            $shop->address = $this->fix_address($shop->address);
            
            $base[] = $shop;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = "http://podarok.ol-gud.ru/news.html";
        $text = $this->httpClient->getUrlBinary($url);
        preg_match_all('#<td class="contentheading" width="100%">\s*<h1><a href="/(news/(.+?).html)" class="contentpagetitle">(.+?)</a></h1>.+?<td valign="top" colspan="2" class="createdate">(.+?)</td>.+?<table class="contentpaneopen">.+?<td valign="top" colspan="2">(.+?)</td>#si', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = "http://podarok.ol-gud.ru/".$news_value[1];
            $news_item->id = $news_value[2];
            $news_item->header = $this->txt($news_value[3]);
            $news_item->date = $this->txt($news_value[4]);
            $news_item->date = mb_substr($news_item->date, 0, mb_strpos($news_item->date, " "));
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlBinary($news_item->urlFull);
            preg_match('#<div id="text">(.+?)</div>#si', $text, $descr);
            $news_item->contentFull = $this->txt($descr[1]);
        
            $base[] = $news_item;
        }

        preg_match('#<div id="news">(.+?)</div>#si', $text, $text);
        preg_match_all('#<td><a href="/(news/(.+?).html)">(.+?)</a><br>(.+?)</td>#si', $text[1], $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = "http://podarok.ol-gud.ru/".$news_value[1];
            $news_item->id = $news_value[2];
            $news_item->header = $this->txt($news_value[3]);
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlBinary($news_item->urlFull);
            preg_match('#<div id="text">(.+?)</div>#si', $text, $descr);
            $news_item->contentFull = $descr[1];
        
            $base[] = $news_item;           
        }

        $url = "http://corp.ol-gud.ru/cgi-bin/show.pl?tpl=akcii";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td></td><TD valign=top><h3>(.+?)</h3><p class=text style=font-size:14px;>(.+?)<div align=right><a href="(.+?)"#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = "http://corp.ol-gud.ru/".$news_value[3];
            preg_match('#page_id=(\d+)#sui', $news_item->urlFull, $id);
            $news_item->id = $id[1];
            $news_item->header = $this->txt($news_value[1]);
            $news_item->contentShort = $news_value[2];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<p class="text">(.+?)</p>#sui', $text, $descr);

            $news_item->contentFull = $descr[1];
        
            $base[] = $news_item; 
        }
            
		return $this->saveNewsResult($base);
	}
}
