<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_eyekraftoptical_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.eyekraftoptical.ru/";
	
	public function loadItems () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."catalogue");
        preg_match_all('#<td><a href="/(catalogue/cat(\d+))" class=user_menu>(.+?)</a></td>#sui', $text, $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->url = $this->shopBaseUrl.$collection_value[1];
            $collection_item->id = $collection_value[2];
            $collection_item->name = $collection_value[3];

            $text = $this->httpClient->getUrlText($collection_item->url);

            preg_match('#<table width="100%" style="background: \#003c8f;">(.+?)</table>#sui', $text, $text);
            if(mb_strlen($text[1]) > 100)
                preg_match_all('#<td><a href="(.+?)" class=user_menu>(.+?)</a>#sui', $text[1], $categories, PREG_SET_ORDER);
            else
                $categories = array(array("1"=>"cat".$collection_item->id, "2"=>""));


            foreach($categories as $category)
            {
                $category_name = $category[2];

                $url = $this->shopBaseUrl."catalogue/".$category[1];
                $page = 1;

                while($page < 100)
                {
                    
                $text = $this->httpClient->getUrlText($url."/pages".$page);
                $text = str_replace('</SPAN><SPAN style="COLOR: #ff0000">', '', $text);
                preg_match_all('#<td nowrap valign=top width=100%><a href="/(catalogue/good(\d+).html)"><strong>(.+?)</strong>.+?(?=<td nowrap valign=top width=100%>|([\d\.]+) руб\.|$)#sui', $text, $items, PREG_SET_ORDER);
                if(!$items)break;

                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->url = $this->shopBaseUrl.$item_value[1];
                    $item->id = $item_value[2];
                    $item->name = $this->txt($item_value[3]);

                    $text = $this->httpClient->getUrlText($item->url);

                    preg_match('#<td class=price nowrap>(\d+)#sui', $text, $price);
                    if($price)$item->price = $price[1];

                    preg_match('#<td valign=top width=100%>(.+?)</td>#sui', $text, $descr);
                    if($descr)$item->descr = $this->txt($descr[1]);

                    if(isset($item_value[4]))
                        $item->price = str_replace('.','',$item_value[4]);

                    preg_match('#<div><A href="/(catalog/good_img_big.+?)"#sui', $text, $image);
                    if($image)$item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

                    $collection_item->items[] = $item;
                }
                $page++;
                }
            }
    
            $base[] = $collection_item;
        }

        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
    {
        return null;
	}
	
	public function loadNews ()
	{
		$base = array();

        $urls = array("about/news", "about/actions");
        foreach($urls as $url)
        {
            $url = $this->shopBaseUrl.$url;
            $text = $this->httpClient->getUrlText($url);
    
            preg_match_all('#<div class=news_archive_date>(.+?)</div>\s*<div><a href="/(about/.+?/item(\d+))" class=news_link>(.+?)</a></div>#sui', $text, $news, PREG_SET_ORDER);
    
            foreach($news as $news_value)
            {
                $news_item = new ParserNews();
    
                $news_item->date = $news_value[1];
                $news_item->date = $this->date_to_str($news_item->date);
                $news_item->urlShort = $url;
                $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
                $news_item->id = $news_value[3];
                $news_item->header = $this->txt($news_value[4]);
    
                $text = $this->httpClient->getUrlText($news_item->urlFull);
                preg_match_all('#<div class=news_anons>(.+?)</div>#sui', $text, $content);
                $news_item->contentFull = $content[1][1];
            
                $base[] = $news_item;
            }
        }

                
		return $this->saveNewsResult($base);
	}
}
