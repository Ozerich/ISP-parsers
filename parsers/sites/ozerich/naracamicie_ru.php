<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_naracamicie_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.naracamicie.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shop/");
        preg_match_all('#<td colspan="2" style="padding-left:25px;"><h2>(.+?)</h2>.+?<td valign="top" style="padding-top:20px; padding-left:20px;">(.+?)</td>#sui', $text, $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $text = $shop_value[2];
            $city_text = $shop_value[1];
            preg_match('#\d+\.\s#sui', $city_text, $city);
            if($city)$city_text = str_replace($city[0], '', $city_text);
            preg_match('#(.+?)(?:\.|,)#sui', $city_text, $city);
            if($city)$shop->city = $this->txt($city[1]);

            preg_match('#<strong>Тел.:</strong>(.+?)<br>#sui', $text, $phone);
            if($phone)$shop->phone = $this->txt($phone[1]);

            preg_match('#<strong>Время работы:</strong>(.+?)<br>#sui', $text, $timetable);
            if($timetable)$shop->timetable = $this->txt($timetable[1]);

            preg_match('#<strong>Адрес:</strong>(.+?)<br>#sui', $text, $address);
            if($address)$shop->address = $this->txt($address[1]);

            if(mb_strpos($shop->address, "Украина") !== false)continue;

            $shop->address = str_replace(array('Россия, ','Беларусь, ',$shop->city.', ','Марксистская, '), array('','','',''),$shop->address);
            if(mb_substr($shop->address, 0, 2) == 'м ')$shop->address = mb_substr($shop->address, mb_strpos($shop->address, ',') + 2);

            $shop->address = $this->address($shop->address);



            
            $base[]= $shop;
        }
        
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/";
        $text = $this->httpClient->getUrlText($url);

        preg_match('#<div style="padding-left:30px;"><h2>Архив новостей</h2></div>(.+?)<table width="100%" cellspacing="0" cellsapacing="0" style="margin-bottom:130px;">#sui', $text, $text);
        preg_match_all('#<span class="newsdate">(.+?)</span>.+?<div style="padding-left:20px; padding-top:10px;">(.+?)</div>#sui', $text[1], $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->date = $this->txt($news_value[1]);
            if($news_item->date == "00.00.0000")continue;
            $news_item->contentShort = $news_value[2];
            
            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
