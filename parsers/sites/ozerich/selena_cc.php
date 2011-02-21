<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_selena_cc extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://selenavip.ru/'; // Адрес главной страницы сайта 
	
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		return null;
	}
	
	
	private function loadImage($url)
	{
		$text = $this->httpClient->getUrlText($url);
		
		preg_match('#<a class="contentheading"  href="(.+?)"#sui', $text, $image_content);
		
		if($image_content)
		{
			
			$image = new ParserImage();
		
			$image->url = $image_content[1];
		
			$this->httpClient->getUrlBinary($this->urlencode_partial($image->url));
			$image->path = $this->httpClient->getLastCacheFile();
		
			$image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, mb_strrpos($image->url, ".") - mb_strrpos($image->url, "/")-1);
		
			$image->type = "jpg";
		
			return $image;
		}
		else
			return null;
	}
	
	private function parseItem($url)
	{
		$text = $this->httpClient->getUrlText($url);
		if(mb_strpos($text, "Извините, но запрошенный товар не найден!") !== false)return null;
		
		
		$item = new ParserItem();
		
		preg_match('#katalog_(\d+).html#sui', $text, $id);
		
		$item->id = $id[1];
		
		$item->url = $url;
		
		preg_match('#<h1 style="margin-left: 0px;  margin-right: 0px;font-family: Tahoma, Arial; font-size:16px; font-weight:normal;">(.+?)</h1>#sui', $text, $name);
		if($name)$item->name = $name[1];
		else return null;
		
		
		preg_match('#Артикул: (\d+)#sui', $text, $articul);
		if($articul)$item->articul = $articul[1];
		
		preg_match_all('#<p class="CatalogList">(.+?)</p>#sui', $text, $p_tags, PREG_SET_ORDER);
		$item->descr = $this->txt($p_tags[1][1]);
		
		preg_match('#(.+?)<form action="http://selenavip.ru/index.php#sui', $text, $price_text);
		//print_r($price_text);
		preg_match('#<span class="productPrice">(.+?)</span>#sui', $price_text[1], $price);
		if($price)$item->price = trim(str_replace("руб.", "", $price[1]));
		

		preg_match('#<span class="product-Old-Price">(.+?)</span>#sui', $price_text[1], $old_price);

		if($old_price)
		{
			$old_price = trim(str_replace("руб.", "", $old_price[1]));

			$item->discount = $this->discount($old_price, $item->price);
			$item->price = $old_price;
		}

		
		
		preg_match('#<td align="left" valign="top" class="valigntop" width="100%">(.+?)</td>#sui', $text, $temp);
		preg_match_all('#<a href="/(.+?)" target="_self">#sui', $temp[1], $images, PREG_SET_ORDER);
		
		$item->images[] = $this->loadImage($url);
		foreach($images as $image)
		{
			$image_ =  $this->loadImage($this->shopBaseUrl.$image[1]);
			if($image_ != null)
				$item->images[] = $image_;
		}

		
		preg_match("#<a href=\"\#bottom\"  onclick=\"javascript:void\(0\);window\.open\('(.+?)'#sui", $text, $info);
		
		if($info)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$info[1]);
		//	$text = $this->httpClient->getUrlText("http://selenavip.ru/index2.php?option=com_virtuemart&category_id=17&flypage=sel_flypage_2.tpl&page=shop.product_details&product_id=3297&Itemid=56");
			
			//print_r($text);
			preg_match('#<td colspan="2">(.+?)</td>#sui', $text, $text);
			preg_match('#(?:<p>|<li>)(?:<strong>)*\s*Материал:*(?:</strong>)*:*(.*?)(?:</p>|</li>)#sui', $text[1], $material);
			if($material)$item->material = $this->txt($material[1]);
		
			preg_match('#(?:<p>|<li>)(?:<strong>)*\s*Производство:*(?:</strong>)*:*(.*?)(?:</p>|</li>)#sui', $text[1], $made_in);
			if($made_in)$item->made_in = $this->txt($made_in[1]);

			preg_match('#(?:<p>|<li>)(?:<strong>)*\s*Изготовитель:*(?:</strong>)*:*(.*?)(?:</p>|</li>)#sui', $text[1], $made_in);
			if($made_in)$item->made_in = $this->txt($made_in[1]);
			
		}
		
		if($item->material == "")
		{
			preg_match('#Материал:(.+?)(\.|$|;)#sui', $item->descr, $material);
			if($material)$item->material = $this->txt($material[1]);
		}
		
		if($item->made_in == "")
		{
			preg_match('#Производство:(.+?)(\.|$|;)#sui', $item->descr, $made_in);
			if($made_in)$item->made_in = $this->txt($made_in[1]);
		}
		
		if($item->structure == "")
		{
			preg_match('#Состав:(.+?)(\.|$|;)#sui', $item->descr, $structure);
			if($structure && mb_strpos($structure[1], "Материал") === false)$item->structure = $this->txt($structure[1]);
		}
		
		if(!$item->colors)
		{
			preg_match('#Цвет:(.+?)(\.|$|;)#sui', $item->descr, $color);
			if($color)$item->colors[] = $this->txt($color[1]);
		}
		$item->price = str_replace(' ', '', $item->price);
		$item->name = str_replace($item->articul, '', $item->name);
		$item->descr = str_replace('Купить в интернет магазине', '', $item->descr);
		$item->name = trim($item->name);
		if($item->name[0] == '.')$item->name = trim(mb_substr($item->name, 1));

		return $item;
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		preg_match_all('#<a title=".+?" style="display:block;" class="mainlevel" href="/((.+?).html)"\s*>(.+?)</a>#sui', $text, $collections, PREG_SET_ORDER);
		
		foreach($collections as $collection_value)
		{
			$collection = new ParserCollection();
			
			$collection->id = $collection_value[2];
			$collection->name = $collection_value[3];
			$collection->url = $this->shopBaseUrl.$collection_value[1];
			
			$text = $this->httpClient->getUrlText($collection->url);

			preg_match_all('#<a title=".+?" style=".+?" class="sublevel" href="/(.+?)" (?:id="active_menu")* >(.+?)</a>#sui', $text, $categories, PREG_SET_ORDER);
			if(!$categories)
				$categories = array(array("1"=>$collection_value[1], "2"=>""));

			foreach($categories as $category_value)
			{
				$category_name = str_replace("купить в интернет магазине недорого", "",$category_value[2]);
				
				$text = $this->httpClient->getUrlText($this->shopBaseUrl.$category_value[1]);
				
				$pages = array();
				preg_match('#<p align="right" class="PgNv1">Страницы: (.+?)</p>#sui', $text, $page_content);
				if(!$page_content)
				{
					preg_match('# <p class="CatalogList">.+?<a href="/(.+?)".+?>Подробнее...</a>#sui', $text, $url);
					$item = $this->parseItem($this->shopBaseUrl.$url[1]);
					if($category_name)
						$item->categ[] = $category_name;
					$collection->items[] = $item;
					continue;
				}
				preg_match_all('#<a href="/(.+?\.html)" class="PgNv"><strong>\d+</strong></a>#sui', $page_content[1], $pages, PREG_SET_ORDER);
				array_unshift($pages, array("1"=>$category_value[1]));

				
				foreach($pages as $page_item)
				{
					$text = $this->httpClient->getUrlText($this->shopBaseUrl.$page_item[1]);
					
					preg_match_all('#<h2 style="margin-left: 0px;  margin-right: 0px;"><a class="contentheading" style="margin-left: 0px;  margin-right: 0px;" title=".+?" href="/(.+?)">#sui',$text, $items, PREG_SET_ORDER);
					foreach($items as $item)
					{
						$item_res = $this->parseItem($this->shopBaseUrl.$item[1]);
						if($item_res == null)continue;
						if($category_name != "")
							$item_res->categ[] = $category_name;
						$collection->items[] = $item_res;
					}
				}

			}
			if($collection->items[0] == null)continue;
			$base[] = $collection;

		}
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		return null;
	}
}
