<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_mirflorange_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://www.mir-florange.ru/'; // Адрес главной страницы сайта 
	
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		$base = array();
		
		$url = $this->shopBaseUrl."news.html";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('# <tr>\s*
          <td align="left" valign="top" style="padding-top: 1px;"><b>(.+?)</b>&nbsp;&nbsp;&nbsp;&nbsp;</td>\s*
          <td align="left" valign="top" width="100%"><a href="(show_news_(\d+).html)" class="cl12"><b>(.+?)</b></a><div class="fil1"></div>(.+?)(<script|</td>)#sui', $text, $news, PREG_SET_ORDER);
		
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$news_item->date = $news_value[1];
			$news_item->urlFull = $this->shopBaseUrl.$news_value[2];
			$news_item->urlShort = $url;
			$news_item->id = $news_value[3];
			$news_item->header = $news_value[4];
			$news_item->contentShort = $news_value[5];
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			preg_match('#<td class="cl12">.+?<div class="fil"></div>(.+?)</td>#sui', $text, $content);
			$news_item->contentFull = $content[1];
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult($base); 
	}
	

	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		preg_match_all('#<td class="dtree"><a href="(category_(\d+).html)" >(.+?)</a></td>#sui', $text, $collections, PREG_SET_ORDER);
		
		foreach($collections as $collection_item)
		{
			
		$collection = new ParserCollection();
		
		$collection->id = $collection_item[2];
		$collection->url = $this->shopBaseUrl.$collection_item[1];
		$collection->name = $collection_item[3];
		
		$text = $this->httpClient->getUrlText($collection->url);
		
		preg_match_all('#<td><img src="data/default/pixel.gif" alt="" align="left" width="8" height="10"></td>\s*<td class="dtree"><a href="(.+?)" >(.+?)</a></td>#sui', $text, $categories, PREG_SET_ORDER);
		
		if(!$categories)
			$categories = array(array("1"=>$collection_item[1], "2"=>$collection->name));
			
		foreach($categories as $category)
		{
			$category_name = $category[2];
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]);


			preg_match_all('#<td class="hdbot"><a href="(product_(\d+).html)">Подробнее</a></td>#sui', $text, $items, PREG_SET_ORDER);

			foreach($items as $item_value)
			{
				$item = new ParserItem();
				
				$item->id = $item_value[2];
				$item->url = $this->shopBaseUrl.$item_value[1];
				//$item->url = "http://www.mir-florange.ru/product_233.html";
				
				$text = $this->httpClient->getUrlText($item->url);
				
				preg_match('#<title>(.+?)</title>#sui', $text, $name);
				$item->name = $this->txt($name[1]);
				$item->categ = $category_name;
			
				preg_match('#<td class="price" id="optionPrice">(.+?)\.00 руб.</td>#sui', $text, $price);
				$item->price = str_replace(' ','',$price[1]);
				
				preg_match('#GetCurrentCurrency\(\);\s+</script>\s*(.+?)</td>#sui', $text, $descr);
				if($descr)$item->descr = $this->txt($descr[1]);
				
				preg_match_all('#<option.+?>(.+?)</option>#sui', $text, $sizes);
				if(count($sizes[0]) == 0)
				{
					preg_match('#РАЗМЕР:\s*<b>(.+?)</b>#sui', $text, $size);
					if(!$size)preg_match('#Бюстгальтер особая поддержка:\s*<b>(.+?)</b>#sui', $text, $size);
					if($size)$item->sizes[] = $size[1];
				}
				else
					$item->sizes = $sizes[1];
				
				//preg_match('#<br>\s*<br>(.+?)(?:$|<)#sui', $descr[1], $material);
				//if(!$material || mb_strlen($material[1]) < 20)preg_match('#</p>(.+)#sui', $descr[1], $material);
				
				//if($material)$item->material = $this->txt($material[1]);
				//if(mb_strpos($item->material, ":") !== false)
				//	$item->material = mb_substr($item->material, mb_strpos($item->material, ":") + 1);
			

				preg_match('#<td class="imboxr">\s*<a href="(.+?)".+?<img src="(.+?)"#sui', $text, $images_text);
				$images = array($images_text[1], $images_text[2]);
				foreach($images as $image_value)
				{
					$image = new ParserImage();
					
					$image->url = $this->shopBaseUrl.$image_value;

		
					$this->httpClient->getUrlBinary($this->urlencode_partial($image->url));
					$image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, mb_strrpos($image->url, ".") - mb_strrpos($image->url, "/")-1);
					$image->path = $this->httpClient->getLastCacheFile();
					$image->type = "jpg";
					$item->images[] = $image;
				}
				
				preg_match('#Код товара: <b>(\d+)</b>#sui', $text, $articul);
				if($articul)$item->articul = $articul[1];

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
		
		return $this->savePhysicalResult($base);
	}
}
