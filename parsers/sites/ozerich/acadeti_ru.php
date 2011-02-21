<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_acadeti_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.acadeti.ru/";
	
	public function loadItems () 
	{
		return null;
		$base = array ();
	
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		
		preg_match_all('#><a href="/(shortcut/\?CatID=(\d+))".+?>(.+?)</a>#sui', $text, $collections, PREG_SET_ORDER);

		foreach($collections as $collection_item)
		{
			$collection = new ParserCollection();
			
			$collection->id = $collection_item[2];
			$collection->url = $this->shopBaseUrl.$collection_item[1];
			$collection->name = $collection_item[3];
			
			$page = 1;
			while(true)
			{
				$text = $this->httpClient->getUrlText($this->shopBaseUrl."shop/?CatID=".$collection->id."&Page=$page");
				
				preg_match_all('#<a href="/(shop/Product_(\d+)/)"#sui', $text, $items, PREG_SET_ORDER);
				if(!$items)break;
				
				foreach($items as $item_value)
				{
					$item = new ParserItem();
					
					$item->url = $this->shopBaseUrl.$item_value[1];
					
					$text = $this->httpClient->getUrlText($item->url);
					
					preg_match('#<strong>(.+?)</strong>#sui', $text, $name);
					$item->name = $name[1];
					
					preg_match('#<div class="carditem">\s*<span>(\d+)\s<i class="rubl">#sui', $text, $price);
					if($price)$item->price = $price[1];
					
					preg_match('#Материалы:(.+?)<br/>#sui', $text, $material);
					if($material)$item->material = $this->txt($material[1]);
					
					
					preg_match('#Вес:(.+?)<br/>#sui', $text, $weight);
					if($weight)$item->weight = $weight[1];
					
					preg_match('#Цвет:(.+?)<br/>#sui', $text, $color);
					if($color)$item->colors[] = $color[1];
					
					preg_match('#<li>\s*<p>(.+?)</p>#sui', $text, $descr);
					if($descr)$item->descr = $this->txt($descr[1]);
					
					preg_match('#<p>Артикул.+?<span>(.+?)</span></p>#sui', $text, $articul);
					if($articul)$item->articul = $articul[1];

					preg_match('#<p>ID.+?<span>(.+?)</span></p>#sui', $text, $id);
					if($articul)$item->id = $id[1];
					
					preg_match('#<p>\s*Бренд\s*<a.+?>(.+?)</a>\s*(.+?)\s*</p>#sui', $text, $brand);
					if($brand)
					{
						$item->brand = $brand[1];
						$item->made_in = $brand[2];
						if($item->made_in[0] == '(')
							$item->made_in = substr($item->made_in, 1, -1);
					}
					
					$images = array();

					preg_match('#<div id="BigImageShower".+?><img src="/_upload/il/(.+?)\.jpg"/></div>#sui', $text, $big_image);
					preg_match_all('#<li.+?zoomsteps="\d+"><a href="\#(\d+)">#sui',$text, $images, PREG_SET_ORDER);
					if($big_image)$images[] = $big_image;
					
					$images_history = array();
					
					foreach($images as $image_item)
					{
						$image = new ParserImage();
						
						$image->id = $image_item[1];
						if(in_array($image->id, $images_history))continue;
						$images_history[] = $image->id; 
						$image->url = $this->shopBaseUrl."_upload/il/".$image->id.".jpg";
						$this->httpClient->getUrlBinary($image->url);
						$image->type = substr($image->url, strrpos($image->url, ".") + 1);
						$image->path = $this->httpClient->getLastCacheFile();
						
						$item->images[] = $image;
					}
					
					preg_match('#<div style="height:25px; font-size:13px; padding-bottom:2px;">(.+?)<strong>#sui', $text, $cat_content);
					preg_match_all('#<a href="/shop/\?CatID=\d+">(.+?)</a>#sui', $cat_content[1], $categories, PREG_SET_ORDER);
					for($i = 1; $i < count($categories); $i++)
						$item->categ[] = $categories[$i][1];
					$collection->items[] = $item;
				}
				$page++;
			}
			
			$base[] = $collection;
		}
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."our_shops/");
		preg_match_all('#\d+\)(.+?)<br\s/>#sui', $text, $shops, PREG_SET_ORDER);
		
		foreach($shops as $shop_item)
		{
			$shop = new ParserPhysical();
			
			$text = $this->txt($shop_item[1]);

			if(mb_substr($text, 0, 2) == 'г.')
			{
				$city = $this->txt(mb_substr($text, 3, mb_strpos($text, ",") - 3));
				$text = mb_substr($text, mb_strpos($text, ",") + 2);
			}
			else
				$city = "Москва";
			if(mb_strrpos($text, ", с ") !== false)
			{
				$address = mb_substr($text, 0, mb_strrpos($text, ", с "));
				$timetable = mb_substr($text,  mb_strrpos($text, ", с ") + 2);
			}
			else
			{
				$address = $text;
				$timetable = "";
			}
			
			$shop->city = $city;
			$shop->address = $this->address($address);
			$shop->timetable = $timetable;
			
			
			$base[] = $shop;
		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		
		preg_match_all('#<div class="item">\s*<a href="/(news/\?ID=(\d+))" title="(.+?)">(.+?)</a>#sui', $text, $news, PREG_SET_ORDER);
		foreach($news as $item)
		{
			$news_item = new ParserNews();
			
			$news_item->id = $item[2];
			$news_item->urlShort = $this->shopBaseUrl;
			$news_item->urlFull = $this->shopBaseUrl.$item[1];
			$news_item->contentShort = $news_item->header = $item[3];
			$news_item->date = $this->date_to_str($item[4]);
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			preg_match('#<div class="rightA">(.+?)<div class="clear">#sui', $text, $content);
			if($content)
			{
				$content = $content[1];
				if(strpos($content,"<!--[if gte mso 9]>") !== false)
				{
					$a = strpos($content,"<!--[if gte mso 9]>");
					$b = strrpos($content,"<![endif]-->")+strlen("<![endif]-->");
					$content = trim(substr($content, 0, $a).substr($content, $b));
				}
				$news_item->contentFull = $content;
			}
			
			$base[] = $news_item;
		}
		
		return $this->saveNewsResult ($base); 
	}
}
