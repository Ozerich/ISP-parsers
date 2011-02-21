<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_premaman_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.premaman.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."katalog/");
        preg_match_all('#<li><a class=\'sublink\' href="/(katalog/(.+?)/)".+?>(.+?)</a>#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->id = $collection_value[2];
            $collection->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($collection->url);
            preg_match_all('#<li><a class=\'sublink\' href="/(katalog/.+?/)".+?>(.+?)</a>#sui', $text, $categories, PREG_SET_ORDER);
            if(!$categories)
                $categories = array(array('1'=>$collection_value[1], '2'=>''));
            foreach($categories as $category)
            {
                $category_name = $category[2];
                $url = $this->shopBaseUrl.$category[1];
                $offset = 0;
                while($offset < 500)
                {
                    $text = $this->httpClient->getUrlText($url."~".$offset);
                    if($offset > 0 && mb_strpos($text, '<span>Страницы: </span>') === false)
                        break;

                    preg_match_all("#<div class='gal_cell_upper'>\s*<a href='/(.+?)/'.+?<p class='gal_cell_header'>(.+?)</p>#sui",
                        $text, $items, PREG_SET_ORDER);
                    if(!$items)
                        break;
                    foreach($items as $item_value)
                    {
                        $item = new ParserItem();

                        $item->id = mb_substr($item_value[1], mb_strrpos($item_value[1],'/') + 1);
                        $item->url = $this->shopBaseUrl.$item_value[1].'/';
                        $item->name = $this->txt($item_value[2]);
                        if($category_name != "")
                            $item->categ = $category_name;

                        $text = $this->httpClient->getUrlText($item->url);
                        preg_match('#<p>\s*<img.+?src="(.+?)"#sui', $text, $image);
                        $item->images[] = $this->loadImage($image[1]);

                        preg_match('#\(арт(?:\.|\:)(.+?)\)#sui', $item->name, $articul);
                        if($articul)
                        {
                            $item->articul = $this->txt($articul[1]);
                            $item->name = str_replace($articul[0],'',$this->txt($item->name));
                        }

                        $collection->items[] = $item;
                    }


                    $offset+=16;
                }
            }


            $base[] = $collection;
        }

		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."magazini/magazini_v_moskve/");
        preg_match_all('#<li><a href="/(.+?)">#sui', $text, $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            $shop_item = new ParserPhysical();

            $shop_item->city = "Москва";

            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_value[1]);

            preg_match('#<strong>Адрес:</strong>(.+?)<br /><br />#sui', $text, $address);
            $shop_item->address = $this->txt($address[1]);

            preg_match('#<strong>Режим работы:</strong>(.+?)</p>#sui', $text, $timetable);
            $shop_item->timetable = $this->txt($timetable[1]);

            preg_match('#тел:(.+?)$#sui', $shop_item->address, $phone);
            if($phone)
            {
                $shop_item->phone = $this->txt($phone[1]);
                $shop_item->address = str_replace($phone[0],'',$shop_item->address);
            }

            $shop_item->address = str_replace($shop_item->city.', ', '', $shop_item->address);

            $base[] = $shop_item;
        }

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."magazini/magazini_v_regionah/");
        preg_match_all('#<li>(.+?)</li>#sui', $text, $shops);
        foreach($shops[1] as $text)
        {
            $shop = new ParserPhysical();

            preg_match('#г\.(.+?)(?:,|(?=ул))#sui', $text, $city);
            if($city)
            {
                $shop->city = $this->txt($city[1]);
                $text = str_replace($city[0], '', $text);
            }

            preg_match('#тел(?:\.*\:*\.*)(.+?)(,.+?)*$#sui', $text, $phone);
            if($phone)
            {
                $shop->phone = $this->txt($phone[1]);
                $text = str_replace($phone[0], '', $text);
            }


            $shop->address = $this->address($text);

            $base[] = $shop;
        }



		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."novosti/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all("#<a href=\"/(novosti/(\d+)/)\" class='news_link'>.+?<em>(.+?)</em>.+?<p>(.+?)</p></b>#sui",
            $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1];
            $news_item->id = $news_value[2];
            $news_item->date = str_replace('/','.',$news_value[3]);
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match("#<h1 id='header_second'>.+?</h1>(.+?)</div>#sui", $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
