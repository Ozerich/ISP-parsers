<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_medvedkovo_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.medvedkovo.ru/";
	
	public function loadItems () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText("http://www.girlen.ru/");
		preg_match_all('#<li class="CatLevel1.*?"><a href="(.+?)">(.+?)</a></li>#sui', $text, $collections, PREG_SET_ORDER);
		
		foreach($collections as $collection_value)
		{
			$collection = new ParserCollection();
			
			$collection->url = $collection_value[1];
			$collection->name = $this->txt($collection_value[2]);
			$collection->id = substr($collection->url, strrpos($collection->url, "=") + 1);
			
			$text = $this->httpClient->getUrlText($collection->url);
			
			preg_match_all('#<li class="CatLevel2.*?"><a href="(.+?)">(.+?)</a></li>#sui', $text, $categories, PREG_SET_ORDER);
			if(!$categories)
				$categories = array(array("1"=>$collection->url, "2"=>""));
			
			foreach($categories as $category)
			{
				$category_name = $this->txt($category[2]);
				$page = 1;
				while(true)
				{
					$text = $this->httpClient->getUrlText($category[1]."&page=$page");
					preg_match('#&nbsp;<b>(.+?)</b>&nbsp;#sui', $text, $text_page);
					if($page > 1 && (!$text_page || $text_page[1] < $page))break;

					
					preg_match_all('#<a href="(http://www.girlen.ru/product_info.php\?products_id=(\d+))">#sui', $text, $items, PREG_SET_ORDER);
					for($i = 0; $i < count($items); $i+=2)
					{
						$item = new ParserItem();
						
						$item->id = $item->articul = $items[$i][2];
						$item->url = $items[$i][1];
						
						$text = $this->httpClient->getUrlText($item->url);
						
						//if($category_name != "")
						//	$item->categ = $category_name;
						
						$item->categ = $collection->name;
						
						preg_match('#<h1 class="contentBoxHeading">(.+?)</h1>#sui', $text, $name);
						if($name)$item->name = $name[1];
						
						preg_match('#<span class="pricenum">(\d+)</span>#sui', $text, $price);
						if($price)$item->price = $price[1];
						
						preg_match('#Производитель:(.+?)<br\s/>#sui', $text, $brand);
						if($brand)$item->brand = $this->txt($brand[1]);

						preg_match('#Материал:(.+?)<br\s/>#sui', $text, $material);
						if($material)$item->material = $this->txt($material[1]);
				
						preg_match('#Размеры:(.+?)<br\s/>#sui', $text, $rasmers);
						if($rasmers)$item->descr .= "Размеры:".$this->txt($rasmers[1])."\n";
						
						preg_match('#Гарантийный срок:(.+?)<br\s/>#sui', $text, $garanty);
						if($garanty)$item->descr .= "Гарантийный срок:".$this->txt($garanty[1])."\n";
						
						preg_match('#Цвет:(.+?)<br\s/>#sui', $text, $colors);
						if($colors)$item->colors[] = $this->txt($colors[1]);
						
						preg_match('#Вес\s-\s(\d+)г#sui', $item->material,  $weight);
						if($weight)
						{
							$item->weight = $weight[1]."г";
							$a = mb_strpos($item->material, 'Вес - '.$item->weight);
							$item->material = mb_substr($item->material, 0, $a).mb_substr($item->material, $a + mb_strlen('Вес - '.$item->weight)+ 1);
						}
						
						preg_match_all('#<a href="(images/product_images/popup_images/(.+?).JPG)"#sui', $text, $images, PREG_SET_ORDER);
						if($images)
						{
							foreach($images as $image_value)
							{
								$image = new ParserImage();
								
								$image->id = $image_value[2];
								$image->url = "http://www.girlen.ru/".$image_value[1];
								$image->type = "JPG";
								$this->httpClient->getUrlBinary($image->url);
								$image->path = $this->httpClient->getLastCacheFile();
								$item->images[] = $image;
							}
						}
						$collection->items[] = $item;
					}
					
					$page++;
				}
			}
			
			$base[] = $collection;
		}

		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shop/");
		
		preg_match_all("#<li class='MenuItem'><a href=\"/(.+?)\" name='.+?>(.+?)</a>#sui", $text, $cities, PREG_SET_ORDER);
		foreach($cities as $city)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
			$city = $city[2];
			
			preg_match_all('#<ul class="shop">(.+?)</ul>#sui', $text, $shops_text, PREG_SET_ORDER);
			foreach($shops_text as $shop_text)
			{
				preg_match_all('#<li><a href="/(.+?)">(.+?)</a></li>#sui',$shop_text[1], $shops, PREG_SET_ORDER);
				foreach($shops as $shop_item)
				{
					$shop = new ParserPhysical();
					
					$shop_url= $this->shopBaseUrl.$shop_item[1];
					
					if(strrpos($shop_url, "_") !== false)
						$shop->id = substr($shop_url, strrpos($shop_url, "_") + 1, strlen($shop_url) - strrpos($shop_url, "_") - 6);
					else
						$shop->id = substr($shop_url, strrpos($shop_url, "/") + 1, strlen($shop_url) - strrpos($shop_url, "/") - 6);
					
					$shop->city = $city;
					
					$address = $this->txt($shop_item[2]);
					if(mb_substr($address, 0, 2) == 'г.')
					{
						$address = trim(mb_substr($address, 2));
						$address = trim(mb_substr($address, mb_strpos($address, " ") + 1));
					}
					$shop->address = $address;
					
					$text = $this->httpClient->getUrlText($shop_url);
					
					preg_match('#<li><b>тел.:</b>(.+?)</li>#sui', $text, $phone);
					if($phone)$shop->phone = $phone[1];
					
					preg_match('#<h1>Время работы:</h1>\s*<ul class="inner_shop">(.+?)</ul>#sui', $text, $timetable);
					if($timetable)$shop->timetable = $this->txt($timetable[1]);
					
					$base[] = $shop;
				}
			}
			
		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$url = $this->shopBaseUrl."info/new";
		$text = $this->httpClient->getUrlText($url);


		preg_match_all('#<div class="news_date">\s*<b>(\d+)</b>\s*<span>/(\d+)</span>\s*<div class="O\_O"></div>(.+?)</div>\s*<div class="news_text">\s*<h1>(.+?)</h1>\s*<p><p>(.+?)</p></p>#sui', $text, $news, PREG_SET_ORDER);
		
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->date = $news_value[1].".".$this->get_month_number($this->txt($news_value[3])).".20".$news_value[2];
			$news_item->header = $news_value[4];
			$news_item->urlShort = $url;
			$news_item->contentShort = $news_value[5];
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult ($base); 
	}
}
