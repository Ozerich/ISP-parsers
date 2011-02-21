<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_incity_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://incity.ru/'; // Адрес главной страницы сайта 
	
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{
		$base_url = $this->shopBaseUrl."promotions/";
		$text = $this->httpClient->getUrlText($base_url);
		
		preg_match_all('#<li><a href="/promotions/(\S+?)" class="root-item(-selected)?">(.+?)</a></li>#sui', $text, $items, PREG_SET_ORDER);
		foreach($items as $item)
		{
			$result_item = new ParserNews();
			
			$url = $this->urlencode_partial($this->shopBaseUrl."promotions/".$item[1]);
			$text = $this->httpClient->getUrlText($url);

			$result_item->urlShort = $base_url;
			$result_item->urlFull = $url;
			$result_item->header = $item[3];
	
			$result_item->id = $item[1];
			
			if (preg_match('#<div class="pm-work-area">(.+?)</div>#sui', $text, $content))
				$result_item->contentFull = $content[1];
			
			$base[] = $result_item;
		}
		
		return $this->saveNewsResult ($base); 
	}
	
	private function parseImages($item)
	{
		$this->httpClient->getUrlText($item->url);
		$url = $this->shopBaseUrl."bitrix/templates/pagemaster_06.2010/js/photos.php";
		$text = $this->httpClient->getUrlText($url, null, false);

		$pregImages = '#pic_url="(/upload/iblock/(.+?).jpg)"#sui';
		preg_match_all($pregImages, $text, $images, PREG_SET_ORDER);

		foreach($images as $curimage)
		{
			$image = new ParserImage();
			$image->url = $this->shopBaseUrl. $this->urlencode_partial (substr($curimage[1], 1));

			$this->httpClient->getUrlBinary($image->url);
			$image->path = $this->httpClient->getLastCacheFile();
			$image->type = "jpg";
			$image->id = $curimage[2];
			$item->images[] = $image;
			
		}
	}
	
	private function parseItem($url, $item)
	{
		$text = $this->httpClient->getUrlText($url);

		$pregName='#<div class="pm-element-title">\s*<span>(.+?)</span><br />\s*Артикул:\s*(.+?)\s*</div>#sui';
		preg_match($pregName, $text, $nameData);
		$item->name = $nameData[1];
		$item->articul = $nameData[2];

		$pregPrice = '#<div class="pm-element-price">\s*(<del><small>(.+?)руб.</small></del>)*(.+?)руб.\s*</div>#sui';
		preg_match($pregPrice, $text, $price);
		$item->price = str_replace("руб.", "", $this->txt($price[3]));
		$item->price = str_replace(" ", "", $item->price);
		if($price[2] != "")
		{
			$old_price = str_replace(" ", "",$this->txt($price[2]));
			$razn = $old_price - $item->price;
			$item->discount = $razn / $item->price * 100;
			$item->discount = mb_substr($item->discount, 0, mb_strpos($item->discount, "."));
		}
        
		$pregStructure = '#<div class="clear"></div>\s*<p>(.+?)</p>#sui';
		preg_match($pregStructure, $text, $structure);
		if(count($structure) > 0)
			$item->structure = $this->txt(str_replace("&#37;", "%", $structure[1]));
		
		$pregSizes = '#Размер (\d+)#sui';
		preg_match_all($pregSizes, $text, $sizes, PREG_SET_ORDER);
		$item->sizes = array();
		foreach($sizes as $size)
			$item->sizes[] = $size[1];
		$this->parseImages($item);
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText ('http://incity.ru/collection/', null, false);
		
		$pregCollections = '#<li><a href="(/collection/(\d+)/)" class="root-item">(.+?)</a>(.+?)</ul></li>#sui';
		preg_match_all($pregCollections, $text, $collections, PREG_SET_ORDER);
		
		$curCollection = $collections[count($collections) - 1];
		//foreach($collections as $curCollection)
		//{
			$collection = new ParserCollection();
			$collection->id = $curCollection[2]." ".$curCollection[3];
			$collection->name = $curCollection[3];
			$collection->url = $this->shopBaseUrl.substr($curCollection[1],1);
			$resultItems = array();
			
			$text = $this->httpClient->getUrlText($collection->url);
			
			$pregType = '#<li><a href="(/collection/(\d+)/)"\s*>(.+?)</a></li>#sui';
			preg_match_all($pregType, $curCollection[4], $types, PREG_SET_ORDER);
			foreach($types as $curType)
			{
				$page_number = 1;
				while(true)
				{
					$url = $this->shopBaseUrl.substr($curType[1],1)."?PAGEN_1=".$page_number."/";
					$text = $this->httpClient->getUrlText($url);
					$pregItem = '#<div class="pm-item">(.+?)<a href="(/collection/'.$curType[2].'/(\d+)/)"><img(.+?)src="(.+?)"(.+?)</div>#sui';
					preg_match_all($pregItem, $text, $items, PREG_SET_ORDER);
					foreach($items as $curItem)
					{
						$item = new ParserItem;
						$item->id = $curItem[3];
						$item->url = $this->shopBaseUrl.substr($curItem[2],1);
						$item->categ = $curType[3];
						$this->parseItem($item->url, $item);
						$resultItems[] = $item;
						//sleep(1);
					}
					$pregPages = '#<a class="modern-page-next"#sui';
					preg_match($pregPages, $text, $pages);
					if(count($pages) == 0)
						break;
					else
						$page_number++;

					
				}
			}
			$collection->items = $resultItems;
			$base[] = $collection;
		//}

		

		return $this->saveItemsResult ($base);
	}
	
	private function getAddress($item, $addressData)
	{
		$data = explode(",", $addressData);
		if(strpos($data[0], "обл.") > 0)
			$index = 1;
		else
			$index = 0;
		$item->city = $data[$index++];
		for(;$index < count($data); $index++)
		{
			$item->address .= $data[$index];
			if($index < count($data) - 1)
				$item->address .= ", ";
		}
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
	
		$text = $this->httpClient->getUrlText("http://incity.ru/stores/");
		
		$pregCountries = '#<li><a href="(/stores/\d+/)" class="root-item">(.+?)</a>#sui';
		preg_match_all($pregCountries, $text, $countriesData, PREG_SET_ORDER);
			$text = $this->httpClient->getUrlText("http://incity.ru/stores");
			$pregCities = '#<div class="pm-store-item"><span class="pm-store-title">(.+?)</span>(.+?)</div>#sui';
			preg_match_all($pregCities, $text, $citiesData, PREG_SET_ORDER);
		


			foreach($citiesData as $currentCity)
			{
				$city = $currentCity[1];
				$pregCityData = '#<td valign="top">(.+?)</table>#sui';
				preg_match_all($pregCityData, $currentCity[2], $cityData);
				$cityData = $cityData[1];
				foreach($cityData as $shop)
				{
					$pregShopName = '#\s*(.+?)\s*</td>#sui';
					preg_match_all($pregShopName, $shop, $shopName);
					$shopName = $shopName[1][0];
				
					$pregShopInfo = '#<td class="pm-store-address">\s*(.+?)\s*</td>#sui';
					preg_match_all($pregShopInfo, $shop, $shopInfo);
					$shopInfo = $shopInfo[1][0];
				
					$pregShopInfo = "#(.+?)тел\.:(.*?)$#sui";
					preg_match_all($pregShopInfo, $shopInfo, $shopInfo);
				
					$address = $shopInfo[1][0];
					$address = str_replace("&laquo;","«",$address);
					$address = str_replace("&raquo;","»",$address);
					$address = substr($address, 0, strlen($address) - 2);
					$phone = $shopInfo[2][0];
					
					$item = new ParserPhysical();
					$item->phone = $phone;
					$this->getAddress($item, $address);
					$base[] = $item;
				}
			}
		
		return $this->savePhysicalResult ($base);
	}
}
