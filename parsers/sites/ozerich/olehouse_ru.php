<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_olehouse_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.olehouse.ru/";
	
	public function loadItems () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?page=cat&pid=0");

        preg_match_all('#<td colspan="2" valign="middle"><a href="(\?page=cat\&pid=(\d+)).+?<b>(.+?)</b>#sui', $text, $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->id = $collection_value[2];
            $collection_item->url = $this->shopBaseUrl.$collection_value[1];
            $collection_item->name = $collection_value[3];

            $text = $this->httpClient->getUrlText($collection_item->url);

            preg_match_all('#<td colspan="2" valign="middle"><a href="(\?page=cat\&pid=\d+).+?<b>(.+?)</b>#sui', $text, $categories, PREG_SET_ORDER);
            if(!$categories)continue;

            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]);
                preg_match('#<table width="700" border="0" cellspacing="0" cellpadding="5" height="100%">(.+?)</table>#sui', $text, $text);
                preg_match_all('#<td colspan="2" valign="middle"><a href="(\?page=cat\&pid=(\d+)).+?<b>(.+?)</b>#sui', $text[1], $items, PREG_SET_ORDER);


                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->url = $this->shopBaseUrl.$item_value[1];
                    $item->id = $item_value[2];
                    $item->name = $this->txt($item_value[3]);
                    $item->categ = $category_name;

                    $text = $this->httpClient->getUrlText($item->url);

                    preg_match('#<p align="justify"><p align="justify"(?: class="txt")*>(.+?)</p></p>#sui', $text, $descr);
                    if($descr)$item->descr = $this->txt($descr[1]);

                    preg_match('#<tr><td valign="top"><td><img src="(.+?)"#sui', $text, $image_url);

                    $image = new ParserImage();

                    $image->url = $image_url[1];
                    $image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, mb_strrpos($image->url, '.') - mb_strrpos($image->url, '/') - 1);
                    $this->httpClient->getUrlBinary($image->url);
                    $image->path = $this->httpClient->getLastCacheFile();
                    $image->type = "jpg";

                    
                    $item->images[] = $image;
    
                    $collection_item->items[] = $item;
                }
            }
            
            
            $base[] = $collection_item;
        }

        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);

        preg_match_all('#<area alt="(.+?)".+?href="(.+?)">#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = trim($city[1]);
            $text = $this->httpClient->getUrlText($city[2]);

            $shop = new ParserPhysical();

            $shop->city = $city_name;
            $shop->id = mb_substr($city[2], mb_strrpos($city[2], '=') + 1);

            preg_match('#Адрес:(.+?)<br />#sui', $text, $address);
            $shop->address = $this->txt($address[1]);
            preg_match('#>Телефон.*?(?:</strong>|&nbsp;)(.+?)(?:</p>|<br />)#sui', $text, $phone);
            $shop->phone = $this->txt($phone[1]);
            preg_match('#(?:График|Режим) \s*работы:*(.+?)e-mail#sui', $text, $timetable);
            if($timetable)$shop->timetable = $this->txt($timetable[1]);

            preg_match('#^(\d+)#sui', $shop->address, $index);
            if($index)$shop->address=str_replace($index[1], '', $shop->address);
            $shop->address = str_replace($city_name, '', $shop->address);
            $shop->address = str_replace('г.', '', $shop->address);
            $shop->address = str_replace('Россия', '', $shop->address);
            while(mb_substr($shop->address, 0, 1) == ',' || mb_substr($shop->address, 0, 1) ==';' || mb_substr($shop->address, 0, 1) == ' ')$shop->address = mb_substr($shop->address, 1);


            preg_match('#(&amp;amp;amp;.+?&amp;amp;amp;gt;)#sui', $shop->phone, $phone_style);
            if($phone_style)$shop->phone = str_replace($phone_style[1], '',$shop->phone);
            
            $base[] = $shop;
            
        }
		
		return $this->savePhysicalResult ($base); 
	}

	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."?page=news";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td class="topHeadNew" width="690" valign="top">(.+?)</table>#sui', $text, $texts);
        foreach($texts[1] as $text)
        {
            preg_match('#<b>(.+?)\|.+?<a href="(\?page=news&id=(\d+)).+?<b>(.+?)</b>.+?<td valign="top" >(.+?)</font>#sui', $text, $info);

            $news = new ParserNews();

            $news->id = $info[3];
            $news->urlFull = $this->shopBaseUrl.$info[2];
            $news->urlShort = $url;
            $news->date = str_replace(':','.',$this->txt($info[1]));
            $news->header = $this->txt($info[4]);
            $news->contentShort = $info[5];

            $text = $this->httpClient->getUrlText($news->urlFull);
            preg_match('#<P align=justify >(.+?)<br /><br>\s*<div>#sui', $text, $text);
            $news->contentFull = $text[1];

            $base[] = $news;
            
        }

        $url = $this->shopBaseUrl."?page=action";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td class="topHeadNew" width="690" valign="top">(.+?)</table>#sui', $text, $texts);
        foreach($texts[1] as $text)
        {
            preg_match('#<a href="(http://www.olehouse.ru/(?:index.php)*\?page=news&id=(\d+)).+?<b>(.+?)</b>.+?действует с (.+?)\s.+?<td valign="top" >(.+?)</font>#sui', $text, $info);
            $news = new ParserNews();

            $news->id = $info[2];
            $news->urlFull = $info[1];
            $news->urlShort = $url;
            $news->header = $this->txt($info[3]);
            $news->contentShort = $info[5];
            $news->date = $info[4];

            $text = $this->httpClient->getUrlText($news->urlFull);
            preg_match('#<P align=justify >(.+?)<br /><br>\s*<div>#sui', $text, $text);
            $news->contentFull = $text[1];

    
            $base[] = $news;
            
        }

		
		return $this->saveNewsResult($base);
	}
}
