<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_estelle_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.estelle.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#<li><a href="(shops/.+?)">(.+?)</a></li>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
            $city_name = $city[2];

            preg_match('#<ul id="shop_list">(.+?)</ul>#sui', $text, $text);
            preg_match_all('#<a href="(.+?(\d+)/)">#sui', $text[1], $shops, PREG_SET_ORDER);

            foreach($shops as $shop_item)
            {
                $shop = new ParserPhysical();

                $shop->id = $shop_item[2];
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_item[1]);

               

                $phone = $address = $timetable = null;

                preg_match('#Адрес.*?:(.+?)(?:</p>|<br/>\s*<br/>)#sui', $text, $address);
                   
                if($address)$shop->address = $this->address($address[1]);

                preg_match('#Тел\.*(?:</b>)*:(.+?)(?:</p>|<br/>\s*<br/>|<br/>\s*</*b>|<br/>\s*&nbsp;<br/>)#sui', $text, $phone);
                if($phone)$shop->phone = $this->txt($phone[1]);

                preg_match('#Часы работы:(.+?)(?:</p>|</div>\s*</div>)#sui', $text, $timetable);
                if($timetable)$shop->timetable = $this->txt($timetable[1]);

                $shop->city = $city_name;

                $shop->address = trim(str_replace(array('МО,','С-Петербург,'),array('',''),$shop->address));
                preg_match('#г. (.+?),#sui', $shop->address, $city);
                if($city)
                {
                    $shop->address = trim(str_replace("г. ".$city[1].',', '',$shop->address));
                    $shop->city = $city[1];
                }

                if($this->address_have_prefix($shop->address) || mb_substr($shop->address, 0, 1) == '"')
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ","));
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ",") + 1).", ".$name;
                }

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, "ул"));
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, "ул")).", ".$name;
                }

                $shop->address = str_replace('Тел: '.$shop->phone, '', $shop->address);

                $shop->address = $this->address($shop->address);
                
                $base[] = $shop;
            }
            
        }
        
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
        $base = array();

        $url = $this->shopBaseUrl."estelle/news/";
        $text = $this->httpClient->getUrlText($url);
        
        preg_match('#<ul class="cpl_list_news">(.+?)</ul>#sui', $text, $text);
        preg_match_all('#<li><p><span>(.+?)</span><br /><a href="(estelle/news/(\d+)/)">(.+?)</a><br/>(.+?)</p></li>#sui', $text[1], $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $news_value[1];
            $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
            $news_item->urlShort = $url;
            $news_item->id = $news_value[3];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="cpl_text">(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];
            
            $base[] = $news_item;
        }
        
        
        
        return $this->saveNewsResult($base);
	}
}
