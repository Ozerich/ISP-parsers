<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_edmins_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.edmins.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#<h2>(.+?)</h2>(.+?)(?=<h2>)#sui', $text, $cities, PREG_SET_ORDER);

        foreach($cities as $city)
        {
            $city_name = $city[1];
            if($city_name == "Чехия")continue;
            $text = str_replace('<span style="color: red;"></span>','',$city[2]);
            /*preg_match_all('#<span style="font-weight: bold;"*?>(.+?)(?=<span style="font-weight: bold;")#sui', $text, $shops);*/
            preg_match_all('#(.+?)(?:<br /><br />|</td>|<br /><span style="font-weight: bold;"(?: valign="top")*><br />)#sui', $text, $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                $shop->city = $city_name;

                $info_temp = explode("<br />", $text);
                $info = array();
                foreach($info_temp as $t)
                {
                    if(mb_strpos($t, "тел.") !== false || mb_strpos($t, "тел:") !== false)$shop->phone = $this->txt($t);
                    else if(mb_strpos($t, "г.") !== false)
                    {
                        preg_match('#г\.(.+?)$#sui', $t, $city);
                        if($city)$shop->city = $this->txt($city[1]);
                    }
                    else if(mb_strpos($t, "ст.") === false && mb_strpos($t, "www")===false && mb_strlen($this->txt($t)) > 2)
                        $info[] = $t;
                }

                $shop->address = $this->txt($info[0]);
                if(count($info) > 1)$shop->address = $this->txt($info[1]).", ".$shop->address;

                $shop->address = str_replace('.,',',',$shop->address);
                
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

        preg_match_all('#<p class="date" style="padding-bottom: 2px;">(.+?)</p><p class="nm" style="padding-bottom: 15px;"><a href="/(news/(\d+)/)">(.+?)</a><br>(.*?)</p>#sui', $text, $news, PREG_SET_ORDER);

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
            preg_match('#<div id="content">(.+?)<br><br><br><a#sui', $text, $content);
            $news_item->contentFull = $content[1];
        
            $base[] = $news_item;
        }

        $url = $this->shopBaseUrl."discounts/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<li><p><a href="/(discounts/(\d+)/)">(.+?)</a></p>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1];
            $news_item->header = $this->txt($news_value[3]);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
             preg_match('#<div id="content">(.+?)<br><br><br><a#sui', $text, $content);
            $news_item->contentFull = $content[1];
            
            $base[] = $news_item;
        }
            
		return $this->saveNewsResult($base);
	}
}
