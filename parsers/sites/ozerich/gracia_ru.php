<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_gracia_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://gracia.ru/'; // Адрес главной страницы сайта 
	
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		$base = array();
	
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."news/");
		preg_match_all('#<tr> <td width="65%" align="right" valign="top">(.+?)<br><a href="/(rus/news/\?action=show&id=(\d+))">(.+?)</a></td>#sui', $text, $news, PREG_SET_ORDER);

		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->id = $news_value[3];
			$news_item->date = $news_value[1];
			$news_item->header = $news_value[4];
			$news_item->urlShort = $this->shopBaseUrl."news/";
			$news_item->urlFull = $this->shopBaseUrl.$news_value[2];
			$news_item->contentShort = "";
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			preg_match('#<div class="text">.+?END BLOCK : block_news_img --> (.+?)</div>#sui', $text, $content);
			$news_item->contentFull = $content[1];
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base);
	}
	

	
	public function loadItems () 
	{
		$base = array ();
		
		$collection_names = array(array("name"=>"gold-gracia", "title"=>"Золотая грация"),array("name"=>"gracia", "title"=>"Грация"),array("name"=>"fashion", "title"=>"Fashion грация"),array("name"=>"kids", "title"=>"Грация Kids"));
		
		foreach($collection_names as $collection_name)
		{
			$collection = new ParserCollection();
			
			$collection->name = $collection->id = $collection_name['title'];
			$collection->url = $this->shopBaseUrl."rus/".$collection_name['name']."/";
		
			
			$text = $this->httpClient->getUrlText($collection->url);
				
			preg_match('#<div class="title" align="center">(.+?)</div>#sui', $text, $text);
			preg_match_all('#<a href="(.+?)">(.+?)</a>#sui', $text[1], $categories, PREG_SET_ORDER);
			for($i = 1; $i < count($categories) - 1; $i++)
			{
				$category_name = $categories[$i][2];

				$text = $this->httpClient->getUrlText($this->shopBaseUrl.$categories[$i][1]);
				
				preg_match_all('#<div class="goodTitle"><a  href="(.+?id=(\d+))">(.+?)</a></div>#sui', $text, $items, PREG_SET_ORDER);
				foreach($items as $item_)
				{
					$item = new ParserItem();
					
					$item->id = $item_[2];
					$item->url = $this->shopBaseUrl.$item_[1];
					$item->name = $item_[3];
					$item->categ = $category_name;
					
									
					$text = $this->httpClient->getUrlText($item->url);
					
					preg_match('#<td width="60%" align="left" valign="top">(.+?)</TD>#sui', $text, $descr);
					if($descr)$item->descr = $this->txt($descr[1]);
					$item->descr = str_replace($item->name, "", $item->descr);
					
					preg_match('#Состав:(.+)#sui', $item->descr, $structure);

					if($structure)$item->structure= $structure[1];
					
					preg_match('#<td width="40%" align="center" valign="top">\s*<img src="(.+?)"#sui', $text, $image_value);
					$image = new ParserImage();
					
					$image->url = $this->shopBaseUrl.$image_value[1];
					$this->httpClient->getUrlBinary($this->urlencode_partial($image->url));
					$image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, mb_strrpos($image->url, ".") - mb_strrpos($image->url, "/")-1);
					$image->path = $this->httpClient->getLastCacheFile();
					$image->type = "jpg";
					
					$item->images[] = $image;
					
					$collection->items[] = $item;
				}
			}
			
			$base[] = $collection;
		}

		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."rus/shops/");
		preg_match_all('#<li><a href="(http://gracia.ru/rus/shopdescr/\?action=shwprd&id=(\d+))">(.+?)</a></li>#sui', $text, $shop_items, PREG_SET_ORDER);
		foreach($shop_items as $shop_item)
		{
			$text = $this->httpClient->getUrlText($shop_item[1]);
			preg_match("#<option value='(.+?)' selected>(.+?)</option>#sui", $text, $city);
			
			$shop = new ParserPhysical();
			
			$shop->city = $city[2];
			$shop->address = $shop_item[3];
			$shop->id = $shop_item[2];
			
			$base[] = $shop;
		}
		
		return $this->savePhysicalResult($base);
	}
}
