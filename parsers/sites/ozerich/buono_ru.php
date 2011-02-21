<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_buono_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.buono.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."Article_291.html");
        preg_match_all('#<li><a href="/(Article_(\d+).html)">(.+?)</a>#sui', $text, $cities, PREG_SET_ORDER);

        foreach($cities as $city)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
            preg_match('#<div id="content">.+?<h1>.+?</h1>(?:.+?<b>(.+?)</b><br>\s*)*(.+?)</div>#sui', $text, $text);

            $shop = new ParserPhysical();

            $shop->id = $city[2];
            $shop->city = $city[3];
            $shop->address = trim(str_replace($shop->city, '',$this->address($text[2])));

            preg_match('#(?:Тел:|тел.|телефон:)([\s|\d|\)\(|\+|\-]+)#sui', $shop->address, $phone);
            if($phone)
            {
                $shop->phone = trim($phone[1]);
                $shop->address = str_replace($phone[0], '', $shop->address);
            }

            preg_match('#г\. (.+?)\s#sui', $shop->address, $city_name);
            if($city_name)
            {
                $shop->city = $city_name[1];
                $shop->address = str_replace($city_name[0], '', $shop->address);
            }

            preg_match('#Проезд:.+#sui', $shop->address, $trip);
            if($trip)$shop->address = str_replace($trip[0], '', $shop->address);

            $shop->address = str_replace('адрес: ','',trim($shop->address).", ".$text[1]);

            $shop->address = $this->txt($shop->address);

            if($this->address_have_prefix($shop->address))
            {
                $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ","));
                $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ",") + 2)." ".$name;
            }

            
            $base[] = $shop;
            
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);

        preg_match('#<div class="next"><a href="/(.+?)">#sui', $text, $news);
        $text = $this->httpClient->getUrlText($this->shopBaseUrl.$news[1]);

        $news = array($this->shopBaseUrl.$news[1]);
    
        preg_match('#<div id="other-news">(.+)#sui', $text, $text);
        preg_match_all('#<a href="(item_\d+.html)">#sui', $text[1], $news_);
        foreach($news_[1] as $url)
            $news[] = $this->shopBaseUrl."news/".$url;
            
        foreach($news as $news_url)
        {
            $news_item = new ParserNews();

            $text = $this->httpClient->getUrlText($news_url);

            $news_item->urlShort = $this->shopBaseUrl."news/item.html";
            $news_item->urlFull = $news_url;

            preg_match('#item_(\d+).html#sui', $news_item->urlFull, $id);
            $news_item->id = $id[1];

            preg_match('#<h2>(.+?)</h2>#sui', $text, $header);
            $news_item->header = $this->txt($header[1]);
            $news_item->contentShort = $header[1];

            preg_match('#<div class="news">(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];

            preg_match('#<br>\s*<div>(.+?)</div>#sui', $text, $date);
            $news_item->date = $date[1];
            
            $base[] = $news_item;
        }
		
		return $this->saveNewsResult($base);
	}
}
