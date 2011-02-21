<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_almazholding_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.almaz-holding.ru/";
	
	public function loadItems () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."catalogue/");
		preg_match('#<div class="co1 catalogue" style="padding-left: 40px;">(.+?)</div>\s*</div>\s*</div>#sui', $text, $text);
		preg_match_all('#<a href="/(catalogue/(.+?)/)" title="">.+?<span class="name"><br>(.+?)</span>#sui', $text[1], $collections, PREG_SET_ORDER);
		foreach($collections as $collection_value)
		{
			$collection = new ParserCollection();
			
			$collection->url = $this->shopBaseUrl.$collection_value[1];
			$collection->id = $collection_value[2];
			$collection->name = $collection_value[3];

			
			$text = $this->httpClient->getUrlText($collection->url);
			preg_match('#</span>\s*<ul class="vert">(.+?)</ul>#sui', $text, $text);
			preg_match_all('#<a href="/(.+?)".+?>(.+?)</a>#sui', $text[1], $categories,PREG_SET_ORDER);
			foreach($categories as $category)
			{
				$category_name = $category[2];
				$url = $this->shopBaseUrl.$category[1];
				$from = 0;
				
				while($from < 10000)
				{
					$text = $this->httpClient->getUrlText($url."?from=$from");
					
					preg_match_all('#<a class="c" href="/(.+?)"#sui', $text, $items, PREG_SET_ORDER);
					if(!$items)break;
					foreach($items as $item_value)
					{
						$item = new ParserItem();
						
						$item->url = $this->shopBaseUrl.$item_value[1];
						$item->id = mb_substr($item->url, mb_strrpos($item->url, "/") + 1);
						
						$text = $this->httpClient->getUrlText($item->url);
						
						preg_match('#<h2 class="good-name">(.+?)\(арт&nbsp;(.+?)\)<#sui', $text, $item_);
						$item->name = $this->txt($item_[1]);
						$item->articul = $this->txt($item_[2]);
						
						$item->categ = $category_name;
						
						preg_match('#<div class="shadowed rc-orange">\s*<div class="c">(.+?)&#sui', $text, $price);
						if($price)
						{
						$item->price = str_replace(' ', '', $price[1]);
						$item->price = str_replace(chr(194).chr(160), "",$price[1]);
						}
						
						preg_match('#<select class="select_size"(.+?)</select>#sui', $text, $size_text);
						if($size_text)
						{
							preg_match_all('#<option.+?>(.+?)</option>#sui', $size_text[1], $sizes);
							$item->sizes = $sizes[1];
						}
						
						preg_match('#<dt>Примерный вес</dt>\s*<dd>(.+?)</dd>#sui', $text, $weight);
						$item->weight = str_replace('-', '', $this->txt($weight[1]));
						
						preg_match('#<dt>Металл</dt>\s*<dd>(.+?)</dd>#sui', $text, $material);
						$item->material = $this->txt($material[1]);
						
						preg_match('#<dl class="vert b-char">(.+?)</dl>#sui', $text, $descr_text);
						preg_match_all('#<dt>(.+?)</dt>\s*<dd.*?>(.+?)</dd>#sui', $descr_text[1], $items, PREG_SET_ORDER);
						$item->descr = '';
						foreach($items as $item_)
							$item->descr .= $item_[1].": ".$this->txt($item_[2])."\n";
						
						
						
						preg_match('#<div class="c"><img src="(.+?)"#sui', $text, $image_value);
						
						$image = new ParserImage();
						
						$image->type = "jpg";
						
						$image->url = $image_value[1];
						$this->httpClient->getUrlBinary($image->url);
						$image->path = $this->httpClient->getLastCacheFile();
						
						$image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, -4);
						
						$item->images[] = $image;
					
						$collection->items[] = $item;

					}
					
					$from+=24;
				}
				
				$text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]);
			}
			
			$base[] = $collection;
		}
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
		preg_match('#<div class="b-regions b-regions_list">(.+?)</div>#sui',$text, $text);
		preg_match_all('#<li><a href="/(.+?)".+?#sui', $text[1], $regions,PREG_SET_ORDER);
		foreach($regions as $region)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$region[1]);
			preg_match('#<div class="b-regions b-regions_list">(.+?)</div>#sui', $text, $text);
			preg_match_all('#<li><a href="/(.+?)".+?>(.+?)</a></li>#sui', $text[1], $cities, PREG_SET_ORDER);
			foreach($cities as $city)
			{
				$city_name = $city[2];
				$text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
				
				preg_match_all('#<td class="adr">(.+?)</td>\s*<td class="schedule">(.+?)</td>\s*<td class="tel" style="word-wrap: normal">(.+?)</td>#sui', $text, $shops,PREG_SET_ORDER);
				foreach($shops as $shop_value)
				{
					$shop = new ParserPhysical();
					
					$shop->address = $this->txt($shop_value[1]);
					$shop->timetable = $this->txt($shop_value[2]);
					$shop->phone = $this->txt($shop_value[3]);
					$shop->city = $city_name;
					
					$shop->address = str_replace("Рі. ".$shop->city. ",", '',$shop->address);
					if(mb_substr($shop->address,1,1) == '"')
					{
						$shop->address = mb_substr($shop->address, 2);
						$name = mb_substr($shop->address, 0, mb_strpos($shop->address, '"'));
						$shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 2).', "'.$name.'"';
					}
					
					$base[] = $shop;
				}
			}
		}
		
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();
		
		$url = $this->shopBaseUrl."news/news/";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('#<div class="b-article">.+?<div class="date">(.+?)</div>\s*<h3 class="name">\s*<a href="/(news/news/(\d+).html)" title="">(.+?)</a>.+?<div class="body" align="justify">(.+?)</div>\s*</div>#sui', $text, $news, PREG_SET_ORDER);
		
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
			preg_match('#<div><em class="author"></em></div>(.+?)<div class="shadowed_wrap">#sui', $text, $content);
			if($content)$news_item->contentFull = $content[1];
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}
}
