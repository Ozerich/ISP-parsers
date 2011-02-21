<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_pjl73_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.pjl73.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."contacts.php");
        preg_match_all('#onclick="window.open\(\'(adress.php\?id=(\d+))\', \'\', \'width=600, height=500, scrollbars=yes\'\)"><b>(.*?)</b></a>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $city[3];
            if($city_name == "Интернет-магазины")continue;
            $city_id = $city[2];
            if($city_name == "")continue;
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);

            preg_match_all('#<a style="font: 11px verdana; color: \#000000; text-decoration: none;">(.+?)</a>#sui', $text, $shops);
            for($i = 1; $i < count($shops[1]); ++$i)
            {
                $text = $shops[1][$i];

                $shop = new ParserPhysical();

                $shop->id = $city_id.$i;
                $shop->address = $this->address($text);
                $shop->city = $city_name;

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ","));
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ",") + 1).", ".$name;
                }

                preg_match('#тел\.* (.+?)$#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = str_replace($phone[0], '', $shop->address);
                }

                if(mb_substr($shop->address, mb_strlen($shop->address) - 2, 1) == ",")
                    $shop->address = mb_substr($shop->address, 0, -2);

                $base[] = $shop;
                
            }
            
        }
        
		return $this->savePhysicalResult ($base); 
	}

	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."content.php";
        
        $text = $this->httpClient->getUrlText($url);
        preg_match_all('#div style="padding-left: 7px; font: 10px verdana;">(.+?)</div>.+?<a style="color: \#000000; text-decoration: none;" href="(news.php\?id=(\d+))">(.+?)</a>.+?<td style="padding-left: 7px; font: 10px verdana;">(.+?)</td>#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $news_value[1];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
            $news_item->id = $news_value[3];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div style="font: 11px verdana; color: \#000000;" >(.+?)</center>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
