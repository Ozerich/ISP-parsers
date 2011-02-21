<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_skvot_com extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.skvot.com/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        $this->httpClient->setRequestsPause (1);

    }

    private function parse_item($url, $category)
    {
        $item = new ParserItem();

        $item->url = $url;
        $item->categ = $category;

        $item->id = mb_substr($item->url, mb_strrpos($item->url, '/') + 1, -5);

        $text = $this->httpClient->getUrlText($item->url);
        if(!$text)return null;

        preg_match('#<h1>(.+?)</h1>#sui', $text, $name);
        $item->name = $this->txt($name[1]);

        preg_match('#<strong>Артикул</strong>:&nbsp;(.+?)<br />#sui', $text, $articul);
        if($articul)$item->articul = $this->txt($articul[1]);

        preg_match('#<strong>Материал</strong>:&nbsp;(.+?)<br />#sui', $text, $structure);
        if($structure)$item->structure = $this->txt($structure[1]);

        preg_match('#<strong>Бренд</strong>:&nbsp;(.+?)<br />#sui', $text, $brand);
        if($brand)$item->brand = $this->txt($brand[1]);

        preg_match('#<strong>Цвет</strong>:&nbsp;(.+?)<br />#sui', $text, $color);
        if($color)$item->colors[] = $this->txt($color[1]);

        preg_match_all('#<option value="\d+">(\d+)</option>#sui', $text, $sizes);
        if($sizes[1])
            foreach($sizes[1] as $size)
                $item->sizes[] = $this->txt($size);

        preg_match('#<div class="catalog-price">(.+?)\.#sui', $text, $price);
        if($price)$item->price = str_replace(' ','',$price[1]);

        preg_match_all('#<a title="Увеличить".+?href="/(.+?)"#sui', $text, $images);
        if(!$images[1])preg_match_all('#<div class="catalog-element-main-picture">\s*<img src="/(.+?)"#sui', $text, $images);
        foreach($images[1] as $image)
            $item->images[] = $this->loadImage($this->shopBaseUrl.$image);

        preg_match('#<strong>Артикул</strong>.+?<br />(.+?)<div class="clear"></div>#sui', $text, $descr);
        if($descr)$item->descr = $this->txt($descr[1]);



        return $item;
    }

	public function loadItems () 
	{
        $this->shopBaseUrl = "http://www.shop.skvot.com/";
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<div class="left-menu">(.+?)</div>#sui', $text, $text);

        preg_match_all('#<li class="nosel parent"><a href="/(catalog/(.+?)/)">(.+?)</a><ul>(.+?)</ul>#sui',
            $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->id = $collection_value[2];
            $collection->name = $this->txt($collection_value[3]);

            preg_match_all('#<a href="/(.+?)">(.+?)</a>#sui', $collection_value[4], $categories, PREG_SET_ORDER);
            foreach($categories as $category_value)
            {
                $category_name = $this->txt($category_value[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category_value[1]."?CATALOG_COUNT=10000");

                preg_match_all('#<p class="catalog-section-name">\s*<a href="/(.+?)"#sui', $text, $items);
                foreach($items[1] as $item)
                {
                    $item = $this->parse_item($this->shopBaseUrl.$item, $category_name);
                    if($item)$collection->items[] = $item;
                }
            }

            $base[] = $collection;
        }

		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $this->shopBaseUrl = "http://www.skvot.com/";
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."locations.html");
        preg_match_all('#<div style="PADDING-RIGHT: 10px; PADDING-LEFT: 10px; PADDING-BOTTOM: 10px; PADDING-TOP: 10px">(.+?)(?:<td class="newsblock"|</table>)#sui',
            $text, $rasdels);
        $cities = array(array('city'=>'Москва', 'text'=>$rasdels[1][0]),array('city'=>'Санкт-Петербург', 'text'=>$rasdels[1][1]));
        foreach($cities as $city)
        {
            preg_match_all('#<div>\d+\..*?<strong>(.+?)(?:</strong><br />|<br /></strong>|</strong></div>)(.+?)(?:<div>&nbsp;</div>|</td>)#sui',
                $city['text'], $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->city = $city['city'];
                $shop->address = $this->address($shop_value[1]);

                $text = $shop_value[2];

                preg_match('#Время работы:(.+?)(?:</div>|<br />)#sui', $text, $timetable);
                if($timetable)$shop->timetable = $this->txt($timetable[1]);

                preg_match('#Телефон:(.+?)(?:</div>|<br />)#sui', $text, $phone);
                if($phone)$shop->phone = $this->txt($phone[1]);

                $base[] = $shop;
            }
        }

        $text = $rasdels[1][2];
        preg_match_all('#\d+. <strong>(.+?)(?=\d+\. <strong>|$)#sui', $text, $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $shop->address = $this->txt($shop_value[1]);

            preg_match('#г\.(.+?),#sui', $shop->address, $city);
            if($city)
            {
                $shop->city = $this->txt($city[1]);
                $shop->address = str_replace($city[0],'',$shop->address);
            }

            preg_match('#Телефон:(.+?)(?:\.|$)#sui', $shop->address, $phone);
            if($phone)
            {
                $shop->phone = $this->txt($phone[1]);
                $shop->address = str_replace($phone[0],'',$shop->address);
            }
            preg_match('#Время работы:(.+?)$#sui', $shop->address, $timetable);
            if($timetable)
            {
                $shop->timetable = $this->txt($timetable[1]);
                $shop->address = str_replace($timetable[0],'',$shop->address);
            }

            $shop->address = preg_replace('#\(ICQ.+?\)#sui','',$shop->address);
            $shop->address = $this->address($shop->address);

            $base[] = $shop;
        }



		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
        $this->shopBaseUrl = "http://www.skvot.com/";
		$base = array();

        $url = $this->shopBaseUrl."main/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<span class="short-strory-title"><a href="(.+?)">(.+?)</a>.+?<div id=\'news-id-(\d+)\'>(.+?)</div>#sui',
            $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = $news_value[1];
            $news_item->header = $this->txt($news_value[2]);
            $news_item->id = $news_value[3];
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="phototext">(.+?)</div><br />\s*<br />#sui', $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
