<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_vereteno_fashion_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.vereteno-fashion.ru/";
	
	public function loadItems () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog/");
		preg_match('#<li><span>(.+?)</span></li>#sui', $text, $cur_col);
		$collections = array(array("1"=>$this->shopBaseUrl."catalog/", "2"=>$cur_col[1]));
		
		preg_match_all('#<li><a href="/(.+?)">(.+?)</a></li>#sui', $text, $collections_, PREG_SET_ORDER);
		foreach($collections_ as $col)
			array_push($collections, array("1"=>$this->shopBaseUrl.$col[1], "2"=>$col[2]));
		foreach($collections as $collection_item)
		{
			$collection = new ParserCollection();
			
			$collection->name = $collection_item[2];
			$collection->url = $collection_item[1];
			$collection->id = mb_substr($collection->url, mb_strrpos(mb_substr($collection->url, 0, -1), "/") + 1, -1);
			
			$text = $this->httpClient->getUrlText($collection->url);
			preg_match('#<span class="dim"><a href="/(.+?)">показать все</a></span>#sui', $text, $all);
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$all[1]);
			
			preg_match_all('#(?:<div class="span-5 append-1">|<div class="span-5 last">).+?<a href="/(.+?)\?.+?</div></div>#sui', $text, $items);
			
			foreach($items[1] as $item_value)
			{
				$item = new ParserItem();
				
				$item->url = $this->shopBaseUrl.$item_value;
				//$item->url = "http://vereteno-fashion.ru/catalog/items/271/";
				preg_match('#catalog/items/(\d+)/#sui', $item->url, $id);
				$item->id = $id[1];
				
				$text = $this->httpClient->getUrlText($item->url);
				
				preg_match('#<span class="all-caps letter-spacing">(.+?)</span>#sui', $text, $name);
				$item->name = $name[1];
				
				preg_match('#<span class="h2">(\d+)#sui', $text, $price);
				$item->price = $price[1];
				
				preg_match('#<span class="italic">Артикулы/цвета:</span><br/>(.+?)</p>#sui', $text, $colors);
				preg_match_all('#<span class="dim">(.+?)</span>#sui', $colors[1], $colors);
				foreach($colors[1] as $color)
					$item->colors[] = $this->txt($color);
				
				preg_match('#<span class="italic">Размеры:</span><br/><span class="dim">(.*?)</span>#sui', $text, $sizes);
				if($sizes[1])$item->sizes = explode('|', $sizes[1]);
				
				$images = array();
				
				preg_match('#<div class="span-16 last" style="padding-top: 30px;"><img src="/(.+?)"#sui', $text, $image_url);
				$images[] = $this->shopBaseUrl.$image_url[1];
				
				preg_match('#<div class="span-8 last" style="background: url\(/(.+?)\)#sui', $text, $image_url);
				$images[] = $this->shopBaseUrl.$image_url[1];
				
				foreach($images as $image_url)
				{
					$image = new ParserImage();
					$image->url = $image_url;
					
					$image->type = "jpg";
					$this->httpClient->getUrlBinary($image->url);
					$image->path = $this->httpClient->getLastCacheFile();
						
					$image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, -4);
					$item->images[] = $image;
				}
				
				
				//print_r($item);exit();
				
				
				$collection->items[] = $item;
			}
			
			
			$base[] = $collection;
		}
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."stores/");
		
		preg_match('#<h3>.+?</h3>(.+?)<h3>.+?</h3>(.+?)Интернет-магазины#sui', $text, $text);
		
		$text1 = $text[1];
		$text2 = $text[2];
		
		$texts = array($text1, $text2);
		
		foreach($texts as $text)
		{
		preg_match_all('#<h4 style="padding-top: 10px;">(.+?)</h4>(.+?)(?:<div class="span-3">|$)#sui', $text, $cities, PREG_SET_ORDER);
		foreach($cities as $city)
		{
			$city_name = $city[1];
			if(mb_strpos($city_name, ",") !== false)continue;
			$text = $city[2];
			
			$items = explode('</p><p><br />', $text);
			if(count($items) > 1)
			{
				$shops = array();
				foreach($items as $item)
					$shops[] = array("1"=>$item);
			}
			else
				preg_match_all('#<div.+?>(.+?)</div>#sui', $text, $shops, PREG_SET_ORDER);
			foreach($shops as $shop_item)
			{
				
				$items = preg_split('#(<br />|<p)#sui', $shop_item[1]);
				if(isset($items[4]))$items[4] = str_replace('<sup>', '-', $items[4]);
				for($i = 0; $i < count($items); ++$i)
				{
					$items[$i] = $this->txt($items[$i]);
					$str = $items[$i];
					if(mb_substr($str, 0, 1) == '>')
						$items[$i] = mb_substr($str, 1);
				}
				
				$shop = new ParserPhysical();
				
				if(count($items) > 2)
                {

                    if(isset($items[3]) && mb_strpos($items[3], 'пл') !== false)
                        $shop->address = $this->txt($items[3]). ", ". $this->txt($items[2]);
					else
                        $shop->address = $this->txt($items[2]). ", ". $this->txt($items[1]);
                }
				else
					$shop->address = $this->txt($items[1]);
				if(isset($items[4]))$shop->timetable = $this->txt($items[4]);
				if(isset($items[5]))$shop->phone = $this->txt($items[5]);
				$shop->city = $city_name;
				
				$shop->timetable = str_replace("Семёновская", "", $shop->timetable);
				
				$base[] = $shop;
			}
			
		}
	}
	
		
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{;
		$base = array();
		
		$url = $this->shopBaseUrl."news/";
		$text = $this->httpClient->getUrlText($url);
		preg_match_all('#<h3><a href="./(\d+)/">(.+?)</a></h3>.+?<p class="serif dim letter-spacing">(.+?)</p>.+?<div class="span-12 last">(.+?)</p></div></div></div>#sui', $text, $news, PREG_SET_ORDER);
		
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->id = $news_value[1];
			$news_item->header = $this->txt($news_value[2]);
			$news_item->date = $news_value[3];
			$news_item->contentShort = $news_value[4];
			$news_item->urlShort = $url;
			$news_item->urlFull = $url.$news_item->id."/";
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			preg_match('#<div class="prepend-3 span-12 last">(.+?)</p></div></div></div></div>#sui',$text, $content);
			$news_item->contentFull = $content[1];
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}
}
