<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_henderson_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.henderson.ru/rus/";
	
	public function loadItems () 
	{
		$base = array();
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."salons/geo/menu.xml");
		$text = mb_convert_encoding($text, "WINDOWS-1251");
		preg_match_all('#<item name="(.+?)" link="(.+?)"/>#si', $text, $cities, PREG_SET_ORDER);
		
		foreach($cities as $city)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl."salons/geo/".$city[2].".xml");
			preg_match_all('#<item name=".+?" link="(.+?)"/>#si', $text, $shops, PREG_SET_ORDER);
			foreach($shops as $shop_value)
			{
				$text = $this->httpClient->getUrlText($this->shopBaseUrl."salons/geo/".$city[2]."/".$shop_value[1].".html");
				$text = mb_convert_encoding($text, "WINDOWS-1251");

				preg_match  ('#﻿.+?<br.*>(.+?)(?:\n|<br.*>)(.+?)<br/>(?:Teл|Телефон|тел|Тел)*:(.+?)<br.*>Режим работы:(.+?)(?:<|$)#si',$text, $shop_value);


				if($shop_value)
				{
					
					$shop = new ParserPhysical();
				
					$shop->address = $this->txt($shop_value[2].", ".$shop_value[1]);
					$shop->city = $this->txt($city[1]);
					$shop->phone = $shop_value[3];
					$shop->timetable = $shop_value[4];
				
					$base[] = $shop;
				}
			}
		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();
		
		$url = $this->shopBaseUrl."news/news.html";
		$text = $this->httpClient->getUrlText($url);
		$text = mb_convert_encoding($text, "WINDOWS-1251");
		
		
		preg_match_all('#(\d+/\d+/\d+)<BR>(.+?)\d+#si', $text, $news, PREG_SET_ORDER);
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->date = str_replace("/",".",$news_value[1]);
			$news_item->urlShort = $news_item->urlFull = $url;
			$news_item->contentFull = $news_item->contentShort = $news_value[2];
			
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}
}
