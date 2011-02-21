<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_silverandsilver_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://www.silverandsilver.ru/'; // Адрес главной страницы сайта 
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		$base = array();
		
		$url = $this->shopBaseUrl."n_news/";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('# <div class="date"><span>(.+?)</span></div>\s*<div class="m5 bb"><a href="(http://www.silverandsilver.ru/n_news/(\d+).htm)">(.+?)</a>.+?<p>(.*?)</p>#sui', $text, $news, PREG_SET_ORDER);

		
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->date = $news_value[1];
			$news_item->urlFull = $news_value[2];
			$news_item->id = $news_value[3];
			$news_item->header = $news_value[4];
			$news_item->contentShort = $news_value[5];
			$news_item->urlShort = $url;
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			
			preg_match('#<div class="text_container">(.+?)</div>#sui', $text, $content);
			if($content)$news_item->contentFull = $content[1];
			
			$base[] = $news_item;
		}
		
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		
		preg_match_all('#<li id="cat_(\d+)"><a href="(.+?)">(.+?)</a></li>#sui', $text, $collections, PREG_SET_ORDER);
		
		foreach($collections as $collection_value)
		{
			$collection = new ParserCollection();
			
			$collection->id = $collection_value[1];
			$collection->url = $collection_value[2];
			$collection->name = $collection_value[3];
			
			
			$text = $this->httpClient->getUrlText($collection->url);
			
			preg_match('#<td valign="top" style="padding-right:30px;">(.+?)</td>#sui', $text, $descr);
			if($descr)$collection->descr = $this->txt($descr[1]);
			
			preg_match('#<div class="popup" id="cat_'.$collection->id.'_content" style="display:none; width: 510px;">(.+?)</div>\s*</div>\s*</div>#sui', $text, $categories_content);
			

			if($categories_content)
				preg_match_all('#<div class="m5 white hova"><a href="(.+?)">(.+?)</a></div>#sui', $categories_content[1], $categories, PREG_SET_ORDER);
			else
				$categories = array(array("1"=>$collection->url, "2"=>$collection->name));

			foreach($categories as $category)
			{
				$page = 1;
				while(true)
				{
					$text = $this->httpClient->getUrlText($category[1]."?pg=$page");
				
					preg_match('#<b>(\d+)</b>\&nbsp;\&nbsp;#sui', $text, $current_page);
					if($current_page)
					{
						if($current_page[1] == 1 && $page > 1)
							break;
					}
					else if($page > 1) break;
				
					preg_match_all('#<div class="m10 bb"><a href="(.+?)/" class="df2">(.+?)</a></div>\s*<div class="m3">Артикул: (.+?)</div>\s*<div class="m3">Вес: (.+?)</div>\s*<div>Цена: (.+?) руб.</div>\s*</div>#sui', $text, $items, PREG_SET_ORDER);
				

					foreach($items as $item_value)
					{
						$item = new ParserItem();
						
						$item->id = mb_substr($item_value[1], mb_strrpos($item_value[1], "-") + 1);
						$item->url = $item_value[1]."/";
						$item->name = $item_value[2];
						$item->weight = $item_value[4];
						$item->price = $item_value[5];
						$item->categ = $category[2];
						
						$text = $this->httpClient->getUrlText($item->url);
						

						preg_match('#<div class="m5">Описание:(.+?)</div>\s*</div>#sui', $text, $descr);
						if($descr)$item->descr = $this->txt($descr[1]);
						
						preg_match('#Артикул: (.+?)</div>#sui', $text, $articul);
						if($articul)$item->articul = $articul[1];
						
						$image = new ParserImage();
						
						$image->url = "http://www.silverandsilver.ru/f/products_new/big_".$item->id.".jpg";
						$image->id = $item->id;
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
		}
		

		return $this->saveItemsResult ($base);
	}
	
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
		$cities = explode('<h2 class="m20">', $text);
		


		foreach($cities as $city)
		{
			$city_name = mb_substr($city,0,mb_strpos($city, "<"));
			$text = $city;

			preg_match_all('#<h4 class="back">(.+?)</h4>\s*<div class="m20"><p>(.+?)</p>\s*<p>\s*Часы работы: (.+?)</p>#sui', $text, $shops, PREG_SET_ORDER);
			
			foreach($shops as $shop_value)
			{
				$shop_item = new ParserPhysical();
				
				$shop_item->address = $this->txt($shop_value[2]).", ".$shop_value[1];
				$shop_item->address = str_replace("г. ".$city_name, "", $shop_item->address);
				if($shop_item->address[0] == ',')$shop_item->address = mb_substr($shop_item->address, 1);
				$shop_item->address = $this->txt($shop_item->address);
				
				$shop_item->timetable = $this->txt($shop_value[3]);
				$shop_item->city = $city_name;
				
				$base[] = $shop_item;
			}
			
		}
		
		
		return $this->savePhysicalResult ($base);
	}
}
