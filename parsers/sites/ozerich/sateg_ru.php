<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_sateg_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.sateg.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."catalogue.html");
        preg_match_all('#div class="cat-index-right-photo">\s*<p><a href="(catalogue_(\d+)\.html)">(.+?)</a>#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[2];
            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->name = $this->txt($collection_value[3]);

            $offset = 0;
            while($offset < 1000)
            {
                $url = $this->shopBaseUrl."catalogue_".$collection->id."_".$offset.".html";
                $text = $this->httpClient->getUrlText($url);

                preg_match_all('#p class="mod-photo-album"><a href="(.+?)">(.+?)</a></p>#sui', $text, $items, PREG_SET_ORDER);
                if(!$items)break;
        
                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->url = $this->shopBaseUrl.$item_value[1];
                    $item->name = $this->txt($item_value[2]);

                    preg_match('#_(\d+)\.#sui',$item->url, $id);
                    $item->id = $id[1];

                    $text = $this->httpClient->getUrlText($item->url);

                    preg_match('#<div>Верх:(.+?)</div>#sui', $text, $material);
                    if($material)$item->material = $this->txt($material[1]);

                    preg_match('#<div>Размерный ряд:(.+?)</div>#sui', $text, $sizes);
                    if($sizes)$item->sizes[] = $this->txt($sizes[1]);

                    preg_match('#</div><div><br>(.+?)</div>#sui', $text, $descr);
                    if($descr)$item->descr = $this->txt($descr[1]);

                    preg_match('#<div id="img" style="display:none;margin-bottom:0px;"><a href="\#" onclick="return switchImg\(\)"><img src="(.+?)"#sui', $text, $image);
                    $item->images[] = $this->loadImage($image[1]);
        
                    $collection->items[] = $item;
                }

        
                
                $offset+=15;
            }

            $base[] = $collection;
        }

		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl.'page-sateg-shops.html');
        preg_match_all('#<h3>.+?</h3>(.+?)<p><a#sui', $text, $shops);
        foreach($shops[1] as $text)
        {
            $shop = new ParserPhysical();

            $shop->city = "Санкт-Петербург";

            $info = array();
            $info_ = preg_split('#<br />|</p>#sui', $text);
            foreach($info_ as $item)
                if($this->txt($item) != "")
                    $info[] = $this->txt($item);

            $use = false;
            foreach($info as $item)
            {
                if(mb_strpos($item, "тел") !== false)
                    $shop->phone = $item;
                else if(mb_strpos($item, "Часы работы") !== false)
                    $shop->timetable = str_replace('Часы работы ','',$item);
                else if(!$use)
                {
                    $use = true;
                    $shop->address = $this->fix_address($this->address($item));
                }
            }
        
            $base[] = $shop;
        }

        $text = $this->httpClient->getUrlText($this->shopBaseUrl.'page-sateg-shops-msk.html');
        preg_match_all('#<h3>.+?</h3>(.+?)<p><a#sui', $text, $shops);
        foreach($shops[1] as $text)
        {
            $shop = new ParserPhysical();

            $shop->city = "Москва";

            preg_match('#<strong>(.+?)</strong>#sui', $text, $address);
            $shop->address = $this->txt($address[1]);

            preg_match('#часы работы:*<br />(.+)#sui', $text, $timetable);
            $shop->timetable = str_replace(',','',$this->txt($timetable[1]));
        
            $base[] = $shop;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news.html";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<span class="mod-news-data">(.+?)</span> <a href="(news_0_(.+?).html)" class="mod-news-a-title">(.+?)</a></strong></p><p>(.+?)</p>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
            $news_item->id = $news_value[3];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<p class="mod-news-data-one-new">(.+?)<p><a href="news_0.html">Вернуться к списку...</a></p>#sui', $text, $content);
            $news_item->contentFull = $content[1];
        
            $base[] = $news_item;
        }

        
        
		return $this->saveNewsResult($base);
	}
}
