<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_bathandbeauty_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.bathandbeauty.ru/";
	
	public function loadItems () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog/");
		
		preg_match_all('#<a class="menu" href="/(catalog/\?SECTION_ID=(\d+))">(.+?)</a>#sui', $text, $collections, PREG_SET_ORDER);
		foreach($collections as $collection_item)
		{
			$collection = new ParserCollection();
			
			$collection->id = $collection_item[2];
			$collection->url = $this->shopBaseUrl.$collection_item[1];
			$collection->name = $collection_item[3];
			
			$text = $this->httpClient->getUrlText($collection->url);
			
			preg_match_all('#<a class="podmenu(?:_act)?" href="/(.+?)">(.+?)</a>#sui', $text, $categories, PREG_SET_ORDER);
			if(!$categories)
				$categories = array(array("1"=>$collection_item[1], "2"=>""));
			foreach($categories as $category)
			{
				$category_name = $category[2];
				$page = 1;
				while(true)
				{
					$text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]."&PAGEN_1=$page");
					
					preg_match('#<b>(\d+)</b>#sui', $text, $id);
					if(strpos($text, "Начало&nbsp;|&nbsp;Пред.&nbsp;|") === false)
						if($page > 1)
							break;
					if($id && $id[1] == 1 && $page > 1)
						break;
					
					preg_match('#<TABLE xmlns="" class="table">(.+?)</center>#sui', $text, $text);
					preg_match_all('#<a href="/(catalog/detail.php\?ID=(\d+))">#sui', $text[1], $items, PREG_SET_ORDER);
					foreach($items as $item_value)
					{
						$item = new ParserItem();
						
						$item->id = $item_value[2];
						$item->url = $this->shopBaseUrl.$item_value[1];
						$item->categ = $collection->name;
						if($category_name != "")
						{
							$item->categ = array($item->categ);
							$item->categ[] = $category_name;
						}
						
						$text = $this->httpClient->getUrlText($item->url);
						
						preg_match('#<div class="catalog-element">\s*<h1>(.+?)</h1>#sui', $text, $name);
						if($name)$item->name = $this->txt($name[1]);
						
						preg_match('#<span class="catalog-price">(\d+)(?:\.|,)(?:.+?)</span>#sui', $text, $price);
						if($price)$item->price = $price[1];
						
						preg_match('#Страна:<b>(.+?)</b>#sui', $text, $made_in);
						if($made_in)$item->made_in = $this->txt($made_in[1]);
						
						preg_match('#Объем:<b>(.+?)</b>#sui', $text, $v);
						if($v)$item->descr = "Объём: ".$this->txt($v[1])."\n";
						
						preg_match('#Марка:<b>(.+?)</b>#sui', $text, $brand);
						if($brand)$item->brand = $this->txt($brand[1]);
						
						preg_match('#Артикул:\s*(\d+)#sui', $text, $articul);
						if($articul)$item->articul = $this->txt($articul[1]);
					
						preg_match('#<br\s/>\s*<br\s/>(.+?)<br\s/>#sui', $text, $descr);
						if($descr)$item->descr .= $this->txt($descr[1]);
						
						preg_match('#<td width="0%" valign="top">\s*<img border="0" src="/(.+?)"#sui', $text, $image);
						if($image)
						{
							$image_item = new ParserImage();
						
							$image_item->url = $this->shopBaseUrl.$image[1];
							$this->httpClient->getUrlBinary($image_item->url);
							$image_item->type = substr($image_item->url, strrpos($image_item->url, ".") + 1);
							$image_item->path = $this->httpClient->getLastCacheFile();
							
	
							$item->images[] = $image_item;
						}
						
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
		
		for($i = 1; $i <= 3; $i++)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/index.php?gr=$i");
			preg_match('#<h1>(.+?)</h1>#sui', $text, $city);
			$city = $city[1];
			$is_city = $city != "Другие города";
			
			preg_match('#</strong></td>\s*</tr>\s*(.+?)<DIV class=footer>#sui', $text, $table);
			preg_match_all("#<tr.+?><td.+?>(.+?)</td><td.+?>(.+?)</td><td.+?>(.*?)</td><td.+?>(.*?)</td></tr>#sui", $table[1], $items, PREG_SET_ORDER);
			foreach($items as $item)
			{
				$shop = new ParserPhysical();
				$shop->city = ($is_city) ? $city : substr($item[1], 0);
				$shop->address = $this->txt($item[3]);
				$shop->phone =  $item[4];
				
				preg_match("#<a href='index.php\?map=(\d+)'>#sui", $item[2], $id);
				$shop->id = $id[1];
				
				$base[] = $shop;
			}
		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$url = $this->shopBaseUrl."news/";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all("#<tr><td width=\"70%\"><strong>(.+?)</td><td.+?>(.+?)</td></tr><tr><td.+?>(.+?)<br/><br/><a href='(.+?)'>Далее...</a></td></tr>#sui", $text, $news, PREG_SET_ORDER);
		
		foreach($news as $item)
		{
			$news_item = new ParserNews();
			
			$news_item->header = $this->txt($item[1]);
			$news_item->date = $item[2];
			$news_item->contentShort = $item[3];
			$news_item->urlShort = $url;
			$news_item->urlFull = $url.$item[4];
			$news_item->id = substr($news_item->urlFull, strrpos($news_item->urlFull, "=") + 1);
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			
			preg_match('#<td colspan="2" style="padding-top\:\s5px;">(.+?)<br/><br/><br/>#sui', $text, $text);
			$news_item->contentFull = $text[1];
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult ($base); 
	}
}
