<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';


class ISP_camelot_ru extends ItemsSiteParser_Drakon
{
	protected $shopBaseUrl = 'http://www.camelot.ru'; // Адрес главной страницы сайта 
	
	function str($text)
	{
		$result = trim(htmlspecialchars_decode(strip_tags($text)));
		$result = str_replace(array("&quot;","&laquo;","&raquo;", "&nbsp;", "\n"), array("\"", "«", "»", " ", ""), $result);
		return $result;
	}
	
	private function parseItem($url)
	{
		$result = new ParserItem();
		$text = $this->httpClient->getUrlText($url);
		preg_match("#наименование:(.+?)<br>#sui", $text, $name);
		$result->name = $this->str($name[1]);
		preg_match("#пол: (.+?)<br>Brand: (.+?)</div>#sui", $text, $info);
		$result->categ = $info[1];
		$result->brand = $info[2];
		
		preg_match("#url\((.+?)\)#sui", $text, $image_name);
		$image = new ParserImage();
		$image->url = $image_name[1];
		$this->httpClient->getUrlBinary($image->url);
		$image->path = $this->httpClient->getLastCacheFile();
		preg_match("#/((\d+)\.GIF)#sui",$image->url, $image_name);
		$image->id = $image_name[2];
		$image->fname = $image_name[1];
		$image->type = "gif";
		$result->images[] = $image;
		$result->url = $url;
		
		return $result;
	}
	
	private function parseItems($section_url)
	{
		$result = array();
		$page = 1;
		while(true)
		{
			$url = $section_url."&page=$page";
			$text = $this->httpClient->getUrlText($url);
			preg_match_all('#<a href="(\?id=(\d+))(.*?)>(.+?)</a>#sui', $text, $items, PREG_SET_ORDER);
			if(!$items)break;
			
			for($i = 0; $i < count($items); $i+=2)
			{
				$item = $items[$i];
				$url = $this->shopBaseUrl."/catalog/".$item[1];
				$res_item = $this->parseItem($url);
				$res_item->id = $item[2];
				$result[] = $res_item;
			}
			$page++;
		}
		
		
		return $result;
	}
	
