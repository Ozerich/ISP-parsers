<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ringo_info extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ringo.info/";

    private function fix_address($address)
    {
        $last_char = mb_substr($address, mb_strlen($address) - 1, 1);
        if($last_char == ';')$address = mb_substr($address, 0, -1);

        $address = $this->address(str_replace(';,', ',', $address));
        $address = str_replace(',,',',',$address);

        return $address;
    }
	
	public function loadItems () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog");
		preg_match_all('#<a href="/(catalog/(.+?))".+?<h2>(.+?)</h2>#sui', $text, $collections, PREG_SET_ORDER);
		
		foreach($collections as $collection_value)
		{
			$collection = new ParserCollection();
			
			$collection->id = $collection_value[2];
			$collection->url = $this->shopBaseUrl.$collection_value[1]."/";
			$collection->name = $collection_value[3];
			
			$url = $collection->url;
			$page = 1;
			while($page < 100)
			{
				$text = $this->httpClient->getUrlText($url."?p=$page");
				
				preg_match('#<li class="current">(\d+)</li>#sui', $text, $current_page);
				$current_page = ($current_page) ? $current_page[1] : 1;
				if($current_page < $page)break;
				
				preg_match('#<div class="b-items">(.+?)</ul>#sui', $text, $text);
				$text = $text[1];


				preg_match_all('#<a href="/(.+?)\?.+?<div class="overlay"></div>(.+?)(?:</a>|</b>)#sui', $text, $items, PREG_SET_ORDER);
				if($page == 1)
				{
					preg_match('#<div class="overlay"></div>(.+?)</b>#sui', $text, $name);
					array_push($items, array("1"=>"?p=1", "2"=>$name[1]));
				}

				foreach($items as $item_value)
				{
					$item = new ParserItem();
					
					$item->id = $item->name = $item->articul = $this->txt($item_value[2]);
					$item->url = $this->shopBaseUrl.$item_value[1];



					
					$text = $this->httpClient->getUrlText($item->url);
					
					preg_match('#ringo.png(.+?)</ul>#sui', $text, $temp);
					preg_match('#<li class="price(?: price-special-offer)*"><span class="price-number">(.+?)</span>#sui', $temp[1], $price);
					if($price)$item->price = str_replace(chr(194).chr(160), "",$price[1]);
					
					$text = $this->httpClient->getUrlText($item->url."/full");
					preg_match('#<ul>(.+?)</ul>#sui', $text, $descr_text);
					$descr_text = str_replace('</li>', "\n", $descr_text[1]);
					
					$item->descr = str_replace("Подробнее", "",$this->txt($descr_text));
					
					preg_match('#Вес, грамм: (.+?)(?:\n|$)#sui', $item->descr, $weight);
					if($weight)$item->weight = $weight[1]." г.";
					
					preg_match('#(Золото, \d+)#sui', $item->descr, $material);
					if($material)$item->material = $material[1];
					



					preg_match('#<div class="views" rel="\'*\.js-main-image(?:-full)* img">(.+?)</div>#sui', $text, $pic_text);

					
					preg_match_all('#rel="/(.+?)"#sui', $pic_text[1], $images, PREG_SET_ORDER);
					foreach($images as $image_item)
					{
						$image = new ParserImage();
						
						$image->url = $this->shopBaseUrl.$image_item[1];
						
						
						$image->type = "jpg";
						$this->httpClient->getUrlBinary($image->url);
						$image->path = $this->httpClient->getLastCacheFile();
						
						$item->images[] = $image;

					}
					$collection->items[] = $item;
				}
				$page++;
			}
			
			$base[] = $collection;
		}
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{

		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."contact/");
		
		preg_match_all('#<li>\s*<a href="/(contact/.+?)">(.+?)</a>\s*</li>#sui', $text, $cities, PREG_SET_ORDER);
		foreach($cities as $city)
		{
			$city[2] = $this->txt($city[2]);
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
			//$text = $this->httpClient->getUrlText("http://ringo.info/contact/moskva");
			
			$shop_address = $shop_phone = $shop_timetable = "";
			
			preg_match_all('#<h3>.+?</h3>(.+?)<p>\&nbsp;</p>#sui', $text, $shops, PREG_SET_ORDER);
			if($shops)
			{
				foreach($shops as $shop_value)
				{
					preg_match_all('#<p>&nbsp;&nbsp;(.+?)</p>#sui', $shop_value[1], $items, PREG_SET_ORDER);
					if(!$items)continue;
					$shop_address = $this->txt($items[0][1]);
					$text = $this->txt($items[1][1]);
					preg_match('#режим работы:(.+?)\n#sui', $text, $timetable);
					if($timetable)$shop_timetable = $this->txt($timetable[1]);
					preg_match('#телефон:(.+?)\n#sui', $text, $phone);
					if($phone)$shop_phone = $this->txt($phone[1]);
					
					$shop = new ParserPhysical();
			
					$shop->address = $shop_address;
					$shop->phone = $shop_phone;
					$shop->timetable = $shop_timetable;
					$shop->city = $city[2];

                    $shop->address = $this->fix_address($shop->address);
			
					$base[] = $shop;
				}
			}
			else 
			{
				preg_match_all('#<h3>.+?</h3>(.+?)<ul>#sui', $text, $shops, PREG_SET_ORDER);
				if($shops)
				{
					foreach($shops as $shop)
					{
						preg_match_all('#<p>(.+?)</p>#sui', $shop[1], $items, PREG_SET_ORDER);
						if(count($items) == 2)
						{
							preg_match('#(.+)<br /><br />(.+?)$#sui', $items[0][1], $address);
							$shop_address = $this->txt($address[2])." ".$this->txt($address[1]);
							$shop_phone = $this->txt($items[1][1]);
						}
						else if(count($items) == 3)
						{
							$shop_address = $this->txt($items[1][1]).", ".$this->txt($items[0][1]);
							$shop_phone = $this->txt($items[2][1]);
						}
						
						$shop_phone = str_replace('телефон:', '', $shop_phone);
					
						$shop = new ParserPhysical();
			
						$shop->address = $shop_address;
						$shop->phone = $shop_phone;
						$shop->timetable = $shop_timetable;
						$shop->city = $city[2];

                        $shop->address = $this->fix_address($shop->address);
			
						$base[] = $shop;
					}
				}
				else
				{
					preg_match('#<p>(?:<strong>)*Адреса магазинов:(?:</strong>)*</p>(.+?)</div>#sui', $text, $content);
					if($content)
					{
						$text = $content[1];
						if(mb_strpos($text, "<li>"))
						{
							preg_match_all('#<li>(.+?)</li>#sui', $text, $items, PREG_SET_ORDER);
							foreach($items as $item)
							{
								$text = $item[1];
								$shop = new ParserPhysical();
								
								preg_match('#(Телефон:*(.+))#sui', $text, $phone);
								if($phone)
								{

									$shop->phone = $this->txt($phone[2]);
									$shop->address = $this->txt(str_replace($phone[1], "", $text));
								}
								else
									$shop->address = $this->txt($text);
								if(mb_substr($shop->address, mb_strlen($shop->address)-1 ) == ',')
									$shop->address = mb_substr($shop->address, 0, -1);
								$shop->city = $city[2];
								$shop->phone = str_replace('Телефон:', '', $shop->phone);

                                $shop->address = $this->fix_address($shop->address);
                                
								$base[] = $shop;
							}
							
						}
						else
						{
							$items = explode("<p>&nbsp;</p>", $text);
							foreach($items as $item)
							{
								preg_match_all("#<p>(.+?)</p>#sui", $item, $items, PREG_SET_ORDER);
								
								$shop = new ParserPhysical();
			
								$shop->address = $this->txt($items[1][1].", ".$items[0][1]);
								if(count($items) > 2)
								{
									$shop->phone = str_replace("телефон:","",$this->txt($items[2][1]));
									$shop->phone = str_replace('Телефон:', '', $shop->phone);
								}
								
								$shop->city = $city[2];
                                $shop->address = $this->fix_address($shop->address);
			
								$base[] = $shop;
							}
						}
					}
					else
					{
						preg_match('#<p>(?:<strong>)*Адрес магазина:(?:&nbsp;)*(?:</strong>)*</p>(.+?)</ul>#sui', $text, $shop_item);
						if($shop_item)
						{
							$text = $shop_item[1];
							if(mb_strpos($text, "<li>") !== false)
							{
								preg_match('#<li>(.+?)</li>#sui', $text, $item);
								$text = $item[1];
								$shop = new ParserPhysical();
								
								preg_match('#(Телефон:*(.+))#sui', $text, $phone);
								if($phone)
								{

									$shop->phone = $this->txt($phone[2]);
									$shop->address = $this->txt(str_replace($phone[1], "", $text));
								}
								else
									$shop->address = $this->txt($text);
								if(mb_substr($shop->address, mb_strlen($shop->address)-1 ) == ',')
									$shop->address = mb_substr($shop->address, 0, -1);
								$shop->city = $city[2];

                                $shop->address = $this->fix_address($shop->address);
								$base[] = $shop;
							}
							else
							{
								preg_match_all("#<p>(.+?)</p>#sui", $text, $items, PREG_SET_ORDER);
								
								$shop = new ParserPhysical();
			
								$shop->address = $this->txt($items[1][1].", ".$items[0][1]);
								if(count($items) > 2)$shop->phone = str_replace("телефон:","",$this->txt($items[2][1]));
								$shop->city = $city[2];

                                $shop->address = $this->fix_address($shop->address);
								$base[] = $shop;
							}
						}
					}
				}
			}

		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();
		
		$url = $this->shopBaseUrl."novosti";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('#<h3>\s*<a href="(.+?)">(.+?)</a>\s*</h3>\s*<p>(.+?)</p>\s*<p class="date">(.+?)</p>#sui', $text, $news, PREG_SET_ORDER);
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->id = mb_substr($news_value[1], mb_strrpos($news_value[1], "/") + 1);
			$news_item->urlShort = $url;
			$news_item->urlFull = $news_value[1];
			$news_item->header = $this->txt($news_value[2]);
			$news_item->contentShort = $news_value[3];
			$news_item->date = $this->date_to_str($this->txt($news_value[4]));
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			
			preg_match('#<div class="b-content">(.+?)</div>#sui', $text, $news_content);
			if($news_content)
			{
				$news_item->contentFull = $news_content[1];
				$news_item->contentFull = str_replace('<h2>Новости</h2>', '', $news_item->contentFull);
			}
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}
}
