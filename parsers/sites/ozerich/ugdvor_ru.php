<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ugdvor_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ugdvor.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }
  
	public function loadItems () 
	{
        $base = array();

        $this->shopBaseUrl = "http://kiwi.ugdvor.ru/";
        $text = $this->httpClient->getUrlText($this->shopBaseUrl."h/search.html");

        preg_match_all('#<li class="g"><a href="/(h/(\d+)/search.html)">(.+?)\(#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[2];
            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($collection->url);

            preg_match('#<ul class="l2">(.+?)</ul>#sui', $text, $text);
            preg_match_all('#<li class="g"><a href="/(.+?)">(.+?)\(#sui', $text[1], $categories, PREG_SET_ORDER);

            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1], array("byPage"=>10000));
                preg_match_all('#<td class="img">(.*?)</td><td class="name_t">(.+?)</td><td class="chena">(.+?)\.#sui', $text, $items, PREG_SET_ORDER);
                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->name = $this->txt($item_value[2]);
                    $item->price = str_replace(' ', '', $this->txt($item_value[3]));

                    $item->url = $this->shopBaseUrl.$category[1];
            
                    preg_match('#src="(.+?)"#sui', $item_value[1], $image);
                    if($image)
                       $item->images[] = $this->loadImage(str_replace('tc','ic',$image[1]));

                    preg_match('#Код: (\d+)$#sui', $item->name, $articul);
                    if($articul)
                    {
                        $item->name = str_replace($articul[0], '', $item->name);
                        $item->articul = $this->txt($articul[1]);
                    }

                    preg_match('#(\d+)#sui', $item->name, $code);
                    if($code)
                        $item->name = trim(str_replace($code[1],'', $item->name));

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

        $this->shopBaseUrl = "http://www.ugdvor.ru/";
        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");

        preg_match('#<tbody>(.+?)</table>#sui', $text, $text);
        preg_match_all('#<tr><td>(.*?)</td><td>&nbsp;</td><td>(.*?)</td><td>(.*?)</td><td>(.*?)</td></tr>#sui', $text[1], $shops, PREG_SET_ORDER);

        foreach($shops as $shop_value)
        {
            $shop_item = new ParserPhysical();

            $shop_item->city = "Москва";
            $shop_item->address = str_replace('МО, Люберецкий р-он, ','',$this->txt($shop_value[2]));
            if($shop_item->address == "")continue;
            $shop_item->timetable = $this->txt($shop_value[3]);

            preg_match('#г\.(.+?),#sui', $shop_item->address, $city);
            if($city)
            {
                $shop_item->city = $this->txt($city[1]);
                $shop_item->address = trim(str_replace($city[0],'',$shop_item->address));
            }

            $shop_item->address = str_replace("ВО,","",$shop_item->address);
    
            $base[] = $shop_item;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td class="contentheading" width="100%">(.+?)</td>.+?<table class="contentpaneopen">(.+?)</table>#sui', $text, $news,
            PREG_SET_ORDER);


        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->header = $this->txt($news_value[1]);
            $news_item->contentShort = $news_value[2];

            preg_match('#<a class="readmore-link" href="/(.+?)"#sui', $news_item->contentShort, $url_full);
            if($url_full)
            {
                $content_text = $this->httpClient->getUrlText($this->shopBaseUrl.$url_full[1]);
                preg_match('#<td valign="top">(.+?)</table>#sui', $content_text, $content);
                if($content)
                    $news_item->contentFull = $content[1];
            }
            $base[] = $news_item;
        }

        preg_match_all('#<a class="blogsection" href="/(.+?)">(.+?)</a>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->contentShort = $news_value[2];
            $news_item->header = $this->txt($news_value[2]);
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1];
            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<td valign="top">(.+?)</td>#sui', $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
