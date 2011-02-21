<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_dialma_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.dialma.ru/";
	
	public function loadItems () 
	{
		$base = array();
		
		return $this->saveItemsResult ($base);
	}
	
	private function parseShop($text, $city)
	{
		$shop = new ParserPhysical();
		
		if($city == '')
		{
			$shop->city = mb_substr($text, 0, mb_strpos($text, ","));
			$text = mb_substr($text, mb_strpos($text, ",") + 2);
		}
		else
			$shop->city = $city;
		
		$shop->address = $this->address($text);
		
		preg_match('#([\(-\d\)]*)$#sui', $shop->address, $phone);
		if($phone)
		{
			$shop->phone = $phone[1];
			$shop->address = str_replace(', '.$shop->phone, ' ', $shop->address);
		}
		if($this->address_have_prefix($shop->address))
		{
			$name = mb_substr($shop->address, 0, mb_strpos($shop->address, ','));
			$shop->address = trim(mb_substr($shop->address, mb_strpos($shop->address, ',') + 1).", ".$name);
		}		
		
		return $shop;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."index.php?pg=shop");
		preg_match('#<div class="scroll-pane".+?>(.+?)</div>#sui', $text, $text);
		preg_match_all('#<p>(.+?)(\n|<p>|</p>)#sui', $text[1], $items);
		for($i = 0; $i < count($items[1]); ++$i)
		{
			$item = trim($this->txt($items[1][$i]));
			
			if(mb_strpos($item, ",") === false)
			{
				$city = $item;
				$i++;
				while($i < count($items[1]))
				{
					$item = $items[1][$i];
					
					$name = mb_substr($item, 0, mb_strpos($item, ","));
					if(mb_strpos($name, " ") !== false)
						$base[] = $this->parseShop($item, $city);
					else
					{
						$i--;
						break;
					}
					$i++;
				}
			}
			else
				$base[] = $this->parseShop($item, "");
		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();
		
		$url = $this->shopBaseUrl."index.php?pg=new";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('#<div style="width:100%;">\s*<div><strong>(.+?)</strong></div>\s*<div style="font-size:8pt;">(.+?)</div>\s*<div.+?>(.+?)</div>.+?<div style="float: right;"><a href="/(.+?)">#sui', $text, $news, PREG_SET_ORDER);
		
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->header = $this->txt($news_value[1]);
			$news_item->date = $this->date_to_str($news_value[2]);
			$news_item->contentShort = $news_value[3];
			$news_item->urlShort = $url;
			$news_item->urlFull = $this->shopBaseUrl.$news_value[4];
			preg_match("#id=(\d+)#sui", $news_item->urlFull, $id);
			$news_item->id = $id[1];
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			preg_match('#<div style="text-align:justify;.+?">(.+?)</div>#sui', $text, $text);
			$news_item->contentFull = $text[1];
			
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}
}
