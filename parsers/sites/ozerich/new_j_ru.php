<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_new_j_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.new-j.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }

    private function parseItem($url, $category_name)
    {
        $url = str_replace('&amp;','&',$url);
        $text = $this->httpClient->getUrlText($url);

        $item = new ParserItem();

        $item->url = $url;
        $item->categ = $category_name;

        preg_match('#<div style="text-align: center;">\s*<h1>(.+?)</h1>#sui', $text, $name);
        $item->name = $this->txt($name[1]);

        preg_match('#product_id=(\d+)#sui', $url, $id);
        $item->id = $id[1];


        preg_match('#<span style="color:\#f0f0f0;" class="productPrice">.+?:\s*(.+?)\.#sui', $text, $price);
        if($price)$item->price = str_replace(' ','',$price[1]);

        preg_match('#<td width="9%" rowspan="3"><a href="(.+?)"#sui', $text, $image);
        $image =  $this->loadImage($image[1], false);
        if(!$image)
        {
            preg_match('#<img src="(.+?)"#sui', $text, $image);
            $image = $this->loadImage($image[1], false);
        }

        if($image)
            $item->images[] = $image;

        preg_match('#<strong>Артикул:</strong>(.+?)<br(?: /)*>#sui', $text, $articul);
        if($articul)$item->articul = $this->txt($articul[1]);

        preg_match('#<b>Материал(?: верха)*:</b>(.+?)<br(?: /)*>#sui', $text, $material);
        if($material)$item->material = $this->txt($material[1]);

        preg_match('#<b>(?:Страна|Производство|Производитель):</b>(.+?)<br(?: /)*>#sui', $text, $made_in);
        if($made_in)$item->made_in = $this->txt($made_in[1]);

        preg_match('#<b>Цвет:</b>(.+?)<br(?: /)*>#sui', $text, $color);
        if(!$color)preg_match('#<b>Расцветки:</b>(.+?)<br(?: /)*>#sui', $text, $color);
        if($color)
        {
            $color = $this->txt($color[1]);
            $colors = explode(',',$color);
            foreach($colors as $color)
            {
                if($color[0] == ':')$color = mb_substr($color, 1);
                $item->colors[] = $color;
            }
        }

        preg_match('#<select class="inputboxattrib(.+?)</select>#sui', $text, $size_text);
        if($size_text)
        {
            preg_match_all('#<option value=".+?">(.+?)</option>#sui', $size_text[1], $sizes);
            foreach($sizes[1] as $size)
                $item->sizes[] = $this->txt($size);
        }

        preg_match('#<strong>Артикул:</strong>.+?<br>(.+?)<strong>Производители:</strong>#sui', $text, $descr);
        if($descr)$item->descr = $this->txt($descr[1]);

        if(mb_strpos($item->descr, "Артикул"))
            $item->descr = mb_substr($item->descr, 0, mb_strpos($item->descr, 'Артикул'));

        preg_match('#<strong>Производители:</strong>(.+?)<br\s*/>#sui', $text, $brands);
        $item->brand = mb_substr($this->txt($brands[1]), 2, -2);


        return $item;
    }
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<div class="menu">(.+?)</div></div></div#sui', $text, $text);
        preg_match_all('#<h3 class="s5_am_toggler"><span class="s5_accordion_menu_left" /><a class="mainlevel" href="/(.+?)"><span>(.+?)</span>.+?<ul class="s5_am_innermenu">(.+?)</ul>#sui', $text[1], $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->id = $collection_value[1];
            $collection_item->url = $this->shopBaseUrl.$collection_value[1].'/';
            $collection_item->name = $this->txt($collection_value[2]);

            $text = $collection_value[3];
            preg_match_all('#<li class="s5_am_inner_li"><a class="mainlevel" href="/(.+?)"><span>(.+?)</span></a></li>#sui', $text, $categories, PREG_SET_ORDER);


            foreach($categories as $category_value)
            {
                $category_name = $this->txt($category_value[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category_value[1]);

                 preg_match_all('#<div  style="font-size:14px; text-align:center; font-weight:bold;"><a href="/(.+?)"#sui', $text, $items);
                 foreach($items[1] as $item_url)
                    $collection_item->items[] = $this->parseItem($this->shopBaseUrl.$item_url,$category_name);

            }
    
            $base[] = $collection_item;
        }
        
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
       return null;
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<h2 class="contentheading">\s*<a href="/(news/(.+?))" class="contentpagetitle">(.+?)</a>\s*</h2>\s*<div class="newsitem_text">(.+?)</div>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1];
            $news_item->id = mb_substr($news_value[2],0,mb_strpos($news_value[2], '-'));
            $news_item->header = $this->txt($news_value[3]);
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="news_item_article">(.+?)<div class="newsitem_text_u">#sui', $text, $content);
            $news_item->contentFull = $content[1];
        
            $base[] = $news_item;
        }
        
		return $this->saveNewsResult($base);
	}
}
