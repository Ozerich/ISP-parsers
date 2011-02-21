<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_budumamoy_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.budumamoy.ru/";
	
	public function loadItems () 
	{
		$base = array ();
		
		$collections = array("clothes", "underwear", "products");
		
		foreach($collections as $collection_name)
		{
			$collection = new ParserCollection();
			
			$collection->url = $this->shopBaseUrl."catalog/".$collection_name."/";
			$collection->id = $collection_name;
			
			$text = $this->httpClient->getUrlText($collection->url);
			
			preg_match('#<h6>(.+?)</h6>#sui', $text, $name);
			$collection->name = $name[1];
			
			preg_match_all('#<li class="c.+?"><a href="/(.+?)">(.+?)</a></li>#sui', $text, $categories, PREG_SET_ORDER);
			
			foreach($categories as $category)
			{
				$category_name = $this->txt($category[2]);
				
				$page = 0;
				while(true)
				{
					$text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]."?page=$page");
					
					preg_match_all('#<h3><a href="/(.+?)/">(.+?)</a></h3>#sui', $text, $items, PREG_SET_ORDER);
					if(count($items) == 0)
						break;
					foreach($items as $item_value)
					{
						$item = new ParserItem();
						
						$item->id = substr($item_value[1], strrpos($item_value[1], "/") + 1);

						$item->url = $this->shopBaseUrl.$item_value[1];
						$item->categ = $category_name;
						$item->name = $this->txt($item_value[2]);
						
						$text = $this->httpClient->getUrlText($item->url);
						
						preg_match('#<div><b>Артикул:</b>(.+?)</div>#sui', $text, $articul);
						if($articul)$item->articul = $this->txt($articul[1]);

						preg_match('#<div><b>Состав:</b>(.+?)</div>#sui', $text, $structure);
						if($structure)$item->structure = $this->txt($structure[1]);
						
						preg_match('#<div><b>Размер:</b>(.+?)</div>#sui', $text, $sizes);
						if($sizes)
						{
							if(strpos($sizes[1], ";") !== false)
							{
								$sizes = explode(';', $sizes[1]);
								foreach($sizes as $size)
								{
									$sub_sizes = explode(',', $size);
									if(count($sub_sizes) > 1)
									{
										preg_match('#(\d+)\D+#sui', $sub_sizes[0], $first);
										if($first)
										{
											$item->sizes[] = trim($sub_sizes[0]);
											for($i = 1; $i < count($sub_sizes); $i++)
												$item->sizes[] = $first[1].trim($sub_sizes[$i]);
										}
										else
											$item->sizes[] = $size;
									}
									else
										$item->sizes[] = $size;
								}
							}
							else if(strpos($sizes[1], ",") !== false)
							{
								$sizes = explode(',', $sizes[1]);
								$item->sizes = $sizes;
							}
							else if(strpos($sizes[1], "от") !== false)
							{
								preg_match('#от(.+)\sдо\s(.+)#sui', $sizes[1], $sizes);
								$item->sizes[] = $sizes[1]."-".$sizes[2];
							}
							else
								$item->sizes[] = $this->txt($sizes[1]);
						}

						preg_match('#<div><b>Розничная цена:</b>(.+?)</div>#sui', $text, $price);
						if($price)
						{
							$price = $this->txt($price[1]);
							if(strpos($price, "руб")!==false)
								$price = substr($price, 0, strpos($price, "руб"));
							$item->price = $price;
						}
						
						$ptags = array();
						preg_match_all('#<p.*?>(.+?)</p>#sui', $text, $ptags, PREG_SET_ORDER);
	
						preg_match('#</div>\s*<p>(.+?)</dd>#sui',$text, $desc);
						if($desc)$item->descr = $this->txt($desc[1]);
						
						preg_match('#<a href="/(brands/(.*?))">#sui', $text, $brand);

						if($brand && $brand[2] != "")
						{
							$brand_text = $this->httpClient->getUrlText($this->shopBaseUrl.$brand[1]);
							preg_match('#<h1>(.+?)</h1>#sui', $brand_text, $brand);
							if($brand)$item->brand = $brand[1];
						}
						
						$images = array();


						preg_match('#<dt id="zoomc">\s*<div style="background-image\:\surl\(\'(.+?)\'\);">#sui', $text, $main_image);
						if($ptags)
						{
							$txt = $ptags[count($ptags)-1][1];
							preg_match_all('#<a href="(.+?)" target="_blank" class="zoomable">#sui', $txt, $images, PREG_SET_ORDER);
						}		
					
						$images[] = $main_image;	
						foreach($images as $image_item)
						{
							$image = new ParserImage();
							$image->url = $this->shopBaseUrl.substr($image_item[1],1);
							$image->id = substr($image->url, strrpos($image->url, '/') + 1, strrpos($image->url, '.')-strrpos($image->url,'/') - 1);
							$this->httpClient->getUrlBinary($image->url);
							$image->type = substr($image->url, strrpos($image->url, ".") + 1);
							$image->path = $this->httpClient->getLastCacheFile();
							
							$item->images[] = $image;
						}
						
						if($ptags)
						foreach($ptags as $txt)
							if(strpos($txt[1], "Цвет:")!==false)
							{
								$txt[1] = $this->txt($txt[1]);
								$colors = substr($txt[1], strlen("Цвет:")+1);
								if(strpos($colors, ",") !== false)
									$colors = explode(",", $colors);
								else if(strpos($colors, ";") !== false)
									$colors = explode(";", $colors);
								else
									$colors = array($colors);
								foreach($colors as $color)
									if($color != "nbsp")
									{
										if(strpos($color,";") === false)
											$item->colors[] = $this->txt($color);
										else
										{
											$subcolors = explode(';', $color);
											foreach($subcolors as $subcolor)
												$item->colors[] = $this->txt($subcolor);
										}
									}
								break;
							}
						
						if(!$item->brand)
						{
							preg_match('#<strong>тм(.+?)</strong>#sui', $text, $brand);
							if($brand)
								$item->brand = $brand[1];
						}						
						$collection->items[] = $item;
					}
					
					$page += 12;
				}
				
			}
			

			$base[] = $collection;
		}
	
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
		
		preg_match_all('#<a href="/(shops/.+?)">(?:.+?)/>(.+?)</a>#sui', $text, $cities, PREG_SET_ORDER);
		foreach($cities as $city)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
			preg_match_all('#<li><a href="/(.+?)">#sui', $text, $shops, PREG_SET_ORDER);
			foreach($shops as $shop_item)
			{
				$url = $this->shopBaseUrl.$shop_item[1];
				$text = $this->httpClient->getUrlText($url);
				
				$shop = new ParserPhysical();
				$shop->id = substr(substr($url, 0, -1), strrpos(substr($url, 0, -1), "/") + 1);
				$shop->url = $url;
				
				preg_match('#<div><b>Адрес:</b>(.+?)</div>#sui', $text, $address);
				if($address)$shop->address = $this->txt($address[1]);
				
				preg_match('#<div><b>Часы работы:</b>(.+?)</div>#sui', $text, $timetable);
				if($timetable)$shop->timetable = $this->txt($timetable[1]);
				
				preg_match('#<div><b>Телефон:</b>(.+?)</div>#sui', $text, $phone);
				if($phone)$shop->phone = $this->txt($phone[1]);
				
				$shop->city = $city[2];
				
				$base[] = $shop;
			}
		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$url = $this->shopBaseUrl."shares/";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('#<dd><span>(.+?)</span><h3><a href="/(.+?)/">(.+?)</a></h3><div>(.+?)</div><a href="/(.+?)" class="more">Подробнее</a></dd>#sui', $text, $news, PREG_SET_ORDER);
		foreach($news as $item)
		{
			$news_item = new ParserNews();
			
			$news_item->urlShort = $url;
			$news_item->urlFull = $this->shopBaseUrl.$item[2];
			$news_item->id = substr($news_item->urlFull, strrpos($news_item->urlFull, "/")+1);
			$news_item->contentShort = $item[4];
			$news_item->date = $item[1];
			$news_item->header = $item[3];
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			
			preg_match('#<h1>.+?</h1>(.+?)<a class="back" href="/shares/">Назад к списку</a>#sui', $text, $content);
			$news_item->contentFull = $this->txt($content[1]);
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult ($base); 
	}
}
