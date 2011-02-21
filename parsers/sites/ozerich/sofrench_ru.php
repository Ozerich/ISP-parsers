<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_sofrench_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.sofrench.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<td  width="100%" style="padding-left:35px;.+?><a href="/(catalog/sc(.+?).php)">.+?alt="(.+?)"#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[2];
            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($collection->url);

            preg_match_all('#onClick=\'window.open\("((\d+)\.php)"#sui', $text, $items, PREG_SET_ORDER);
            foreach($items as $item_value)
            {
                $item = new ParserItem();

                $item->id = $item_value[2];
                $item->url = $this->shopBaseUrl."catalog/".$item_value[1];

                $text = $this->httpClient->getUrlText($item->url);

                preg_match('#<img src="/(.+?)"#sui', $text, $image);
                $item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

                preg_match('#<table width="100%" border="0" cellspacing="0" cellpadding="0" style="color:\#C8C8C8"  class="DETAIL">(.+?)</table>#sui', $text, $descr_text);
                $descr_text = $descr_text[1];

                preg_match_all('#<tr>(.+?)</tr>#sui', $descr_text, $info);
                $info = $info[1];

                preg_match('#<td colspan="2" nowrap="nowrap" align="center" style="padding-bottom:10px"><strong style="font-size:18px">(.+?)</strong> ((\d+)р\.)*</td>#sui', $info[0], $price);
                $item->name = $this->txt($price[1]);
                if(isset($price[3]))$item->price = $price[3];

                preg_match('#<td colspan="2" nowrap="nowrap" align="center">Арт: (.+?)</td>#sui', $info[1], $articul);
                if($articul)$item->articul = $articul[1];

                $item->structure = $this->txt($info[2]);

                $size = mb_substr($this->txt($info[3]), 2);
                if($size)$item->sizes[] = $size;

                $item->descr = '';

                if($item->name == '')continue;


                $collection->items[] = $item;
            }

            $base[] = $collection;
        }
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $this->add_address_prefix('"Европа Сити Молл"');
        $this->add_address_prefix('"Орловские ряды"');
		$base = array ();
        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match('#<table border="0" style="margin:10px" width="100%" cellpadding="0" cellspacing="0">(.+?)</table>#sui', $text, $text);
        preg_match_all('#<a href="(.+?)">(.+?)</a>#sui', $text[1], $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/".$city[1]);
            $city_name = $city[2];
            preg_match_all('#<div style="PADDING-LEFT: 23px; FONT-SIZE: 13px; LINE-HEIGHT: 22px">\s*<a href=".+?">(.+?)</a>#sui', $text, $shops);
            foreach($shops[1] as $text)
            {
                if($text == '.' || $text == 'Скоро открытие')continue;
                $shop = new ParserPhysical();

                $shop->city = $city_name;
                $shop->address = $this->txt($text);

                preg_match('#тел.([\s\d\(\)-]+)#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address, 'тел.'));
                }
                preg_match('#33, ([\s\d\(\)-]*)$#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = str_replace($phone[0], '', $shop->address);
                }

                $shop->address = $this->address($shop->address);

                if(mb_substr($shop->phone, mb_strlen($shop->phone) - 2, 1) == '(')
                    $shop->phone = mb_substr($shop->phone, 0, -2);

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 1);
                    $name .= mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 1).", ".$name;
                }

                $shop->address = $this->address($shop->address);

                if(mb_substr($shop->address, 0, 3) == 'ЦУМ')
                    $shop->address = mb_substr($shop->address, 4).", ЦУМ";
                
                $base[] = $shop;
            }

        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/";
        $text = $this->httpClient->getUrlText($url);

        preg_match('#<tr>\s*<td width="155">(.+?)</td>\s*<td style="font-size:16px">(.+?)</td>.+?<td valign="top" style="padding-top:13px">(.+?)</td>#sui', $text, $news);
        $news_item = new ParserNews();
        $news_item->id = 1;
        $news_item->date = $this->date_to_str($news[1]);
        $news_item->header = $this->txt($news[2]);
        $news_item->contentShort = $news[2];
        $news_item->contentFull = $news[3];
        $news_item->urlFull = $news_item->urlShort = $url;
        $base[] = $news_item;

        preg_match_all('#<td colspan="2" style="padding:5px 0px"><strong>(.+?)</strong></td>(.+?)(?=<td colspan="2" style="padding:5px 0px">|</tbody>)#sui', $text, $years, PREG_SET_ORDER);
        foreach($years as $year)
        {
            $year_value = $year[1];
            $text = $year[2];

            preg_match_all('#<td nowrap="nowrap" style="padding-right:2px">(.+?)</td>.+?<td style="padding:2px 0px">\s*<a href="((.+?)\.php)">(.+?)<#sui', $text, $news, PREG_SET_ORDER);
            foreach($news as $news_value)
            {
                $news_item = new ParserNews();

                $news_item->date = $this->date_to_str($news_value[1]);
                $news_item->urlShort = $url;
                $news_item->urlFull = $this->shopBaseUrl."news/".$news_value[2];
                $news_item->id = $news_value[3];
                $news_item->header = $this->txt($news_value[4]);
                $news_item->contentShort = $news_value[4];

                $news_item->date = mb_substr($news_item->date, 0, mb_strrpos($news_item->date, '.') + 1).$year_value;

                $text = $this->httpClient->getUrlText($news_item->urlFull);
                preg_match('#<div align="justify">(.+?)</div>#sui', $text, $content);
                if(!$content)preg_match('#<td valign="top" style="padding-top:13px">(.+?)</td>#sui', $text, $content);
                $news_item->contentFull = $content[1];

                $base[] = $news_item;
            }

        }

        
		return $this->saveNewsResult($base);
	}
}
