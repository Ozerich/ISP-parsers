<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_kiraplastinina_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://kiraplastinina.ru/'; // Адрес главной страницы сайта 
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        $this->httpClient->setRequestsPause (0.5); 
    }	
	public function loadNews() 
	{ 
		$month_names = array("ЯНВАРЯ" => 1,"ФЕВРАЛЯ" => 2,"МАРТА" => 3,"АПРЕЛЯ" => 4,"МАЯ" => 5,"ИЮНЯ" => 6,"ИЮЛЯ" => 7,"АВГУСТА" => 8,"СЕНТЯБРЯ" => 9,"ОКТЯБРЯ" => 10,"НОВБРЯ" => 11,
			"НОЯБРЯ" => 11,"ДЕКАБРЯ" => 12);
		$base = array();
		$url = $this->shopBaseUrl."events/";
		$text = $this->httpClient->getUrlText($url);
		preg_match_all("#<p class='date'>(.+?)</p><p class='rose'>(.+?)</p>(.+?)</td>#sui", $text, $items, PREG_SET_ORDER);
		$cur_id = 1;
		foreach($items as $item)
		{
			$result_item = new ParserNews();
			$result_item->id = $cur_id++;
			$result_item->date = substr($item[1], 0, strpos($item[1], " ")).".";
			$result_item->date .= $month_names[substr($item[1], strpos($item[1], " ") + 1, strrpos($item[1], ",") - strpos($item[1], " ")-1)].".";
			$result_item->date .= substr($item[1], strrpos($item[1], ",") + 2);
			
			$result_item->header = $item[2];
			$result_item->urlShort = $url;
			$result_item->contentShort = $item[3];
			$result_item->contentShort = "1".$result_item->contentShort;
			if(strpos($result_item->contentShort, "<!--[if gte mso 9]><xml>") == 1)
				$result_item->contentShort = mb_substr($result_item->contentShort, strrpos($result_item->contentShort,"<![endif]-->") - mb_strlen("<![endif]-->") - 15);
			else
				$result_item->contentShort = substr($result_item->contentShort, 1);
			$base[] = $result_item;
		}
		return $this->saveNewsResult ($base); 
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText("http://www.kiraplastinina.ru/collections/");
		
		$pregMenu = '#<DIV class=menu>(.+?)<DIV style="HEIGHT: 40px">#sui';
		preg_match_all($pregMenu, $text, $menu, PREG_SET_ORDER);
		$menu = $menu[0][1];
		$pregMenu = '#<A href="(.+?)"#sui';
		preg_match_all($pregMenu, $menu, $menu, PREG_SET_ORDER);
		$col_count = 0;
		foreach($menu as $menu_elem)
		{
			$col_count ++;
			$url = $this->shopBaseUrl.substr($menu_elem[1], 1);
			$text = $this->httpClient->getUrlText($url);
			$collection = new ParserCollection();
			
			$pregTitle = "#<title>(.+?)</title>#sui";
			preg_match($pregTitle, $text, $title);
			$collection->id = $collection->name = $title[1];
			$pregDescr = "#<div style='padding-bottom: 10px;'>(.+?)</div>#sui";
			preg_match($pregDescr, $text, $descr);
			if(count($descr) > 0)
				$collection->descr = $descr[1];
			$collection->descr = str_replace("\r\n", "", $collection->descr);
			$collection->descr = str_replace("<br>", "\r\n", $collection->descr);
			$collection->descr = strip_tags($collection->descr);
			$collection->url = $url;
			
			$pregPictures = "#<table width=571 cellpadding=0 cellspacing=0 border=0 class=photo><tr>(.+?)</table>#sui";
			preg_match($pregPictures, $text, $table);
			$table = $table[1];
			if($col_count == 1)$pregPictures = '#<a id="thumb\d+" href="(.+?)"#sui';
			else $pregPictures = '#<img src="(.+?)"/>#sui';
			preg_match_all($pregPictures, $table, $images);
			$images = $images[1];
			foreach($images as $curImage)
			{
				$curImage = str_replace("prv", "pic", $curImage);
				preg_match('#.+/(.+?).jpg#sui', $curImage, $imageInfo);

				
				$item = new ParserItem();
				
				$image = new ParserImage();
				$item->id = $imageInfo[1];
				$image->url = $this->shopBaseUrl.substr($curImage, 1);
				$this->httpClient->getUrlBinary($image->url);
				$image->path = $this->httpClient->getLastCacheFile();
				$image->type = "jpg";
				
				$item->images[] = $image;
				$collection->items[] = $item;

			}
			$base[] = $collection;
		}

		return $this->saveItemsResult ($base);
	}
	
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText("http://www.kiraplastinina.ru/shopping/address/");
		$pregCountries = '#<select size="1" id="mySelect1" name="country" class="width_200">(.+?)</select>#sui';
		preg_match_all($pregCountries, $text, $result, PREG_SET_ORDER);
		$countries = $result[0][1];
		$pregCountries = '#<option value="(\d+)">(.+?)</option>#i';
		preg_match_all($pregCountries, $countries, $countries, PREG_SET_ORDER);
		foreach($countries as $country)
		{
			$text = $this->httpClient->getUrlText("http://www.kiraplastinina.ru/shopping/address/?country=".$country['1']);
			$pregCities = '#<select size="1" id="mySelect2" name="city" class="width_200">(.+?)</select>#sui';
			preg_match_all($pregCities, $text, $result, PREG_SET_ORDER);
			$cities = $result[0][1];
			$pregCities = '#<option value="(\d+)">(.+?)</option>#i';
			preg_match_all($pregCities, $cities, $cities, PREG_SET_ORDER);
			foreach($cities as $city)
			{
				$text = $this->httpClient->getUrlText("http://www.kiraplastinina.ru/shopping/address/?country=".$country['1']."&city=".$city[1]);
	
				$pregShop = "#<b class=title>.+?<br><br>(.+?)</div>#sui";
				preg_match_all($pregShop, $text, $result);
				$pregShop = "#(.+?)Телефон:(.+?)<BR>#sui";
				preg_match_all($pregShop, $result[1][0], $shops, PREG_SET_ORDER);
				foreach($shops as $shop)
				{
					$item = new ParserPhysical();
					$item->phone = trim($shop[2]);
					$item->address = $shop[1];
					$item->address = str_replace(array("<I>","</I>","<BR>", "<br>" ,"\r\n", "<br/>", "<BR/>"), array("","",", ", ", ", "", ", ", ", "),$item->address);
					$item->address = trim(substr($item->address, 0, strlen($item->address) - 2));
					$item->address = preg_replace ("/^[\s,]+/sui", "", $item->address);
					$item->address = $this->address($item->address);
					
					if($this->address_have_prefix($item->address))
					{
						$delimetr = "";
						
						if(mb_strpos($item->address, '",') !== false)$delimetr = '",';
						else if(mb_strpos($item->address, '" ,') !== false)$delimetr = '" ,';
						else if(mb_strpos($item->address, ',') !== false)$delimetr = ',';
						
						$pos = mb_strpos($item->address, $delimetr);
						$name = mb_substr($item->address, 0, $pos + 1);
						if($name[mb_strlen($name)] == ",")$name = substr($name, 0, -1);
						$item->address = mb_substr($item->address, $pos+mb_strlen($delimetr)). ", ".$name;
					}
					
					$item->city = $city[2];
					$base[] = $item;
				}
			}
		}
		return $this->savePhysicalResult ($base);
	}
}