	public function loadItems () 
	{
		$base = array ();
		$url = $this->shopBaseUrl."/catalog";
		$text = $this->httpClient->getUrlText($url);
		$collection = new ParserCollection();
		
		preg_match("#<h3>Коллекция:(.+?)</h3>#sui", $text, $name);
		$collection->name = $this->str($name[1]);
		$collection->url = $url;
		
		preg_match_all('#<div class="title_2">\s*<span class="title_bg_link" style="padding-left:20px;">(.+?)</span></div>\s*
\s*<ul>\s*
<li><a href="(.+?)">(.+?)</a></li>\s*
<li><a href="(.+?)">(.+?)</a></li>\s*
</ul>#sui', $text, $sections, PREG_SET_ORDER);
		
		foreach($sections as $section)
		{
			$category = array($section[1]);
			$category[1] = $section[3];
			$items = $this->parseItems($url."/".$section[2]);
			foreach($items as $item)
				$collection->items[] = $item;
			$category[1] = $section[5];
			$items = $this->parseItems($url."/".$section[4]);
			foreach($items as $item)
				$collection->items[] = $item;
		}
		
		$base[] = $collection;

		return $this->saveItemsResult ($base);
	}
	

	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$url = $this->shopBaseUrl."/shop-address";
		$text = $this->httpClient->getUrlText($url);
		preg_match_all("#<li id=\"n\"><a href='(.+?)' id=\"u\">(.+?)</a></li>#sui", $text, $cities, PREG_SET_ORDER);
		$items = array();
		foreach($cities as $city)
		{
			$url = $this->shopBaseUrl.$city[1];
			$text = $this->httpClient->getUrlText($url);
			
			preg_match("#<td colspan='3'>\s*<div style='margin-left: 10px;'>(.+?)</div>#sui", $text, $content);
			if(!$content)continue;
			$content=$content[1];
			
			preg_match_all('#<font face=\\\"Calibri\\\">(.+?)</font>#sui', $content, $items_, PREG_SET_ORDER);
			if(!$items_) preg_match_all('#<font face=\"Calibri\">(.+?)</font>#sui', $content, $items_, PREG_SET_ORDER);
			if($items_)
				foreach($items_ as $item)
					$items[]['address'] = $this->str($item[1]);

			$found = false;
			if(!$items_)
			{
				preg_match_all("#(.+?)<br />\s*<br />#sui", $content, $temp, PREG_SET_ORDER);
				foreach($temp as $item)
				{
					$item = $item[1];
					preg_match("#^(.+?)</p>\s*<p>(.+?)</p>#sui", $item, $item);
					$newitem['address'] = $this->str($item[1]);
					$newitem['phone'] = $this->str($item[2]);
					if(strpos($newitem['phone'], "Тел.:"))
						$newitem['phone'] = substr($newitem['phone'], strpos($newitem['phone'], "Тел.:") + strlen("Тел.:"));
					$items[] = $newitem;
					$found = true;
				}
			}
			if(!$found)
			{
				$content = substr($content, strlen("<p>"), strlen($content) - strlen("</p></p>"));
				preg_match_all("#<p>(.+?)</p>#sui", $content, $temp, PREG_SET_ORDER);
				foreach($temp as $item)
				{
					$item = $this->str(trim($item[1]));
					if(strlen($item) < 2)continue;
					$items[]['address'] = $this->str($item);
				}
			}
		}
		foreach($items as $item)
		{
			$result_item = new ParserPhysical();
			$address = trim($item['address']);
			$city = "";
			
			if(isset($item['phone']))
				$result_item->phone = trim($item['phone']);
			if(substr($address, 0, strlen("Московская обл., ")) == "Московская обл., ")
				$address = substr($address, strlen("Московская обл., "));
			if(substr($address, 0, strlen("г.")) == "г.")
				$address = trim(substr($address, strlen("г.")));
			if(strpos($address, "("))
			{
				$city = substr($address, strpos($address, "(") + 1, strpos($address, ")") - strpos($address, "(") - 1);
				$result_item->address = substr($address, 0, strlen($city) + 1);
			}
			
			$city = ($city != "") ? $city : substr($address, 0, strpos($address, " "));
			$result_item->address = ($result_item->address == "") ? substr($address, strlen($city)) : $result_item->address;
			if($city[strlen($city) - 1] == ",")
				$city = substr($city, 0, strlen($city) - 1);
			if($city == "Ленинский")
			{
				$city = "Ленинский р-н";
				$result_item->address = substr($result_item->address, 8);
			}
			$result_item->city = $city;
			if($city == 'ул')continue;
			$base[] = $result_item;
		}
		return $this->savePhysicalResult ($base);
	}


	public function loadNews()
	{
		$base = array();
		
		$url = $this->shopBaseUrl."/news";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all("#<a href='(\S+?)' class=\"link_more\">подробнее ...</a>#sui", $text, $more_links, PREG_SET_ORDER);
		foreach($more_links as $more_link)
		{
			$url = $this->shopBaseUrl.$more_link[1];
			$text = $this->httpClient->getUrlText($url);
			
			$news = new ParserNews();
			
			preg_match('#(.+?)(\d+)\.html(.*?)#sui', $url, $id);
			$news->id = $id[2];
			
			$header = null;
			preg_match('#<span style="font-size:16px;">(.+?)</span></p>#sui', $text, $header);
			if(!$header)preg_match("#<td class='title_bg_right'>(.+?)</td>#sui", $text, $header);
		
			$news->header = $this->str($header[1]);
			if(strpos($url, '?'))
				$url = substr($url, 0, strpos($url, '?'));
			$news->urlShort = $this->shopBaseUrl."/news";
			$news->urlFull = $url;
			
			preg_match('#\<\!-- block catalog --\>(.+)\<\!-- block catalog --\>#sui', $text, $content);
			$news->contentFull = $content[1];
			
			$base[] = $news;
		}
			
		
		return $this->saveNewsResult($base);
	}
}