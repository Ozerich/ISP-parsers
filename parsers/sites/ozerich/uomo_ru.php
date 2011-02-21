<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_uomo_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.uomo.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."salons/");
        preg_match_all('#/><p>(.+?)</p>#sui', $text, $shops);
        foreach($shops[1] as $text)
        {
            $shop = new ParserPhysical();

            $shop->city = mb_substr($text, 0, mb_strpos($text, ','));
            $shop->phone = mb_substr($text, mb_strpos($text, '<br />') + 7);
            $shop->address = mb_substr($text, mb_strpos($text, ',') + 1);
            $shop->address = $this->txt(mb_substr($shop->address, 0, mb_strpos($shop->address, '<br />')));
    
            $base[] = $shop;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<h2>(.+?)</h2><p>(.+?)</p>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->header = $this->txt($news_value[1]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
    
            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
