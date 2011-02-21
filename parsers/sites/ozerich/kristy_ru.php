<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_kristy_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.kristy.ru/";
	
	public function loadItems () 
	{
		$base = array ();
	
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $pages_names = array("shops", "shopsinobl", "othershops", "regions");
        $prefixes = array("ТЦ", "Универмаг");
    
        foreach($pages_names as $page_name)
        {
            $url = $this->shopBaseUrl."gdekupit/".$page_name;
            $text = $this->httpClient->getUrlText($url);
            preg_match_all('#<tr><td colspan=2>(.+?)<br></td></tr>#sui', $text, $shop_items, PREG_SET_ORDER);
            foreach($shop_items as $shop_item)
            {
                $text = $shop_item[1];

                $shop = new ParserPhysical();

                preg_match('#<b>Адрес:</td><td>(.+?)</td>#sui', $text, $address);
                if($address)
                {
                    $shop->address = $this->txt($address[1]);
                    $shop->address = str_replace('(схема проезда)', '', $shop->address);
                }
                preg_match('#<b>Телефоны:</td><td>(.+?)</td>#sui', $text, $phone);
                if($phone)$shop->phone = $this->txt($phone[1]);

                preg_match('#<b>Режим работы:</td><td>(.+?)</td>#sui', $text, $timetable);
                if($timetable)$shop->timetable = $this->txt($timetable[1]);

                preg_match("#<h4 style='margin-bottom:0;'>(.+?)</h4>#sui", $text, $header);
                $header = $header[1];

                if(strpos($header, "м.") !== false)
                    $shop->city = "Москва";
                if($page_name == "regions")
                    $shop->city = substr($header, 0, -1);

                if(mb_strpos($header, 'г.') !== false)
                {
                    $header = mb_substr($header, mb_strlen('г.') + 1);
                    if(mb_strpos($header, ","))
                        $header = mb_substr($header, 0, mb_strpos($header, ","));
                    $shop->city = $header;
                }

                foreach($prefixes as $prefix)
                    if(mb_substr($shop->address, 0, mb_strlen($prefix)) == $prefix)
                    {
                        $pos = mb_strpos($shop->address, ",");
                        $prefix_me = mb_substr($shop->address, 0, $pos);
                        $shop->address = mb_substr($shop->address, $pos + 1).",".$prefix_me;
                    }
                
                $base[] = $shop;
            }
        }   
        
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."news/");

        preg_match("#<div style='margin:10 0 0 20;'>(.+?)</div>#sui", $text, $text);
        preg_match_all('#<b>((?:\d|\.)+?)</b>(?:\s\-\s<b>(.*?)</b>)*<br>(.+?)<br><br>#sui', $text[1], $news, PREG_SET_ORDER);

        
        foreach($news as $news_value)
        {
            $news_item =  new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->header = $news_value[2];
            $news_item->contentShort = $news_item->contentFull = trim($news_value[3]);
            $news_item->urlShort = $news_item->urlFull = $this->shopBaseUrl."news/";
            
            $base[] = $news_item;
        }
        
		return $this->saveNewsResult ($base); 
	}
}
