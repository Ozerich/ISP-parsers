<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_zoloto585_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.zoloto585.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."addresses/switch/city");
        preg_match('#<ul class="submenu">(.+?)</ul>#sui', $text, $text);
        preg_match_all('#<a href="/(.+?)"#sui', $text[1], $regions);

        foreach($regions[1] as $url)
        {
            $url = str_replace("map","list",$url);
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$url);
            preg_match('#<div class="city-box">(.+?)</div></div>#sui', $text, $text);
            if(!$text)continue;
            preg_match_all('#<li><a href="/(.+?)">(.+?)</a>#sui', $text[1], $cities, PREG_SET_ORDER);
            foreach($cities as $city)
            {
                $city_name = $city[2];
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
                preg_match('#<tbody>(.+?)</table>#sui', $text, $text);
                if($city_name != "Санкт-Петербург")
                    preg_match_all('#<tr><td>(.+?)</td><td>(.+?)</td><td>.+?</td></tr>#sui', $text[1], $shops, PREG_SET_ORDER);
                else
                    preg_match_all('#<tr><td>(.+?)</td><td>.+?</td><td>(.+?)</td><td>.+?</td></tr>#sui', $text[1], $shops, PREG_SET_ORDER);
                foreach($shops as $shop_value)
                {
                    $shop = new ParserPhysical();

                    $shop->city = $city_name;
                    $shop->address = $this->txt($shop_value[1]);
                    $shop->timetable = $this->txt($shop_value[2]);

                    $base[] = $shop;
                }

            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/";
        $text = $this->httpClient->getUrlText($url);
        preg_match('#<div class="news">(.+?)</div></div></div>#sui', $text, $text);
        preg_match_all('#<dl><dt>(.+?)</dt><dd><a href="/(news/id/(\d+))" class="header">(.+?)</a>(.+?)</dd>#sui', $text[1], $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[3];
            $news_item->date = $this->date_to_str($news_value[1]);
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="content-row">(.+?)</div></div>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }
            
		return $this->saveNewsResult($base);
	}
}
