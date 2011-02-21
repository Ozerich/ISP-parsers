<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_decathlon_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://www.decathlon.ru/RU/'; // Адрес главной страницы сайта 
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        $this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		return null;
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText("http://www.decathlon.ru/RU/");
		preg_match('#<li id="sport" class="bouton">(.+?)<li id="marque" class="bouton"#sui', $text, $text);
		$text = $text[0];
		preg_match_all('#<li><a href="/RU/(.+?)">(.+?)</a></li>#sui', $text, $collections, PREG_SET_ORDER);
		
		$collections_memory = array();			$collection_count=0;
		foreach($collections as $collection_item)
		{
			$collection = new ParserCollection();
			$collection->id = mb_substr($collection_item[1], 0, -1);
			$collection->url = $this->shopBaseUrl.$collection_item[1];
			if(in_array($collection->url, $collections_memory))
				continue;
			$collections_memory[] = $collection->url;
			
			$text = $this->httpClient->getUrlText($collection->url);
			
			preg_match('#<h2>(.+?)</h2>#sui', $text, $collection->name);
			$collection->name = $this->txt($collection->name[1]);
			$collection->descr = $collection->name;
			
			preg_match_all('#<h3><a href="/RU/(.+?)">\&\#8250; (.+?)</a></h3>#sui', $text, $categories, PREG_SET_ORDER);

			foreach($categories as $category)
			{
				$url = $this->shopBaseUrl.$category[1];
				$category_name = $category[2];
				
				$text = $this->httpClient->getUrlText($url);
				preg_match_all('#<a href="/RU/(product_(.+?)/)"#sui', $text, $items, PREG_SET_ORDER);
				$items_memory = array();
				foreach($items as $item_value)
				{
					$item_url = $this->shopBaseUrl.$item_value[1];
					if(in_array($item_url, $items_memory))
						continue;
					$items_memory[] = $item_url;
					
					$item = new ParserItem();
					$item->url = $item_url;
					$item->id = mb_substr($item_value[2], 0, mb_strpos($item_value[2], "-"));

					$text = $this->httpClient->getUrlText($item->url);
					if(mb_strpos($text,"<title>404 Not Found</title>")!==false)
						continue;
					
					preg_match('#<p class="intitule">(.+?)</p>#sui', $text, $name);
					if($name)
						$item->name = $this->txt($name[1]);
					
					preg_match('#<p class="reference">(.+?)</span></p>#sui', $text, $articul);
					if($articul)
					{
						$articul = $this->txt($articul[1]);
						preg_match('#(.+?)(\d+)(.+?)#sui', $articul, $articul);
						$item->articul = $articul[2];
					}
					$item->categ = $category_name;

					preg_match('#<li><span>Цвет&nbsp;:&nbsp;</span>(.+?)<#sui', $text, $colors);
					if($colors)
					{
						$colors = explode(',', $colors[1]);
						foreach($colors as $color)
						{	
							$color = trim($color);
							if(mb_strpos($color, "есть также")!==false)
								$color = mb_substr($color, mb_strlen("есть также"));
							if($color[mb_strlen($color)-1] =='.')
								$color = mb_substr($color, 0, -1);
							$color = str_replace(".","",$color);
							$item->colors[] = $this->txt($color);
						}
					}
					preg_match('#<div class="texte"><ul>(.+?)</div>#sui', $text, $descr);
					if($descr)
					{
						$descr[1] = str_replace("</li>", "\n", $descr[1]);

						preg_match('#<img src="/RU/images/static/garantie/(\d+)-garantie.gif".+?>#sui',$descr[1], $garanty);
						$item->descr = $this->txt($descr[1]);
						if($garanty)$item->descr .= " ".$garanty[1]." года";
						$item->descr = str_replace(" : ", ": ", $item->descr);
					}
					preg_match('#><li><span>Состав</span>&nbsp;(.+?)</li>#sui', $text, $structure);
					if($structure)
						$item->structure = $structure[1];
					
					$images = array();
					preg_match_all('#<img id="autreVue_\d" onclick="ZoomIt\(this.id\);gaHmap\(\'hmap_model\',\'zoom_select\'\);" src="/(products-pictures/pt-asset_(\d+)\.jpg)"#sui', $text, $images,PREG_SET_ORDER);
					if(!$images)
					{
						preg_match('#<img id="ZoomImg" src="/(.+?)"#sui', $text, $main_image);
						if($main_image)
						$images[] = $main_image;
					}
					for($i = 0; $i < count($images);$i++)
						$images[$i][1] = str_replace('pt-', 'gd-', $images[$i][1]);
					

					if($images)
					{
						foreach($images as $image)
						{
						$image_path = $image[1];
					
						$image = new ParserImage();
						
						preg_match('#(.+?)(\d+)(.+?)#sui', $image_path, $image_id);
						$image->id = $image_id[2];
						
						$image->type = "jpg";
						$image->url = "http://www.decathlon.ru/".$image_path;
						$image->url = str_replace('gd-asset', 'asset', $image->url);
						
						//$this->httpClient->getUrlBinary($image->url);
						//$image->path = $this->httpClient->getLastCacheFile();
						
						$item->images[] = $image;
						}
					}
					
					
					$xml_url ="http://www.decathlon.ru/erep3/service/getProductData.do?cli=ITOOL_INDEX&srv=MOD&lng=RU&thp=14594889-18-399&mod=".$item->id."&dtf=PSXCV";
					$text = $this->httpClient->getUrlText($xml_url);
					
					preg_match('#<val>(\d+)\.#', $text, $price);
					if($price)
						$item->price = $price[1];
					
					preg_match_all('#<vrt-lit>(.+?)</vrt-lit>#sui', $text, $sizes, PREG_SET_ORDER);
					//print_r($sizes);exit();
					
					foreach($sizes as $size)
					{
						$size = $size[1];    
						if($size == ".")continue;
						$size = mb_convert_encoding($size, "Windows-1251", "UTF-8");
						$item->sizes[] = $size;
					}
					
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
		
		$regions = array('18794774/tsentralny-federalny-okrug', '114928754/yujniy-federalny-okrug');
		foreach($regions as $region)
		{
			$url = 'http://www.decathlon.ru/nettools/store/14594889/RU/area/'.$region;
			$text = $this->httpClient->getUrlText($url);
			
			preg_match_all('#<li id="liMagasin_(\d+)"><a href="(.+?)" (.+?)>(.+?)</a></li><script type="text/javascript">
#sui', $text, $shops, PREG_SET_ORDER);
			foreach($shops as $shop_item)
			{

				$shop = new ParserPhysical();
				$shop->id = $shop_item[1];
				
				$url = "http://www.decathlon.ru".$shop_item[2];
				$text = $this->httpClient->getUrlText($url);
				
				preg_match('#<p style="position:relative">(.+?)</p>#sui',$text, $info);
				
				preg_match('#(\d*)(.+?)<br/>(.+?)<br/>(\s*)<br>Тел.:&nbsp;(.+?)<br>(.+?)#sui', $info[1], $info);

				
				$shop->city = $this->txt($info[2]);
				if(mb_strpos($shop->city, 'г.') !== false)
					$shop->city = mb_substr($shop->city, 6);
				$shop->address = $this->txt($info[3]);
				$shop->phone = $this->txt($info[5]);
				
				preg_match_all("#getStringHoraireGlobal\('(.+?)', '(.+?)', '', '', '(.+?)'\)#sui", $text, $timetable_data, PREG_SET_ORDER);
				if($timetable_data)
				{
					for($i = 0; $i < count($timetable_data); $i++)
					{
						$from = $timetable_data[$i][2];
						$to = $timetable_data[$i][3];
						
						if(mb_strlen($from) == 3)$from = $from[0].":".$from[1].$from[2];
						else if(mb_strlen($from) == 4)$from = $from[0].$from[1].":".$from[2].$from[3];
						if(mb_strlen($to) == 3)$to = $to[0].":".$to[1].$to[2];
						else if(mb_strlen($to) == 4)$to = $to[0].$to[1].":".$to[2].$to[3];
						
						$timetable_data[$i][2] = $from;
						$timetable_data[$i][3] = $to;
					}
					
					$curday = $timetable_data[0][1];
					$curtime = array($timetable_data[0][2], $timetable_data[0][3]);
					$timetable = '';
					for($i = 1; $i < count($timetable_data); $i++)
					{
						if($timetable_data[$i][2] != $curtime[0] || $timetable_data[$i][3] != $curtime[1])
						{
							if($timetable)$timetable.="\r\n";
							$timetable .= $curday." - ". $timetable_data[$i - 1][1]." : ".$curtime[0]."-".$curtime[1];
							$curday = $timetable_data[$i][1];
							$curtime = array($timetable_data[$i][2], $timetable_data[$i][3]);
						}
					}
					if($timetable)$timetable.="\r\n";
					$timetable .= $curday." - ". $timetable_data[$i - 1][1]." : ".$curtime[0]."-".$curtime[1];
					
					$shop->timetable = $timetable;
				}
				$base[] = $shop;
			}
		}
		
		return $this->savePhysicalResult ($base);
	}
}
