<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_rivegauche_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.rivegauche.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match('#<table class="city">(.+?)</table>#sui', $text, $text);
        preg_match_all('#<li.*?><a href="(.+?)">(.+?)</a>.*?</li>#sui', $text[1], $cities, PREG_SET_ORDER);

        foreach($cities as $city)
        {
            $city_name = $city[2];
            $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/".$city[1]);
            preg_match('#<table class="shopsContainer">(.+?)</table>#sui', $text, $text);
            preg_match_all('#<td>(.+?)</td>#sui', $text[1], $texts);
            foreach($texts[1] as $text)
            {
                if($this->txt($text) == "")continue;

                $shop = new ParserPhysical();

                $shop->city = str_replace(' (респ.)','',$city_name);

                preg_match('#<div class="schedule">(.+?)</div>#sui', $text, $timetable);
                if($timetable)$shop->timetable = $this->txt($timetable[1]);

                preg_match('#<div class="address">(.+?)</div>#sui', $text, $address);
                if($address)
                {
                    $address[1] = str_replace("<br />", "<br>", $address[1]);
                    $address = explode("<br>", $address[1]);

                    foreach($address as $t)
                    {
                        if(preg_match('#^[\d\s,\)\(-]+$#sui', $t, $phone))
                            $shop->phone = $phone[0];
                        else
                            $shop->address .= $t;
                    
                    }
                }

                preg_match('#<div class="title">(.+?)</div>#sui', $text, $title);
                if($title)
                {
                    if($shop->address == "")
                        $shop->address = $title[1];
                    else
                        $shop->address .= ", ".$title[1];
                }

                preg_match('#^г\.(.+?),#sui', $shop->address, $city);
                if($city)
                {
                    $shop->city = $city[1];
                    $shop->address = str_replace($city[0], '', $shop->address);
                }

                if($shop->address[0] == '"')
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ",") + 1);

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

        preg_match_all('#<td class="news">\s*<a  href="/(.+?)/".+?>(.+?)</a>\s*<p>(.+?)</p>\s*</td>\s*<td class="date">\s*<div class="day">(\d+)</div>\s*<div class="month".+?>(.+?)</div>#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = mb_substr($news_value[1], mb_strrpos($news_value[1], "/") + 1);
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1]."/";
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[3];
            $news_item->date = $this->date_to_str($news_value[4]." ".$news_value[5]);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="newsPormotionalImage" style="text-align: left;">.+?</div>(.+?)<div style="margin-bottom:\d+px"><#sui', $text, $content);
            if($content)
                $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }
        
		return $this->saveNewsResult($base);
	}
}
