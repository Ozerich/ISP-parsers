<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_froggy_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.froggy.ru/";
	
	public function loadItems () 
	{
		$base = array();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		preg_match('#<h2>(.+?)</h2>#sui', $text, $col_name);
		
		$collection = new ParserCollection();
	
		$collection->name = $this->txt($col_name[1]);
		$collection->url = $this->shopBaseUrl."catalog/";
		
		$text = $this->httpClient->getUrlText($collection->url);
		
		preg_match_all('#<td class=men><a href="(.+?)".+?><span class=borange>(.+?)</span>#sui', $text, $categories, PREG_SET_ORDER);
		
		foreach($categories as $category)
		{
			$category_name = $category[2];
			$url = $category[1];
			$text = $this->httpClient->getUrlText($url);
			
			preg_match_all("#\[\d+\]#sui", $text, $pages, PREG_SET_ORDER);

			$page_count = count($pages);
			if($page_count == 0)$page_count = 1;
			
			for($page = 1; $page <= $page_count; $page++)
			{
				$text = $this->httpClient->getUrlText($url."page/$page/");
				preg_match_all('#<td valign=top class=\'tovar_td\' align=center><a href="('.$url.'item/(\d+)/)".+?><b class="green up nd">(.+?)</b></a>#sui', $text, $items, PREG_SET_ORDER);
				foreach($items as $item_value)
				{
					$item = new ParserItem();
					
					$item->id = $item_value[2];
					$item->name = $item_value[3];
					$item->url = $item_value[1];
					
					$item->categ = $category_name;
					
					$image = new ParserImage();
					
					$image->url = "http://froggy.ru/catalog/upload_cat/big/".$item->id.".jpg";
					$image->type = "jpg";
					$this->httpClient->getUrlBinary($image->url);
					$image->path = $this->httpClient->getLastCacheFile();
					$image->id = $item->id;
					
					$item->images[] = $image;
					
					$collection->items[] = $item;
				}
			}
			
			
		
			
		}
		
		$base[] = $collection;
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog/page/13/");
		preg_match_all('#<span style="COLOR: rgb\(0,128,0\)">.+?</span>(.+?)<p>\&nbsp;</p>#sui', $text, $rasdels, PREG_SET_ORDER);
		
		for($i = 0; $i < count($rasdels); $i++)
		{
			$text = $rasdels[$i][1];
			preg_match_all('#(?:<b>|<span id="viewmessagebody"><span style="FONT-WEIGHT: bold">)(.+?)(?:</b>|</span>)(.+?)<br />#sui', $text, $shops, PREG_SET_ORDER);
			foreach($shops as $shop_value)
			{
				if($i == 0)$shop_city = "Москва";
				else if($i == 1)$shop_city = "Санкт-Петербург";
				else if($i == 2)$shop_city = $shop_value[1];
				$shop = new ParserPhysical();
				
				$shop_address = $shop_value[2];
				$shop_phone = "";
				preg_match('#(тел. (.+?))$#sui', $shop_address, $phone);
				if($phone)
				{
					$shop_phone = $phone[2];
					$shop_address = str_replace($phone[1], "", $shop_address);
				}
				if($i < 2)
					$shop_address .= ", ".$shop_value[1];

				$shop_address = $this->txt($shop_address);
				$shop_phone = $this->txt($shop_phone);
				$shop_city = $this->txt($shop_city);
				
				if($shop_address[0] == ",")
					$shop_address = mb_substr($shop_address, 2);
				if($i == 2)
					$shop_city = mb_substr($shop_city,2);				

				if(mb_substr($shop_address, 0, 2) == "г.")
				{
					$shop_address = mb_substr($shop_address, 2);
					$shop_city = mb_substr($shop_address, 0, mb_strpos($shop_address, ","));
					$shop_address = mb_substr($shop_address, mb_strpos($shop_address, ",") + 1);
				}
				
				$shop_phone = str_replace("магазина", "", $shop_phone);
				
				if($this->address_have_prefix($shop_address))
				{
					$shop_prefix = mb_substr($shop_address, 0, mb_strpos($shop_address, " "));
					$shop_address = trim(mb_substr($shop_address, mb_strpos($shop_address, " ")));
					$shop_name = mb_substr($shop_address, 0, mb_strpos($shop_address, " "));
					$shop_address = trim(mb_substr($shop_address, mb_strpos($shop_address, " "))).", ".$shop_prefix." ".$shop_name;
				}
				
				$shop->address = $shop_address;
				$shop->phone = $shop_phone;
				$shop->city = $shop_city;
				
				
				$base[] = $shop;
			}
		}
		
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		return null;
	}
}
