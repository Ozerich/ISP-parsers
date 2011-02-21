<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';

class ISP_douglas_ru extends ItemsSiteParser_Drakon
{
	protected $shopBaseUrl = 'http://www.douglas.ru'; // Адрес главной страницы сайта 
	
	public function loadNews() 
	{ 
		$rasdels = array("http://www.douglas.ru/gifts/by_with_gift/", "http://www.douglas.ru/gifts/actions/","http://www.douglas.ru/gifts/competition/");
		foreach($rasdels as $base_url)
		{
			$cur_page = 1;
			while(1)
			{
				$url = $base_url."?PAGEN_1=$cur_page";
				$text = $this->httpClient->getUrlText($url);
				
				preg_match('#<span>(\d+)</span>#sui', $text, $page);
				if(!$page)
				{
					if($cur_page > 1)
						break;
				}
				else
				{
					$page = $page[1];
					if($page == '1' && $cur_page > 1)
						break;
				}
				preg_match_all('#<h1><a href="(.+?)">(.+?)</a></h1>#sui', $text, $items,PREG_SET_ORDER);

				foreach($items as $item)
				{
					$result_item = new ParserNews();
					$result_item->header = $item[2];
					preg_match("#id=(\d+)#", $item[1], $id);
					$result_item->id = $id[1];
					$result_item->urlShort = $url;
					$result_item->urlFull = $this->shopBaseUrl.$item[1];
					
					$text = $this->httpClient->getUrlText($result_item->urlShort);
					preg_match('#<!--анонс-->\s+<td>(.+?)</td>#sui', $text, $contentShort);
					$result_item->contentShort = $contentShort[1];
					$text = $this->httpClient->getUrlText($result_item->urlFull);
					preg_match('#<tr valign="top">(.+?)</tr>#sui', $text, $content);
					if(!$content)preg_match('#<div style="font-size: 12px;">(.+?)</div>#sui', $text, $content);
					$result_item->contentFull = substr(trim($content[1]), 4, strlen($content[1]) - 12);
					
					$base[] = $result_item;
				}
				
				$cur_page++;
			}
			
		}
		
		return $this->saveNewsResult($base);
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$collection = new ParserCollection();
		$collection->name = "Каталог товаров";
		$collection->id = "catalog";
		
		$url = $this->shopBaseUrl."/catalog/";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('#<a href="(/catalog/(.+?))">(.+?)</a><br>#sui', $text, $sections, PREG_SET_ORDER);
		for($i = 1; $i < count($sections); $i++)
		{
			if($sections[$i][2] == "allbrands/")continue;
			$cur_categ = array($sections[$i][3]);
			$url = $this->shopBaseUrl.$sections[$i][1];
			$text = $this->httpClient->getUrlText($url);
			$modes = array("new" => "Новинки", "ex" => "Эксклюзив", "top"=>"Топ-10");
			foreach($modes as $mode=>$cat_name)
			{
				$suburl = $url."index.php?show=$mode";
				$cur_categ[1] = $cat_name;
				$page = 0;
				while(true)
				{
					if($page>10)break;
					$pageurl = $suburl."&page=$page";
					$page_text = $this->httpClient->getUrlText($pageurl);
					preg_match_all('#<td class="img"><img src="(.+?)"(.+?)</td>\s*<td valign="middle"><a href="(/catalog/detail.php\?id=(.+?))">(.+?)</a>(.+?)</td>#sui', $page_text, $items, PREG_SET_ORDER);
					if(count($items) == 0)
						break;
					foreach($items as $cur_item)
					{
						$item = new ParserItem();
						$item->id = $cur_item[4];
						$item->url = $this->shopBaseUrl.$cur_item[3];
						$item->name = $cur_item[5];
						$item->descr = strip_tags($cur_item[6]);
						$item->categ = $cur_categ;
						$image = new ParserImage();
						$image->url = urlencode_partial($this->shopBaseUrl.$cur_item[1]);
						$image->type = "jpg";
						$this->httpClient->getUrlBinary($image->url);
						$image->path = $this->httpClient->getLastCacheFile();
						$image->fname = substr($image->url, strrpos($image->url, "/") + 1);
						$item->images[] = $image;
						$collection->items[] = $item;
					}
					$page++;
				}
			}
		}
		$base[] = $collection;
		return $this->saveItemsResult ($base);
	}
	
	private function loadFileData($url, $type)
	{
		$result = array();
		$text = $this->httpClient->getUrlText($url);
		preg_match_all('#<description>(.+?)</description>#sui', $text, $shops, PREG_SET_ORDER);
		foreach($shops as $shop)
		{
			preg_match_all('#(.+?)<br />#sui', $shop[1], $curshop);
			foreach($curshop[1] as $ind=>$value)
				$curshop[1][$ind] = htmlspecialchars_decode(strip_tags($value));
			$result['info'][] = $curshop[1];
		}
		if($type == "regions")
			preg_match_all('#link="/shops/.*?/detail.php\?region_id=(\d+)"#sui', $text, $shops, PREG_SET_ORDER);
		else if($type == "moskow")
			preg_match_all('#link="/shops/detail.php\?shop_id=(\d+)">#sui', $text, $shops, PREG_SET_ORDER);
		foreach($shops as $shop)
			$result['id'][] = $shop[1];
		return $result;
	}
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$url = $this->shopBaseUrl."/shops/regions/objects.xml";
		$regionsShops = $this->loadFileData($url, "regions");
		$shopsInfo = $regionsShops['info'];
		$ids = $regionsShops['id'];
		$count = 0;
		foreach($shopsInfo as $data)
		{
			$cur_id = $ids[$count++];
			for($i = 1; $i < count($data); $i+=3)
			{
				$item = new ParserPhysical();
				$item->id = $cur_id;
				$item->city = strpos($data[$i], ",") ? substr($data[$i], 0, strpos($data[$i], ",")): $data[$i];
				preg_match('#(.+?)тел.\s(.+)#sui', $data[$i + 1], $info);
				$item->address = substr($info[1], 0, strlen($info[1]) - 1);
				$item->phone = $info[2];
				$item->timetable = $data[$i + 2];
				$base[] = $item;
			}
		}
		$url = $this->shopBaseUrl."/shops/moskow/objects.xml";
		$moscowShops = $this->loadFileData($url, "moskow");
		$shopsInfo = $moscowShops['info'];
		$ids = $moscowShops['id'];
		$count = 0;
		foreach($shopsInfo as $shop)
		{
			$item = new ParserPhysical();
			$item->city = "Москва";
			$item->id = $ids[$count++];
			preg_match('#(.+?)тел.\s(.+)#sui', $shop[0], $info);
			if($info)
			{
				$item->address = substr($info[1], 0, strlen($info[1]) - 1);
				$item->phone = $info[2];
			}
			$item->timetable = $shop[1];
			$base[] = $item;
		}
		return $this->savePhysicalResult ($base);
	}
}
