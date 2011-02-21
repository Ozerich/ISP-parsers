<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_monarch_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://www.monarch.ru/'; // Адрес главной страницы сайта 
	
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."news.php");
		preg_match_all('# <div id="news_tab">(.+?)<br /><a href="/(show_news.php\?id=(\d+)&page_num=1)">(.+?)</a></div>#sui', $text, $news, PREG_SET_ORDER);

		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->id = $news_value[3];
			$news_item->header = $this->txt($news_value[4]);
			$news_item->date = $news_value[1];
			$news_item->urlShort = $this->shopBaseUrl."news.php";
			$news_item->urlFull = $this->shopBaseUrl.$news_value[2];
			$news_item->contentShort = $news_value[4];
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			preg_match('#<div class="cont2_yellow">(.+?)</div>#sui', $text, $content);
			$news_item->contentFull = $content[1];
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}

	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."collections.php");
		preg_match('#<a style="color: \#000000; font-size: 13px;" href="(collections.php\?col_id=(\d+))">(.+?)</a#sui', $text, $collection_value);
		
		$collection = new ParserCollection();
		
		$collection->name = $collection_value[3];
		$collection->id = $collection_value[2];
		$collection->url = $this->shopBaseUrl.$collection_value[1];
		
		$text = $this->httpClient->getUrlText($collection->url);
		preg_match_all('#\&nbsp;\&nbsp;\&nbsp;\&nbsp;<a href="(.+?)">(.+?)</a>#sui', $text,$categories,PREG_SET_ORDER);
		
		foreach($categories as $category)
		{
			$category_name = $category[2];
			$category_url = $this->shopBaseUrl.$category[1];
			$page = 1;
			while($page < 500)
			{
				$text = $this->httpClient->getUrlText($category_url."&page_num=$page");
				
				preg_match_all('#<div style="padding-top: 10px; width: 204px;"><a href="/(show_good.php\?id=(\d+).+?)".+?<strong><em>(.+?)</em></strong>.+?Артикул: (.+?)</em>#sui', $text, $items, PREG_SET_ORDER);
				if(!$items)break;
				foreach($items as $item_value)
				{
					$item = new ParserItem();
					$item->id = $item_value[2];
					$item->url = $this->shopBaseUrl.$item_value[1];
					$item->articul = $item_value[4];
					$item->name = $item_value[3];
					$item->categ = $category_name;
			
					$text = $this->httpClient->getUrlText($item->url);
					
					preg_match('#<strong>Цвет:</strong>(.+?)<#sui', $text, $color);
					if($color)$item->colors[] = $this->txt($color[1]);

					preg_match('#<strong>Материал верха:</strong>(.+?)<#sui', $text, $material);
					if($material)$item->material = $this->txt($material[1]);
					
					preg_match('#<strong>Торговая марка:</strong>(.+?)<#sui', $text, $brand);
					if($brand)$item->brand = $this->txt($brand[1]);
					
					preg_match('#<div id="cleft" style="height: 30px;"></div>(.+?)</div>#sui', $text, $descr);
					if($descr)$item->descr = $this->txt(str_replace("<br>","\n ",$descr[1]));
					
					preg_match('#<img src="/(big/(.+?).jpg)"#sui', $text, $image_item);
					$image = new ParserImage();
					
					$image->id = $image_item[2];
					$image->url = $this->shopBaseUrl.$image_item[1];
					$this->httpClient->getUrlBinary($this->urlencode_partial($image->url));
					$image->path = $this->httpClient->getLastCacheFile();
					$image->type = "jpg";
					
					$item->images[] = $image;
					
					
					$collection->items[] = $item;
				}
				
				$page++;
			}
		}
		
		$base[] = $collection;
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shops.php");
		preg_match_all('#<a href="/(show_shops.php\?city_id=\d+)">(.+?)</a>#sui', $text, $cities, PREG_SET_ORDER);
		foreach($cities as $city)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
			preg_match_all('#<p><span>(.+?)(?:<br />Тел.: (.+?))*</span></p>#sui', $text, $shops, PREG_SET_ORDER);
			foreach($shops as $shop_value)
			{
				$shop = new ParserPhysical();
				
				$shop->city = $city[2];
				$shop->address = $this->address($shop_value[1]);
				$shop->address = str_replace('(', '', $shop->address);
				if(isset($shop_value[2]))$shop->phone = $shop_value[2];
				
				$base[] = $shop;
			}
		}
		
		return $this->savePhysicalResult($base);
	}
}
