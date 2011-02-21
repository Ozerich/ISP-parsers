<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_eternel_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.eternel.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?area=boutiques");
        preg_match_all('#<div class="address">(.+?)</div>#sui', $text, $shops);

        foreach($shops[1] as $text)
        {
            $shop = new ParserPhysical();

            $info = explode("<br />", $text);

            $shop->address = $this->address($info[1]);
            $shop->phone = trim($this->txt(str_replace('Тел.','',$info[2])));
            $shop->city = $this->txt($info[0]);

            if(mb_strpos($shop->city, ",") !== false)
            {
                $shop->address .= mb_substr($shop->city, mb_strpos($shop->city, ','));
                $shop->city = mb_substr($shop->city, 0, mb_strpos($shop->city, ','));
            }

            $shop->address = $this->address(str_replace('.,',',',$shop->address));
        
            $base[] = $shop;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."?area=news";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<a href="/(\?area=news&amp;id=news\d+)">#sui', $text, $news);
        $news = $news[1];

        $text = $this->httpClient->getUrlText($this->shopBaseUrl.$news[0]);
        preg_match('#<a href="/(\?area=news&amp;id=news\d+)">#sui', $text, $first_news);
        $news = array($first_news[1]) + $news;

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.str_replace("&amp;","&",$news_value);
            $news_item->id = mb_substr($news_item->urlFull, mb_strrpos($news_item->urlFull, "=") + 1);

            $text = $this->httpClient->getUrlText($news_item->urlFull);

            preg_match('#<p class="date">(.+?)</p>\s*<div class="descr"><p><center><b>(.+?)</b></center></p>(.+?)</div>\s*<div class="fulltext">(.+?)</div>#sui', $text, $info);
            
            
            $news_item->date = $info[1];
            $news_item->contentShort = $info[2];
            $news_item->header = $this->txt($info[2]);
            $news_item->contentFull = $info[4];
            
            $base[] = $news_item;
        }
    
		return $this->saveNewsResult($base);
	}
}
