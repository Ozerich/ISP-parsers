<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';

class ISP_finn_flare_ru extends ItemsSiteParser_Drakon
{
	protected $shopBaseUrl = 'http://finn-flare.ru'; // Адрес главной страницы сайта 
	
	public function loadNews() 
	{ 
		$month_names = array("Января" => 1,"Февраля" => 2,"Марта" => 3,"Апреля" => 4,"Мая" => 5,"Июня" => 6,"Июля" => 7,"Августа" => 8,"Сентября" => 9,"Октября" => 10,"Ноября" => 11,"Декабря" => 12);
		$news_url = $this->shopBaseUrl."/news/news-line/";
		$url = $news_url;
		$text = $this->httpClient->getUrlText($url);
		preg_match_all('#<td width="100%">\s+<a href="(.+?)" class="u uppercase gray small">(.+?)</a><span class="uppercase gray small">&nbsp;&nbsp;|&nbsp;&nbsp;</span><a href="(.+?)" class="u uppercase gray small"><b>(.+?)</a>(.+?)</td>#sui', $text, $items,PREG_SET_ORDER);
		for($i = 0; $i < count($items); $i+=2)
		{
			$date = str_replace("&nbsp;", " ", $items[$i][2]);
			$header = $items[$i + 1][4];
				
			$result_item = new ParserNews();
			$result_item->header = $header;
			$result_item->urlShort = $url;
			$result_item->urlFull = $this->shopBaseUrl.$items[$i][1];
			$result_item->id = substr($result_item->urlFull, strrpos($result_item->urlFull, "/") + 1);
			$result_item->contentShort = substr($date, 0, strpos($date, " ")).".".$month_names[substr($date, strpos($date, " ") + 1)];
			$result_item->date = $result_item->contentShort;
				
			$text = $this->httpClient->getUrltext($result_item->urlFull);
			preg_match('#<div>&nbsp;</div>\s+<table border="0" cellpadding="0" cellspacing="0" width="100%">(.+?)<div align="right">(.+?)</div>#sui', $text, $content);
			if($content)
			{
				$result_item->contentFull = $content[1];
				$result_item->date .= ".".substr($content[2], strrpos($content[2], ";") + 1);
			}
			$base[] = $result_item;
		}
		return $this->saveNewsResult ($base);
	}
	
