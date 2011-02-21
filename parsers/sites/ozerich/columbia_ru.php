<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_columbia_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://www.columbia.ru'; // Адрес главной страницы сайта 
	public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        //$this->httpClient->setRequestsPause (0.2); 
    }
	
	public function loadNews() 
	{ 
		$base = array();
		
		$url = $this->shopBaseUrl."?part=news";
		$text = $this->httpClient->getUrlText($url);
		
		preg_match_all('#<h2>(.+?)\.</h2>\s*<span class="b">(.+?)</span><br />(.+?)<hr size="1" color="Silver" />#sui', $text, $news, PREG_SET_ORDER);
		
		foreach($news as $news_value)
		{
			$news_item = new ParserNews();
			
			$date = $news_value[1];
			$month = mb_substr($date, 0, mb_strpos($date, " "));
			$year = mb_substr($date, mb_strpos($date, " ") + 1);
			
			$news_item->date = "1.".$this->get_month_number($month).".".$year;
			$news_item->header = $this->txt($news_value[2]);
			$news_item->contentShort = $news_item->contentFull = $news_value[3];
			$news_item->urlShort = $news_item->urlFull = $url;
			
			$base[] = $news_item;
		}		
		return $this->saveItemsResult ($base);
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$url = $this->shopBaseUrl."?part=catalogue&id=79";
		
		$text = $this->httpClient->getUrlText($url);
		preg_match('#<span class="b">КАТАЛОГ<br />(.+?)</span>#sui', $text, $collection_value);

		$collection = new ParserCollection();
		
		$collection->id = $collection->name = $collection_value[1];
		$collection->url = $url;
		
		preg_match('#<div class="menu-header">(.+?)<div class="content-part">#sui', $text, $text);
		preg_match_all('#<a class="menu" href="(.+?)"><span style="font-size: 10px;">(.+?)</span></a>#sui', $text[1], $sub_categories, PREG_SET_ORDER);
		foreach($sub_categories as $sub_category)
		{
			$text = $this->httpClient->getUrlText($this->shopBaseUrl.$sub_category[1]);
			
			preg_match('#\&nbsp;\&raquo;\&nbsp;<a href=".+?">(.+?)</a>#sui', $text, $category);
			$categ = array($category[1], $sub_category[2]);
			
			preg_match_all('#<a class="u" href="(\?part=catalogue&id=\d+&item=(\d+))">#sui', $text, $items, PREG_SET_ORDER);
			foreach($items as $item_value)
			{
				$item = new ParserItem();
				
				$item->id = $item_value[2];
				$item->url = $this->shopBaseUrl.$item_value[1];
				$item->categ = $categ;
				
				$text = $this->httpClient->getUrlText($item->url);
				
				preg_match('#<h2>(.+?)</h2>#sui', $text, $name);
				if($name)$item->name = $name[1];
				
				preg_match('#<span class="red">арт. (.+?).</span>#sui', $text, $articul);
				if($articul)$item->articul = $articul[1];
				
				preg_match('#</p>\s*<p style="margin: 5px;">(.+?)</p>#sui', $text, $descr);
				if($descr)$item->descr = $this->txt($descr[1]);
				
				preg_match_all('#<img class="href" border="0" src="img/catalogue/aw11/(.+?).jpg">#sui', $text, $images, PREG_SET_ORDER);
				if(!$images)
				{
					preg_match('#<img class="href" id="imgItem" border="0" src="img/catalogue/aw11/medium/(.+?).jpg">#sui', $text, $main_image);
					$images = array($main_image);
				}
				
				foreach($images as $image_value)
				{
					$image = new ParserImage();
					
					$image->id = $image_value[1];
					$image->url = $this->shopBaseUrl."/img/catalogue/aw11/big/".$image->id.".jpg";
					
					$this->httpClient->getUrlBinary($this->urlencode_partial($image->url));
					$image->path = $this->httpClient->getLastCacheFile();
					
					$item->images[] = $image;
				}
				
				$collection->items[] = $item;
			}
			
		}
		
		
		
		$base[] = $collection;

		return $this->saveItemsResult ($base);
	}
	
	
	
	private function get_shop_timetable($text)
	{
		preg_match_all('#<td style="text-align: center;">(.+?)<br />(.+?)</td>#sui', $text, $items, PREG_SET_ORDER);
		
		$start_a = $items[0][1];
		$start_b = $items[0][2];
		$start = 0;
		
		$result = "";
		
		for($i = 1; $i <= count($items); $i++)
		{
			if($i < count($items))
			{
				$cur_a = $items[$i][1];
				$cur_b = $items[$i][2];
			}
			if($i == count($items) || $start_a != $cur_a || $start_b != $cur_b)
			{
				if($i - $start > 1)
					$result.= $this->week_days[$start]." - ".$this->week_days[$i - 1];
				else
					$result.= $this->week_days[$start];
				$result.=": ".$start_a. "-".$start_b;
				if($i < count($items))
				{
					$start_a = $cur_a;
					$start_b = $cur_b;
					$start = $i;
				}
				$result .= "\n";
			}
				
		}
		
		return $result;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text =  $this->httpClient->getUrlText($this->shopBaseUrl."?part=store");
		preg_match_all('#\&nbsp;\&nbsp;\&raquo;\&nbsp;<a href="(.+?)">(.+?)</a><br />#sui', $text, $cities, PREG_SET_ORDER);
		
		foreach($cities as $city)
		{
			$url = $this->shopBaseUrl.$city[1];
			if(mb_strpos($url, "part") === false)continue;
			
			$text = $this->httpClient->getUrlText($url);
			
			preg_match_all('#<table style="width: 750px;" cellspacing="1" cellpadding="1">(.+?)<table(.+?)</table>#sui', $text, $shops, PREG_SET_ORDER);


			foreach($shops as $shop_value)
			{
				$text = $shop_value[1];
				
				$shop = new ParserPhysical();
				
				preg_match('#<span class="b">Адрес:</span>(.+?)<br />#sui', $text, $address);
				preg_match('#<span class="b">Телефон:</span>(.+?)<br />#sui', $text, $phone);
				
				if($address)$shop->address = $address[1];
				if($phone)$shop->phone = $phone[1];
				
				$shop->timetable = $this->get_shop_timetable($shop_value[2]);
				$shop->city = $this->txt($city[2]);
				
				if($this->address_have_prefix($shop->address))
				{

					$name = mb_substr($shop->address, 0, mb_strpos($shop->address, '",') + 1);
					$shop->address = mb_substr($shop->address, mb_strpos($shop->address, '",') + 2)." ,".$name;
					
				}
				
				$base[] = $shop;
			}
			
		}
				
		return $this->savePhysicalResult ($base);
	}
}
