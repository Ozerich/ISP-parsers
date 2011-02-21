<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_velars_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.velars.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."page.html?id=11");
        preg_match_all('#<b>(.+?):</b><br /><br />(.+?)<br />\s*<br />#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $city[1];
            $text = $city[2];

            preg_match_all('#<a href="/(page.html\?id=11&sid=(\d+))">#sui', $text, $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->id = $shop_value[2];
                $shop->city = $city_name;

                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_value[1]);
                preg_match('#<table width="100%">(.+?)</td>#sui', $text, $text);

                $shop->address = $this->txt($text[1]);

                preg_match('#тел\.*:* ([\d\(\)-]+)#sui', $shop->address, $phone);
                if(!$phone)preg_match('#тел\.*:*\s*([\d-]+)#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address, 'тел'));
                }

                preg_match('#\((.+?)\)#sui', $shop->address, $brack);
                if($brack)$shop->address = str_replace($brack[0],'',$shop->address);

                $shop->address = $this->address($shop->address);
                $shop->address = str_replace('ТЦ "Европейский ','ТЦ "Европейский"',$shop->address);

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 1);
                    $name .= mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 1).", ".$name;
                }

                $shop->address = $this->address($shop->address);
                
                $base[] = $shop;
            }
        }
        

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<div class="content" style="font-size: 12px; color: gray; padding-top: 10px; padding-left: 30px; padding-right: 30px">\s*<p><b>(.+?)</b><br />(.+?)<br />\s*<p>(.+?)</p>.+?<a href="/(news/\?pid=(\d+))"#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->header = $this->txt($news_value[1]);
            $news_item->date = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[3];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[4];
            $news_item->id = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);

            preg_match('#<div class="content" style="font-size: 12px; color: gray; padding-top: 10px; padding-left: 30px; padding-right: 30px">(.+?)<a href="/news/">#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }
            
		return $this->saveNewsResult($base);
	}
}