	private function parseItem($url)
	{
		$text = $this->httpClient->getUrlText($url);
		$item = new ParserItem();
		$item->id = substr($url, strrpos($url, "/") + 1);
		$item->url = $url;
		preg_match('#<td>\s*<b>(\D+?)(\n|<)#sui', $text, $name);
		$item->name = $name[1];
		preg_match('#Арт:\s(.+?)\s#sui', $text, $art);
		$item->articul = $art[1];
		
		preg_match('#good_price = {(.+?)}}}#sui', $text, $curprice);
		preg_match_all("#'\d+':'((\d|\s)+.00)&nbsp;руб.'#sui", $curprice[1], $price, PREG_SET_ORDER);
		if(count($price) > 5)
		{
			$item->price = str_replace(" ", "", $price[0][1]);
			$item->bStock = 1;
			preg_match('#good_price_old = {(.+?)}}}#sui', $text, $curprice);
			preg_match("#'\d+':'((\d|\s)+.00)&nbsp;руб.'#sui", $curprice[1], $newprice);
			if($newprice)
			{
				$oldprice = $item->price;
				$newprice = str_replace(" ", "", $newprice[1]);
				$discount = $newprice - $oldprice;
				$item->discount = intval($discount / ($newprice / 100));
				$item->price = $newprice;
			}
		}
		else
			$item->bStock = 0;


		
		
		preg_match("#(материал|материалы):(.+?)<br />#sui", $text, $materials);
		if($materials)
			$item->structure = strip_tags($this->deletebr($materials[2]));
		if($item->structure[0] == ",")$item->structure = substr($item->structure, 1);
		preg_match('#<select(.+?)>(.+?)</select>#sui', $text, $sizes);
		if($sizes)
		{
			preg_match_all("#<option(.+?)>(.+?)</option>#sui", $sizes[2], $sizes, PREG_SET_ORDER);
			foreach($sizes as $size)
				$item->sizes[] = $size[2];
		}
		preg_match("#<td>Цвет:&nbsp;&nbsp;</td>\s*<td>(.+?)</td>#sui", $text, $colors);
		preg_match_all("#(.+?)background: (.+?);(.+?)#sui", $colors[1], $colors, PREG_SET_ORDER);
		foreach($colors as $color)
			$item->colors[] = $color[2];
		preg_match_all("#document.mainpict.src='(.+?)'#sui", $text, $picture, PREG_SET_ORDER);
		$pictures = array();
		foreach($picture as $pic)
			$pictures[] = $pic[1];
		preg_match('#\#D7E0E8">(.+?)<img(.+?)src="(.+?)"(.+?)name="mainpict">#sui', $text, $main_pic);
		if(!$pictures)
		{
			preg_match('#<img src="(/res/(.+?))"#sui', $text, $main_pic);
			if($main_pic)
				$pictures[] = $main_pic[1];
		}
		foreach($pictures as $picture)
		{
			$image = new ParserImage();
			$image->url = $this->shopBaseUrl.$picture;
			$this->httpClient->getUrlBinary($image->url);
			$image->path = $this->httpClient->getLastCacheFile();
			$image->type = "jpg";
			$item->images[] = $image;
		}

		return $item;
	}
	
	
	public function loadItems () 
	{
		$base = array ();
		
		$url = $this->shopBaseUrl."/collections/";
		$text = $this->httpClient->getUrlText($url);
		
		$collections = array();
		$collections[] = "";
		
		$pregCollection = '#<td><div><a href="/collections/(\S+?)" class="small uppercase">.*?коллекция.*?</a></div></td>#sui';
		preg_match_all($pregCollection, $text, $collections_, PREG_SET_ORDER);
		foreach($collections_ as $col)
			$collections[] = $col[1];
		foreach($collections as $col)
		{
			$collection = new ParserCollection();
			$collection->id = ($col != "") ? $col : "ff_autumn_2010";
			$collection->url = $this->shopBaseUrl."/collections/".$col;
			$text = $this->httpClient->getUrlText($collection->url);
			preg_match('#<td nowrap>\s*<h1>\s*(.+?)\s*</h1>\s*</td>#sui', $text, $name);
			$collection->name = $name[1];
			preg_match('#<!-- Body -->\s*(.+?)<div>&nbsp;</div><div>#sui', $text, $desc);
			if($desc)
			{
				preg_match('#<!-- Body -->\s*(.+?)$#sui', $desc[1], $desc);
				if($desc)
				{
					$collection->descr = $this->deletebr($desc[1]);
					if($collection->descr[0] == ",")$collection->descr = substr($collection->descr, 1);
				}
			}
		
			preg_match_all('#<a href="(\S+?)">подробнее</a>#sui', $text, $categories, PREG_SET_ORDER);
			foreach($categories as $category)
			{
				$url = $this->shopBaseUrl.$category[1];
				$category_text = $this->httpClient->getUrlText($url);
				preg_match('#<td nowrap>\s*<h1>\s*(.+?)\s*</h1>\s*</td>#sui', $category_text, $name);
				$category = array(strip_tags($name[1]));
				preg_match_all('#<a href="(\S+?)">подробнее</a>#sui', $category_text, $categories_in, PREG_SET_ORDER);
				if(!$categories_in)
				{
					$categories_in = array(array("",substr($url, strlen($this->shopBaseUrl))));
					$category = array();
				}
				foreach($categories_in as $category_in)
				{
					$url = $this->shopBaseUrl.$category_in[1];
					$category_in_text = $this->httpClient->getUrlText($url);
					preg_match('#<td nowrap>\s*<h1>\s*(.+?)\s*<(.+?)</td>#sui', $category_in_text, $name);
					$category[1] = strip_tags($name[1]);

					$page = 1;
					while(1)
					{
						$cururl = $url."?pg=".$page;
						$page_text = $this->httpClient->getUrlText($cururl);
						preg_match_all('#<div>&nbsp;</div>\s*<a href="(\S+?)" class="uppercase">(.+?)</a>#sui', $page_text, $items, PREG_SET_ORDER);
						if(count($items) == 0)
							break;
						$page++;
						foreach($items as $item)
						{
							$itemurl = $this->shopBaseUrl.$item[1];
							$item = $this->parseItem($itemurl);
							$item->categ = $category;
							$collection->items[] = $item;
						}
					}
				}
			}
			$base[] = $collection;
		}
		return $this->saveItemsResult ($base);
	}
	
	public function deletebr($str)
	{
		$str = str_replace(array("\r\n", "<br>", "<BR>"), array("", ", ", ", "), $str);
		return $str;
	}
	
