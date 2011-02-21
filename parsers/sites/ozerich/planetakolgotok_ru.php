<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_planetakolgotok_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.planetakolgotok.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $this->add_address_prefix('"Европа Сити Молл"');
        $this->add_address_prefix('"Галерея Краснодар"');

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<li><a href="/trade-shops/">Магазины</a>\s*<ul>(.+?)</ul>#sui', $text, $text);
        preg_match_all('#<a href="/(.+?)">(.+?)</a>#sui', $text[1], $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $this->txt($city[2]);
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);

            preg_match_all("#'comment' : '(.+?)'#sui", $text, $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                $shop->city = $city_name;
                $shop->address = $this->txt($text);

                preg_match('#тел.:(.+)#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = str_replace($phone[0], '', $shop->address);
                }

                preg_match('#г\. (.+?),#sui', $shop->address, $city);
                if($city)
                {
                    $shop->city = $city[1];
                    $shop->address = str_replace($city[0],'',$shop->address);
                }

                $shop->address = str_replace($shop->city.' ', '', $shop->address);
                $shop->address = str_replace('в Ростове-на-Дону', '', $shop->address);

                $shop->address = $this->fix_address($this->address($shop->address));

                $shop->address = str_replace($shop->city.' ', '', $shop->address);
        
                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#ul id="latestnews">\s*<li><p><a href="/(news/(.+?)/)">(.+?)</a>.+?<span class="date">(.+?)</span>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $this->shopBaseUrl;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1];
            $news_item->id = $news_value[2];
            $news_item->header = $this->txt($news_value[3]);
            $news_item->date = $this->txt($news_value[4]);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div id="content">(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];
        
            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
