<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_detmir_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.detmir.ru/";
	
	public function loadItems () 
	{
		$base = array ();
        
        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        
        preg_match_all('#<span class="lev1"><a href="/(catalog/index/name/(.+?)/)">(.+?)</a></span>#sui', $text, $collections, PREG_SET_ORDER);
        
        foreach($collections as $collection_item)
        {
            $collection = new ParserCollection();
            
            $collection->id = $collection_item[2];
            $collection->url = $this->shopBaseUrl.$collection_item[1];
            $collection->name = $collection_item[3];
            
            $text = $this->httpClient->getUrlText($collection->url);
            
            preg_match_all('#<li><a href=".+?">(\d+)</a></li>#sui', $text, $pages, PREG_SET_ORDER);

            $pages = $pages ? $pages[count($pages)-1][1] : 1;
            
            for($page = 1; $page <= $pages; $page++)
            {
                $url = $collection->url."page/$page";
                
                $text = $this->httpClient->getUrlText($url);
                
                preg_match_all('#<div class="cat-img"><a href="/(product/index/id/(\d+)/)">#sui', $text, $items, PREG_SET_ORDER);
                
                foreach($items as $item_value)
                {
                    $item = new ParserItem();
                    
                    $item->id = $item_value[2];
                    $item->url = $this->shopBaseUrl.$item_value[1];
                    
                    $text = $this->httpClient->getUrlText($item->url);
                    
                    preg_match('#<h2>(.+?)</h2>#sui', $text, $name);
                    if($name)$item->name = $name[1];
                    
                    preg_match('#<div class="b-content-item">\s*<div class="clear">.+?</div>(.+?)</div>#sui', $text, $descr);
                    if($descr)$item->descr = $this->txt($descr[1]);
                    
                    preg_match('#Код\&nbsp;товара\s*(\d+)<br/>#sui', $text, $articul);
                    if($articul)$item->articul = $articul[1];
                    
                    preg_match('#Производитель:\s*(.+?)<br/>#sui', $text, $brand);
                    if($brand)$item->brand = $brand[1];
                    
                    preg_match('#Страна:\s*(.+?)<br/>#sui', $text, $made_in);
                    if($made_in)$item->made_in = $made_in[1];
                    
                    preg_match('#Цвет:\s*(.+?)<br/>#sui', $text, $color);
                    if($color)$item->colors[] = $color[1];
                    
                    preg_match('#Вес:\s*(.+?)<br/>#sui', $text, $weight);
                    if($weight)$item->weight = substr($weight[1],0,-1);
                    
                    preg_match('#<span class="price2"><b>(.+?)</b> руб.</span>#sui', $text, $price);
                    if($price)
                    {
                        $item->price = $this->txt($price[1]);
                        $item->price = str_replace(" ", "", $item->price);
                    }

                    $item->bStock = mb_strpos($text, 'Товар в наличии') !== false ? 1 : 0;
                    
                    preg_match_all('#<span class="bread-drop">(.+?)</span>#sui', $text, $categories, PREG_SET_ORDER);
                    foreach($categories as $category)
                        $item->categ[] = $this->txt($category[1]);
                    
                    preg_match_all('#<img src="/proxy/cache/84x84/(.+?).jpg"#sui', $text, $images, PREG_SET_ORDER);
                    foreach($images as $image_value)
                    {
                        $image = new ParserImage();
                        
                        $image->id = $image_value[1];
                        $image->url = $this->shopBaseUrl."proxy/cache/320x495/".$image->id.".jpg";
                        $image->type = "jpg";
                        $this->httpClient->getUrlBinary($image->url);
                        $image->path = $this->httpClient->getLastCacheFile();

                        $item->images[] = $image;
                    }
                    
                    $collection->items[] = $item;
                    
                }

            }

            $base[] = $collection;
        }
	
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
        
        $text = $this->httpClient->getUrlText($this->shopBaseUrl."pages/shops");
        preg_match_all('#<div class="region">.+?<h1>(.+?)</h1>(.+?)</div>\s*</div>#sui', $text, $cities, PREG_SET_ORDER);
        
        foreach($cities as $city)
        {
            $text = $city[2];
            $city = $city[1];
            
            preg_match_all('#<div class="shop">(.+?)</div>#sui', $text, $shops, PREG_SET_ORDER);
            
            foreach($shops as $shop_item)
            {
                $text = $shop_item[1];
                $shop = new ParserPhysical();
                
                $shop->city = $city;
                
                preg_match('#<strong>Адрес магазина:</strong>(.+?)(<br\s/>|</p>)#sui', $text, $address);
                if($address)$shop->address = $this->txt($address[1]);
                
                preg_match('#<strong>Телефон:</strong>(.+?)(<br\s/>|</p>)#sui', $text, $phone);
                if($phone)$shop->phone = $this->txt($phone[1]);
                
                preg_match('#<strong>Время работы:</strong>(.+?)(<br\s/>|</p>)#sui', $text, $timetable);
                if($timetable)$shop->timetable = $this->txt($timetable[1]);
                
                if(mb_substr($shop->address, 0, 2) == "г.")
                {
                    $shop->address = mb_substr($shop->address, 2);
                    $pos = mb_strpos($shop->address, ",");
                    $shop->city = mb_substr($shop->address, 0, $pos);
                    $shop->address = mb_substr($shop->address, $pos + 1);
                }
                
                $base[] = $shop;
            }
            
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
        
        $pages = array("news", "actions");
        
        foreach($pages as $page)
        {
            $url = $this->shopBaseUrl.$page;
            
            $text = $this->httpClient->getUrlText($url);
            
            preg_match_all('#<p class="date">(.+?)</p>\s*<h4><a href="/((?:news|actions)/item/id/(\d+)/)">(.+?)</a></h4>\s*<p.*?>(.+?)</p>#sui', $text, $news, PREG_SET_ORDER);
            
            foreach($news as $news_value)
            {
                $news_item = new ParserNews();
                
                $news_item->id = $news_value[3];
                $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
                $news_item->urlShort = $url;
                $news_item->header = $this->txt($news_value[4]);
                $news_item->contentShort = $news_value[5];
                $news_item->date = $this->date_to_str($news_value[1]);
                
                $text = $this->httpClient->getUrlText($news_item->urlFull);
                
                preg_match('#<div class="b-newsitem">.+?</h4>(.+?)</div>#sui', $text, $content);
                $news_item->contentFull = $content[1];

                $base[] = $news_item;
            }

        }
        
		return $this->saveNewsResult ($base); 
	}
}