	private function parseShop($text)
	{
		$shop = new ParserPhysical();
		preg_match('#<font color=red>Адрес:</font></td>\s*<td valign=top>(.+?)</td>#sui', $text,$addr);
		$addr = ($addr) ? $addr[1] : "";
		$phone = "";
		

		if(strpos($addr,"Тел."))
		{
			preg_match('#Тел.\s*(.+?)\n#sui', $addr, $phone);
			$addr = str_replace($phone[0], "", $addr);
			$phone = $phone[1];
			$addr = $this->deletebr($addr);
			$phone = $phone;
		}

		preg_match('#(Телефон|Телефон магазина|Тел:):\s*(.+?)($|\n)#sui', $addr, $phone_);
		if($phone_)
		{
			$addr = str_replace($phone_[0], "", $addr);
			$phone = $phone_[1];
			$addr = $this->deletebr($addr);
		}
		
		$shop->address = $this->deletebr($addr);
		$shop->phone = $phone;

		preg_match('#<font color=red>Описание:</font></td>\s*<td valign=top>(.+?)</td>#sui', $text,$desc);
		$desc = ($desc) ? $desc[1] : "";

		preg_match('#(Режим работы|Часы работы):\s*(.+?),#sui', $this->deletebr($desc), $times);
		$times = ($times) ? $times = $times[2] : "";
	
		
		preg_match('#Телефон:\s*(.+?)(\n|<)#sui', $desc, $phone);
		$phone = $phone ? $phone[1] : "";
		if($phone != "")$shop->phone = $phone;
		

		
		$shop->timetable = $times;
		
		preg_match('#<font color=red>Дискаунтер</font></td>\s*<td valign=top>(.+?)</td>#sui', $text,$disc);
		$disc = $disc ? $disc[1] : "";
		if($disc != "")
			$shop->b_stock = ($disc == "НЕТ") ? 0 : 1;
		
		if($shop->phone != "" && $shop->phone[0] == ":")$shop->phone = substr($shop->phone, 1);
		if($shop->address != "" && $shop->address[0] == ",")$shop->address = substr($shop->phone, 1);
		return $shop;
		
	}
	
	private function parseShopPage($url)
	{
		$result = array();
		$text = $this->httpClient->getUrlText($url);
		$shop = new ParserPhysical();
		
		preg_match('#<td nowrap><h1>(.+?)</h1></td>#sui', $text, $name);
		$city = $name[1];
		if($city == "Магазины в Санкт-Петербурге")
			$city = "Санкт-Петербург";
		else if($city == "Магазины в Москве")
			$city = "Москва";
		
		preg_match_all("#<h5>(.+?)</table>#sui", $text, $shops, PREG_SET_ORDER);
		if(count($shops) > 1)
		{
			foreach($shops as $shop)
			{
				$shop = $this->parseShop($shop[1]);
				$shop->city = $city;
				$result[] = $shop;
			}
		}
		else
		{
			$shop = $this->parseShop($text);
			$shop->city = $city;
			$result[] = $shop;
		}
		
		return $result;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array();
		
		$url = $this->shopBaseUrl."/stores/";
		$text = $this->httpClient->getUrlText($url);
		$pregSubitems = '#<td><div><a href=\"(\S+?)\" class=\"small uppercase\">(.*?)</a></div></td>#sui';
		preg_match_all($pregSubitems, $text, $subitems, PREG_SET_ORDER);
		foreach($subitems as $subitem)
		{
			$url = $this->shopBaseUrl.$subitem[1];
			$text = $this->httpClient->getUrlText($url);
			$country = (strpos($text, "/inc/_shops_stores") != false) ? true : false;
			$bigcity = ($country && (strpos($text, "подробнее") != false)) ? true : false;
			if($bigcity)
			{
				$pregShops = '#<a href=(\S+?)>подробнее</a>#sui';
				preg_match_all($pregShops, $text, $shops, PREG_SET_ORDER);
				foreach($shops as $shop)
					$result[] = $this->parseShopPage($this->shopBaseUrl.$shop[1]);
			}
			else if($country)
			{
				$pregShopsArea = "#<td valign='top' width=140 >(.+?)</td>#sui";
				preg_match_all($pregShopsArea, $text, $shopsArea);
				foreach($shopsArea as $shopArea)
				{
					$pregShop = "#<a href='(.+?)'>(.+?)</a><hr>#sui";
					preg_match_all($pregShop, $text, $shops, PREG_SET_ORDER);
					foreach($shops as $shop)
						$result[] = $this->parseShopPage($this->shopBaseUrl.$shop[1]);
				}
			}
			else 
				$result[] = $this->parseShopPage($url);
			foreach($result as $item)
				foreach($item as $subitem)
					$base[] = $subitem;
			$result = array();

		}
		return $this->savePhysicalResult ($base);
	}
}
