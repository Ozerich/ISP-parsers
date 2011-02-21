<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_pilgrimdk_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://pilgrim-dk.ru/'; // Адрес главной страницы сайта 
	
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		$base = array();
		
		$url = $this->shopBaseUrl."news/index.php";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('#<p class="news-item">.+?<a href="/(news/\?ELEMENT_ID=(\d+))"><b>(.+?)</b></a><br />(.*?)</p>#sui', $text, $news, PREG_SET_ORDER);
		

		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->id = $news_value[2];
			$news_item->urlShort = $url;
			$news_item->urlFull = $this->shopBaseUrl.$news_value[1];
			$news_item->header = $this->txt($news_value[3]);
			$news_item->contentShort = trim($news_value[4]);
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			preg_match('#</h3>\s*(.+?)<div style="clear:both"></div>#sui', $text, $descr);
			$news_item->contentFull = trim($descr[1]);
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}
	

	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."collections/");
		preg_match('#<h4 align="center"><b>(.+?)</b></h4>#sui', $text, $name);
		
		$collection = new ParserCollection();
		
		$collection->name = $name[1];
		$collection->url = $this->shopBaseUrl."collections/";
		
		preg_match('#<ul class="left-menu">(.+?)</ul>#sui', $text, $menu);
		preg_match_all('#<li><a href="/(.+?)".*?>(.+?)</a></li>#sui', $menu[1], $categories,PREG_SET_ORDER);

		foreach($categories as $category)
		{
			//$category[1] = "collections/independence.php";
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]);
			preg_match_all('#<a href="/collections/watches/\?PAGEN_1=(\d+)">#sui', $text, $pages, PREG_SET_ORDER);


			if(!$pages)$pages_count = 1;
			else $pages_count = count($pages) - 1;
			$page = 1;


			while($page <= $pages_count)
			{
				$url = $this->shopBaseUrl.$category[1]."?PAGEN_1=$page";
				$text = $this->httpClient->getUrlText($url);
				

				preg_match_all('#(collections(?:\w+|/)+?detail.+?ELEMENT_ID=(\d+))"#sui', $text, $items, PREG_SET_ORDER);
				foreach($items as $item_)
				{
					$item = new ParserItem();
					
					$item->id = $item_[2];
					$item->url = $this->shopBaseUrl.$item_[1];

					$text = $this->httpClient->getUrlText($item->url);
					
					preg_match('#<b>№:</b>&nbsp;\s*(\d+)#sui', $text, $articul);
					if($articul)$item->articul = $articul[1];
					
					preg_match('#<td width="100%" align="center" valign="top" style="background-color:\#FFFFFF;"><b>(.+?)<b#sui', $text, $name);

					if($name)$item->name = $this->txt($name[1]);
					
					preg_match('#<b>Описание продукта:</b>&nbsp;(.+?)<#sui', $text, $descr);
					if($descr)$item->descr = $descr[1];
					
					preg_match('#<img border="0" src="/(.+?)"#sui', $text, $image_item);
					
					$image = new ParserImage();
					
					$item->images[] = $image;
					
					$image->url = $this->shopBaseUrl.$image_item[1];
		
					$this->httpClient->getUrlBinary($this->urlencode_partial($image->url));

					$image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, mb_strrpos($image->url, ".") - mb_strrpos($image->url, "/")-1);

					$image->path = $this->httpClient->getLastCacheFile();
					$image->type = "jpg";
					
					$item->categ = $category[2];
					
					$collection->items[] = $item;
				}
				//print_r($collection);exit();
				$page++;
			}
		}
		
		
		$base[] = $collection;

		
		return $this->saveItemsResult ($base);
	}
	
	private function parse_shop($url, $shop)
	{
		//$url = "http://www.pilgrim-dk.ru/shopfinder/detail.php?ELEMENT_ID=787";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match('#<div class="news-detail">(.+?)<div style="clear:both"></div>#sui', $text, $info);
		$text = $info[1];
		
		preg_match('#<h4 align="center"><b>(.+?)</b></h4>\s*<p>(.+?)<br>\s*Время работы: (.+?)</p>#sui', $text, $info);

		if($info)
		{
			$shop->address = $this->txt($info[2]);
			$shop->timetable = $info[3];
		}
		else
		{
			preg_match('#(.+?)<br />(.+?)<br />\s*Время работы:* (.+)#sui', $text, $info);
			if($info)
			{
				$shop->address = $this->txt($info[2].", ".$info[1]);
				$shop->timetable = $info[3];
			}
			else
			{
				preg_match('#<h4 align="center"><b>(.+?)</b></h4>\s*<p>(.+?)<br>\s*(.+?)</p>#sui', $text, $info);
				if($info)
				{
					$shop->address = $this->txt($info[2].", ".$info[1]);
					$shop->timetable = $info[3];
				}	
			}
		}
		$shop->address = $this->address($this->address($shop->address));
		preg_match('#(тел.: ((?:\d|\s)+))#sui', $shop->address, $phone);
		if($phone)
		{
			$shop->phone = $phone[2];
			$shop->address = str_replace(', '.$phone[1], "", $shop->address);
		}
		
		if($shop->address[0] == '"')
		{
			$shop->address = mb_substr($shop->address, 1);
			$name = mb_substr($shop->address, 0, mb_strpos($shop->address, '"'));

			$shop->address = mb_substr($shop->address, mb_strlen($name) + 3).", ".'"'.$name.'"';

		}

		$shop->timetable = $this->txt($shop->timetable);
		//print_r($shop);exit();
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shopfinder/index.php");
		
		preg_match_all('#<div class="news-list">(.+?)</div>\s*</div>#sui', $text, $shops_text, PREG_SET_ORDER);
		foreach($shops_text as $shop_text)
		{
				preg_match_all('#<a href="/(shopfinder/detail.php\?ELEMENT_ID=(\d+))"><b>(.+?)</b></a>#sui', $shop_text[1], $shops_items,PREG_SET_ORDER);
				foreach($shops_items as $shop_value)
				{
					$shop = new ParserPhysical();
					
					$shop->id = $shop_value[2];
					$shop->city = "Москва";
					
					$this->parse_shop($this->shopBaseUrl.$shop_value[1], $shop);
					
					$base[] = $shop;
				}
					
			}
		
		preg_match('#<table width="80\&\#37;" align="center">(.+?)</table>#sui', $text, $table);
		preg_match_all('#<a href="/(shopfinder/detail.php\?ELEMENT_ID=(\d+))"><b>(.+?)</b></a>#sui', $table[1], $shops_items,PREG_SET_ORDER);
			foreach($shops_items as $shop_value)
			{
				$shop = new ParserPhysical();
					
				$shop->id = $shop_value[2];
				$shop->city = trim(mb_substr($shop_value[3], 2));
					
				$this->parse_shop($this->shopBaseUrl.$shop_value[1], $shop);
					
				$base[] = $shop;
			}
			
		return $this->savePhysicalResult($base);
	}
}
