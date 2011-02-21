<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_westland_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.westland.ru/";
	
	public function loadItems () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."collection/show/");
		preg_match('#<h1>.+?<a href="/(collection/show/.+?)".+?>(.+?)</a>#sui', $text, $collection_name);
		

		
		$collection = new ParserCollection();
		
		$collection->url = $this->shopBaseUrl.$collection_name[1];
		$collection->name = $this->txt($collection_name[2]);
		
		$text = $this->httpClient->getUrlText($collection->url);
		preg_match('#</b></a></h1>\s*<p>(.+)</div>#sui', $text, $text);
		preg_match_all('#<h1>(.+?)</h1>\s*<ul type="disc">(.+?)</ul>#sui', $text[1], $categories, PREG_SET_ORDER);
		
		foreach($categories as $category_value)
		{
			$category_name = $category_value[1];
			preg_match_all('#<a href="/(.+?)".+?>(.+?)</a>#sui', $category_value[2], $sub_categories, PREG_SET_ORDER);
			foreach($sub_categories as $sub_category)
			{
				$sub_category_name = $sub_category[2];
				$text = $this->httpClient->getUrlText($this->shopBaseUrl.$sub_category[1]);
				preg_match('#<center>\s*<a href="./(.+?)"><b>Показать все</b></a>#sui', $text, $url);
				$text = $this->httpClient->getUrlText($this->shopBaseUrl."collection/show/".$url[1]);
				preg_match('#<table border="0" width="460" cellpadding="0" cellspacing="4" align="center">(.+?)</table>#sui', $text, $text);
				preg_match_all('#<tr>\s*<td align="center"><a href="/(.+?)">#sui', $text[1], $items, PREG_SET_ORDER);
				foreach($items as $item_value)
				{
					$item = new ParserItem();
					
					$item->url = $this->shopBaseUrl.$item_value[1];
					
					preg_match('#id=(\d+)#sui', $item->url, $id);
					$item->id = $id[1];
					$item->categ = array($category_name, $sub_category_name);
					
					$text = $this->httpClient->getUrlText($item->url);
					
					preg_match('#<tr><td><b>(.+?)</b></td>#sui', $text, $name);
					$item->name = $name[1];
					
					preg_match('#<p><strong>Описание модели:</strong>(.+?)</td>#sui', $text, $descr);
					$item->descr = $this->txt($descr[1]);
					
					preg_match('#арт. (\d+)#sui', $text, $articul);
					if($articul)$item->articul = $articul[1];
					
					preg_match('#<p><strong>состав:</strong>(.+?)\n#sui', $text, $material);
					$item->structure = $this->txt($material[1]);
					
					preg_match('#<p><strong>цена:</strong> <s>(.+?)р.</s>, <br>теперь <font color="red">(.+?)р.</font>#sui', $text, $price);
					if($price)
					{
						
						$old_price = $price[1];
						$new_price = $price[2];
					}
					else
					{
						preg_match('#<p><strong>цена:</strong> (.+?)р.#sui', $text, $price);
						$old_price = $price[1];
						$new_price = '';
					}
					
					
					$old_price = str_replace(' ', '', $this->txt($old_price));
					$new_price = str_replace(' ', '', $this->txt($new_price));
					
					$item->price = $old_price;
					
					if($new_price != '')
						$item->discount = $this->discount($old_price, $new_price);
					
					preg_match('#<tr><td width="300" valign="top">\s*<a href="/(.+?)"#sui', $text, $main_image);
					$images = array($main_image[1]);
					
					preg_match('#<td width="150" valign="top">(.+?)</td>#sui', $text, $images_text);
					preg_match_all('#<a href="/(.+?)"#sui', $images_text[1], $image_urls);
					foreach($image_urls[1] as $url)
						$images[] = $url;
					
					foreach($images as $image_value)
					{
						$image = new ParserImage();
						
						$image->url = $this->urlencode_partial($this->shopBaseUrl.$image_value);
						$image->type = "jpg";

						$this->httpClient->getUrlBinary($image->url);
						$image->path = $this->httpClient->getLastCacheFile();
						
						$image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, -4);
						
						$item->images[] = $image;
					}
					
					preg_match('#<td>Рост/размер:</td>(.+?)</select>#sui', $text, $sizes);
					if($sizes)
					{
						preg_match_all('#<option>(.+?)</option>#sui', $sizes[1], $sizes);
						foreach($sizes[1] as $size)
						{	
							if(mb_strpos($size, "Не выбран") !== false)continue;
							$item->sizes[] = $size;
						}
					}
					$collection->items[] = $item;
				}
			}
		}
		
		$base[] = $collection;
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
		preg_match('#<table border="0" width="450">.+?</td></tr>(.+?)</table>#sui', $text, $text);

		preg_match_all('#<a href="(.+?)".*?>(.+?)</a>#sui', $text[1], $cities, PREG_SET_ORDER);

		foreach($cities as $city)
		{
			$url_add = (mb_strpos($city[1], '.') === false) ? mb_substr($city[1], 1) : "shops".mb_substr($city[1], 1);

			$url = $this->shopBaseUrl.$url_add;

			$text = $this->httpClient->getUrlText($url);
			if(mb_strpos($text, "Отзывы о магазине") !== false)
				$shops = array("1"=>array($url_add));
			else
				preg_match_all('#<a href="/(shops/city/shop/.+?)">#sui', $text, $shops);
			foreach($shops[1] as $shop_url)
			{
				$text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_url);

				preg_match('#Наименование:</b>(.+?)<br />#sui', $text, $name);
				preg_match('#Адрес:</b>(.+?)<br />#sui', $text, $address);
				preg_match('#Часы работы:</b>(.+?)<br />#sui', $text, $timetable);
				preg_match('#Телефон:</b>(.+?)<br />#sui', $text, $phone);
				
				$address = $this->txt($address[1]);
				$name = $this->txt($name[1]);
				$timetable = $this->txt($timetable[1]);
				$phone = ($phone) ? $this->txt($phone[1]) : "";
				
				$shop = new ParserPhysical();
				
				$shop->address = $address;
				$shop->phone = $phone;
				$shop->timetable = $timetable;
				
				preg_match('#<a href="/shops/" class="greencol">Где купить</a> / <a.+?>(.+?)</a>#sui', $text, $city_name);
				$shop->city = $city_name[1];
				
				$shop->city = str_replace('Москва и Московская область', 'Москва', $shop->city);
				
				preg_match('#(г\.(.+?),)#sui', $shop->address, $city);
				if($city)
				{
					$shop->city = $city[2];
					$shop->address = trim(str_replace($city[1], " ", $shop->address));
				}
				
				$shop->address = $this->address($shop->address);
				if($this->address_have_prefix($shop->address))
				{
					$name = mb_substr($shop->address, 0, mb_strpos($shop->address, ','));
					$shop->address = trim(mb_substr($shop->address, mb_strpos($shop->address, ',') + 1).", ".$name);
				}
				
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
		
		preg_match_all('#<table border="0">(.+?)</table>#sui', $text, $news_blocks, PREG_SET_ORDER);
		foreach($news_blocks as $block)
		{
			$text = $block[1];
			preg_match_all('#<td>(.+?)</td>#sui', $text, $items);
			$items = $items[1];
			
			$news = new ParserNews();
			
			$news->date = mb_substr($this->txt($items[0]), 1, -1);
			$news->header = $this->txt($items[1]);
			$news->contentShort = trim($items[3]);
			$news->urlShort = $url;
			
			preg_match('#<a href="/(news/show/\?(\d+))">#sui', $items[5], $item);
			$news->urlFull = $this->shopBaseUrl.$item[1];
			$news->id = $item[2];
			
			$text = $this->httpClient->getUrlText($news->urlFull);
			preg_match('#<table border="0">(.+?)</table>#sui', $text, $text);
			preg_match_all('#<td>(.+?)</td>#sui', $text[1], $items);
			$news->contentFull = trim($items[1][3]);

			$base[] = $news;
		}
		
		return $this->saveNewsResult($base);
	}
}
ntShort = trim($items[3]);
			$news->urlShort = $url;
			
			preg_match('#<a href="/(news/show/\?(\d+))">#sui', $items[5], $item);
			$news->urlFull = $this->shopBaseUrl.$item[1];
			$news->id = $item[2];
			
			$text = $this->httpClient->getUrlText($news->urlFull);
			preg_match('#<table border="0">(.+?)</table>#sui', $text, $text);
			preg_match_all('#<td>(.+?)</td>#sui', $text[1], $items);
			$