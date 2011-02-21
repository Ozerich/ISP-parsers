<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_nashamama_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://www.nashamama.ru/'; // Адрес главной страницы сайта 
	
	public function loadNews() 
	{ 
		$base = array();
		
		$page = 1;
	
		while(true)
		{
			$url = $this->shopBaseUrl."rus/st10025/news/?action=archive&category=22&start=$page";
			$text = $this->httpClient->getUrlText($url);
			
			preg_match_all('#<span class="date">(.+?)<br></span>\s*<a href="(.+?)" class="news_title">(.+?)</a>.+?<div class="announce_text">(.+?)</div>#sui', $text, $news, PREG_SET_ORDER);
			
			if(!$news)break;
			
			foreach($news as $news_item)
			{
				$item = new ParserNews();
			
				$item->urlShort = $url;
				$item->urlFull = $this->shopBaseUrl.substr($news_item[2],1); 
				$item->id = substr($item->urlFull, strrpos($item->urlFull, "=") + 1);
				$item->date = $news_item[1];
				$item->header = $news_item[3];
				$item->contentShort = $this->txt($news_item[4]);
				
				$text = $this->httpClient->getUrlText($item->urlFull);
				
				preg_match('#<div class="announce_text">(.+?)</div>#sui', $text, $text);
				$item->contentFull = $text[1];
				
				$base[] = $item;
			}
			
			break;
		}
		
		
		return $this->saveNewsResult ($base);
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		
		preg_match('#objTreeMenu_1(.+?)objTreeMenu_1\.drawMenu\(\);#sui', $text, $text);
		$text = $text[1];
		
		$data = array();
		preg_match_all("#newNode_(\d+)\s=(.+?)class=main_text\s*>(.+?)</span>(.+?);#sui", $text, $nodes, PREG_SET_ORDER);
		foreach($nodes as $node)
		{
			$node_id = $node[1];
			$node_name = $node[3];
			
			preg_match_all("#newNode_".$node_id."_(\d+)\s=.+?<a href=(.+?)(>|\s).+?class=main_text>(.+?)</span>.+?;#sui", $text, $subnodes, PREG_SET_ORDER);
			if(!$subnodes)
			{
				$collection = $node_name;
				
				preg_match('#<a href=/(.+?)>#sui', $node[2], $url);
				$url = $this->shopBaseUrl.$url[1];
				
				$data[] = array("collection" => $collection, "categories" => array(), "url"=>$url);
			}
			else
			{	
				foreach($subnodes as $subnode)
				{
					$subnode_id = $subnode[1];
					$subnode_name = $subnode[4];
					
					$collection = $subnode_name." ".$node_name;
					$subnode[2] = trim($subnode[2]);
					if($subnode[2][0] == '/')
						$collection_url = $this->shopBaseUrl.substr($subnode[2],1);
					else
						$collection_url = $this->shopBaseUrl.$subnode[2];
					
	
					preg_match_all("#newNode_".$node_id."_".$subnode_id."_(\d+)\s=(.+?)<a href=/(.+?)(>|\s)(.+?)class=main_text>(.+?)</span>(.+?);#sui",$text, $categories,PREG_SET_ORDER);
					
					$c_items = array();
					foreach($categories as $category)
					{
						$url = $this->shopBaseUrl.$category[3];
						$name = $category[6];
						$c_items[] = array("name"=>$name, "url"=>$url);
					}
					$data[] = array("collection" => $collection, "url"=>$collection_url, "categories" => $c_items);
				}
			}
		}
		$c = 0;
		foreach($data as $collection_item)
		{
			$collection = new ParserCollection();
			
			$collection->name = $collection_item['collection'];
			$collection->url = $collection_item['url'];
			
			if($collection->url[strlen($collection->url)-1] == '/')
				$collection->url = substr($collection->url, 0, -1);
			$collection->id = substr($collection->url, strrpos($collection->url, "/") + 1);
			
			if($collection_item['categories'])
			{
				$text = $this->httpClient->getUrlText($collection->url);
				preg_match('#<div class="main_text">(.+?)<br><br>\s*<div>\s*</div>#sui', $text, $text);
				$collection->descr = $this->txt($text[1]);
			}
				if(!$collection_item['categories'])
					$collection_item['categories'][] = array('url'=>$collection_item['url'], 'name'=>'');
				foreach($collection_item['categories'] as $category)
				{
					$text = $this->httpClient->getUrlText($category['url']);
					
					preg_match_all("#<TR height=(?:300|320)>(.+?)</TR>#sui", $text, $items, PREG_SET_ORDER);
					foreach($items as $item_data)
					{
						$text = $item_data[1];
						
						$item = new ParserItem();
						
						preg_match('#<IMG.+?src="(.+?)"#sui', $text, $image);
						if($image)
						{
							$image_url = $image[1];
						
							$image = new ParserImage();
							$image->url = $this->shopBaseUrl.substr($image_url,1);
							$image->id = substr($image->url, strrpos($image->url, '/') + 1, strrpos($image->url, '.')-strrpos($image->url,'/') - 1);
							$this->httpClient->getUrlBinary($image->url);
							$image->type = substr($image->url, strrpos($image->url, ".") + 1);
							$image->path = $this->httpClient->getLastCacheFile();
						
							$item->images[] = $image;
						}
						preg_match_all('#<DIV class=cat_item_header.*?>(.+?)</DIV>#sui', $text, $names, PREG_SET_ORDER);
						foreach($names as $name)
						{
							if($name[1] != "&nbsp;")
							{
								$item->name = $this->txt($name[1]);
								break;
							}
						}
						
						$item->url = $category['url'];
						
						preg_match('#<DIV class=main_text.*?>(.+?)</DIV>#sui', $text, $desc);
						if($desc)$item->descr = $this->txt($desc[1]);
						
						preg_match('#Артикул(.+?)<#sui', $text, $art);
						if(!$art)preg_match('#Арт(.+?)<#sui', $text, $art);
						if($art)$item->articul = $this->txt($art[1]);
						if($item->articul != "" && $item->articul[0] == '.')
							$item->articul = substr($item->articul, 2);
						$item->id = $item->articul;
						
						if($category['name'] != "")
						$item->categ = $category['name'];
						
						
						preg_match('#<DIV class=artikul_size>(.+?)</DIV>#sui',$text, $sizes);
						if($sizes)
						{
							$sizes = $sizes[1];
							if(strpos($sizes, ",") !== false)
								$item->colors = explode(',', $sizes);
							else
								$item->sizes[] = $sizes;
						}
						else
						{
							preg_match('#<DIV class=cat_sostav>(.+?)</DIV>#sui', $text, $items);
							preg_match('#<DIV class=cat_sostav_size>(.+?)</DIV>#sui', $text, $items_value);
							if($items)
							{
								$items = explode("<BR>", $items[1]);
								if(count($items) == 1)
								{
									if(strpos($items[0], "Состав") !==false)
										$item->structure = $this->txt($items_value[1]);
								}
								else
								{
									$items_value = explode("<BR>", $items_value[1]);
									$item->structure = $this->txt($items_value[0]);
									if(strpos($items[1], "Цвет") !== false)
										$item->colors = explode(',', $items_value[1]);
									else
										$item->sizes = explode(',', $items_value[1]);
								}
							}
						}
						
						
						if(!$item->structure)
						{
							preg_match('#<SPAN class=cat_sostav>Состав:\s*</SPAN>(.+?)<#sui', $text, $st);
							if($st)
								$item->structure = $st[1];
						}
						if(!$item->sizes)
						{
							preg_match('#<SPAN class=cat_sostav>Размеры:\s*</SPAN>(.+?)</SPAN>#sui',$text, $sizes);
							if($sizes)
							{
								$sizes = explode('<BR>', $sizes[1]);
								foreach($sizes as $size)
									if($this->txt($size) != "")
										$item->sizes[] = $this->txt($size);
							}
						}
						
						if(!$item->sizes)
						{
							preg_match('#<SPAN class=cat_sostav>.*?Размеры:\s*</SPAN>(.+?)</TD>#sui', $text, $sizes);
							if($sizes)
							{

								$sizes = explode("<BR>", $sizes[1]);
								for($i = 0; $i < count($sizes) - 1; $i++)
									if($this->txt($sizes[$i]) != "")
										$item->sizes[] = $this->txt($sizes[$i]);
							}
						}
						
						if($item->id == "")
							$item->id = $item->name;
						
						if(count($item->sizes)==1 && strpos($item->sizes[0], "мл") !== false)
						{
							$item->descr = "Объём: ".$item->sizes[0]."\n".$item->descr;
							$item->sizes = null;
						}
						
						if(count($item->sizes)==1 && strpos($item->sizes[0], "шт") !== false)
						{
							$item->descr = "Количество: ".$item->sizes[0]."\n".$item->descr;
							$item->sizes = null;
						}
						
						if(count($item->sizes)==1 && strpos($item->sizes[0], "г") !== false)
						{
							$item->weight = $item->sizes[0];
							$item->sizes = null;
						}
						
						if(count($item->sizes)==1 && strpos($item->sizes[0], "см") !== false)
						{
							$item->descr .= "\nРазмеры: ".$item->sizes[0];
							$item->sizes = null;
						}
						
						if(count($item->sizes)==1 && strpos($item->sizes[0], ",") !== false)
							$item->sizes = explode(',', $item->sizes[0]);
						
						if(count($item->sizes) > 0 && strpos($item->sizes[0], "мес") !== false)
						{
							$item->descr .= "\nРазмеры:";
							foreach($item->sizes as $size)
								$item->descr .= "\n$size";
							$item->sizes = null;
						}		

						if(count($item->sizes) == 1 && $item->sizes[0] =='')
							$item->sizes = null;

						
						$collection->items[] = $item;
				
					}
				}
			

			$base[] = $collection;
		}
	
		
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."rus/buy/");
		
		preg_match('#<SPAN style="FONT-FAMILY: Comic Sans MS; FONT-SIZE: 24pt">Фирменные магазины в Москве:</SPAN>(.+?)</DIV>#sui', $text, $moscow_shops_text);
		preg_match('#<SPAN style="FONT-FAMILY: Comic Sans MS; FONT-SIZE: 24pt">Фирменные магазины в Регионах:</SPAN>(.+?)</DIV>#sui', $text, $region_shops_text);
		
		preg_match_all('#<P>.*?<A href="(.+?)" target=_self>(.+?)</A>.*?<BR>(.+?)</P>#sui',$moscow_shops_text[1], $moscow_shops, PREG_SET_ORDER);

		
		foreach($moscow_shops as $shop_item)
		{
			$shop = new ParserPhysical();
			
			$url = $shop_item[1];
			$shop->id = substr(substr($url, 0, -1), strrpos(substr($url, 0, -1), '/') + 1);
			$shop->city='Mосква';
			if(strpos($shop_item[3], "<BR>") !== false)
			{
				$address = $this->txt(substr($shop_item[3], 0, strpos($shop_item[3], "<BR>")));
				$phone = substr($shop_item[3], strpos($shop_item[3], "<BR>") + 12);
				if(strpos($phone, ";"))$phone = substr($phone, 0, strpos($phone, ";"));
				if(strpos($phone, "."))$phone = substr($phone, 0, strpos($phone, "."));
			}
			else
			{
				$address = $this->txt($shop_item[3]);
				$phone = "";
			}
			$shop->address = $address;
			if($shop->address[strlen($shop->address)-1] == ",")
				$shop->address = substr($shop->address,0,-1);
			$shop->phone = $phone;
			
			$text = $this->httpClient->getUrlText($url);
			preg_match_all('#<DIV class=main_text>(.+?)</DIV>#sui', $text, $time, PREG_SET_ORDER);
			$index = count($time) - 1;
			while($time[$index][1] == "&nbsp;")$index--;
			$time = $time[$index][1];
			if(strpos($time, "тел") !== false)
				$time = $this->txt(substr($time, strpos($time, "<BR>") + 4));
			$time = $this->txt($time);
			if(strpos($time, 'Автобусом') !== false)
				$time = substr($time, 0, strpos($time,'Автобусом'));
			if($time[0] != 'С')
				$time = substr($time, strpos($time, ' С '));
			$shop->timetable = $time;
			
			if(strpos($shop->address, "ТРК") !== false || strpos($shop->address, "ТЦ") !== false)
			{
				$str = substr($shop->address, 0, strpos($shop->address,","));
				$shop->address = substr($shop->address, strpos($shop->address, ",") + 1).",".$str;
			}
	
			$base[] = $shop;
		}
		
		preg_match_all('#<P>(.+?)</P>#sui',$region_shops_text[1], $region_shops, PREG_SET_ORDER);
		for($i = 0; $i < count($region_shops); ++$i)
		{
			$shop_data = explode('<BR>', $region_shops[$i][1]);
			for($j = 0; $j < count($shop_data); ++$j)
				$shop_data[$j] = $this->txt($shop_data[$j]);
			$region_shops[$i] = $shop_data;
		}
		
		for($i = 0; $i < count($region_shops); ++$i)
		{
			$shop_data = $region_shops[$i];
			$count = 0;
			foreach($shop_data as $item)
				if(strpos($item, "тел") === false)
					$count++;
			if($count == 1)
			{
				$temp = $shop_data[0];
				$a = substr($temp, 0, strpos($temp, ','));
				$b = substr($temp, strpos($temp, ',') + 1);
				$temp = $shop_data;
				$shop_data[0] = $a;
				$shop_data[1] = $b;
				for($j = 1; $j < count($temp); $j++)
					$shop_data[$j + 1] = $temp[$j];
			}
			if(count($shop_data) == 2 && ($i+1) < count($region_shops) && strpos(mb_strtolower($region_shops[$i + 1][0]), "тел") !== false)
			{
				$shop_data[2] = $region_shops[$i + 1][0];
				$i++;
			}
			
			$shop = new ParserPhysical();
			
			$city = $shop_data[0];
			if($city == "")
				continue;
			if(strpos($city, "г.") !== false)
				$city = substr($city, strpos($city, "г.") + 3);
			if($city[strlen($city)-1] == ",")
				$city = substr($city,0,-1);
			$shop->city = $this->txt($city);
			$shop->address = $this->txt($shop_data[1]);
			if($shop->address[strlen($shop->address)-1] == ",")
				$shop->address = substr($shop->address,0,-1);
			if(count($shop_data) > 2 && $shop_data[2] != "")
				$shop->phone = substr($shop_data[2],8 );
			

			if(strpos($shop->address, "ТРК") !== false || strpos($shop->address, "ТЦ") !== false)
			{
				$str = substr($shop->address, 0, strpos($shop->address,","));
				$shop->address = substr($shop->address, strpos($shop->address, ",") + 1).",".$str;
			}
			$base[] = $shop;
		}
		
		
		
		return $this->savePhysicalResult ($base);
	}
}
