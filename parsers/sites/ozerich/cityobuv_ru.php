<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_cityobuv_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = "http://www.cityobuv.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (1);

    }


	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."rubrics/collections/collections_articles.shtml");
        preg_match('#<H2>коллекции обуви</H2>(.+?)</TABLE>#sui', $text, $text);
        preg_match_all('#<DIV class="menu" id="(.+?)"><A href="/(.+?)" title=".+?">(.+?)</A></DIV>#sui', $text[1],
            $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[1];
            $collection->url = $this->shopBaseUrl.$collection_value[2];
            $collection->name = $this->txt($collection_value[3])." коллекция";

            $text = $this->httpClient->getUrlText($collection->url);

            preg_match_all('#<li>\s*<a href="\.\./\.\./(.+?)">\s*<img src=".+?".+?title="(.+?)" longdesc="(.+?)".*?></a>#sui',
                $text, $items, PREG_SET_ORDER);
            foreach($items as $item_value)
            {
                $item = new ParserItem();

                $item->images[] = $this->loadImage($this->shopBaseUrl.$item_value[1]);
                $item->id = $item->images[0]->id;
                $item->name = $this->txt($item_value[2]);

                preg_match('#Арт: (.+?) Цена: (.+?)\.-(?:(.+?)%)*#sui', $item_value[3], $info);
                if(!$info)continue;
                $item->articul = $this->txt($info[1]);
                $item->price = $this->txt($info[2]);
                $item->url = $collection->url;
                if(isset($info[3]))
                    $item->discount = $this->txt($info[3]);

                $collection->items[] = $item;
            }

            $base[] = $collection;
        }

		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
        $text = $this->httpClient->getUrlText($this->shopBaseUrl."rubrics/shops/moscow/moscow_shops.shtml");
        preg_match('#<H2>Магазины</H2>(.+)#sui', $text, $text);

        preg_match_all('#<DIV class="menu" id=".+?"><A href="/(.+?)"\s*title=".+?">(.+?)</A></DIV>#sui', $text[1], $cities,
            PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $this->txt($city[2]);
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
            if(!$text)continue;
            
            preg_match('#<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" CLASS="tab2">.+?</TR>(.+?)</TABLE>#sui', $text, $text);

            preg_match_all('#<TD.*?>(.*?)</TD>\s*<TD><A href=".*?(shops.+?)".*?>(.*?)</A></TD>\s*<TD>(.*?)</TD>#sui',
                $text[1], $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->city = str_replace('и Московская обл.','',$city_name);

                $shop->address = $this->txt($shop_value[3]);
                $shop->phone = $this->txt($shop_value[4]);

                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_value[2]);
                preg_match('#<dt>Режим работы:</dt>(.+?)</dl>#sui', $text, $timetable);
                if(!$timetable)preg_match('#<TD align="right">\s*<P class=(?:RVPS323240|RVPS2(?:3|0))>(.+?)</td>#sui', $text, $timetable);
                if(!$timetable)preg_match('#<P align="left" class=text>режим работы:&nbsp;</P>(.+?)</td>#sui', $text, $timetable);
                if(!$timetable)preg_match('#<SPAN class=RVTS323241>(.+?)</SPAN>#sui', $text, $timetable);
                if($timetable)
                    $shop->timetable = $this->txt($timetable[1]);

                if(mb_strpos($shop->timetable, "Как добраться") !== false)
                    $shop->timetable = mb_substr($shop->timetable, 0, mb_strpos($shop->timetable, "Как добраться"));

                $shop->timetable = preg_replace("#режим работы:#sui","",$shop->timetable);

                preg_match('#г\.(.+?),#sui', $shop->address, $city);
                if($city)
                {
                    $shop->city = $this->txt($city[1]);
                    $shop->address = str_replace($city[0],"",$shop->address);
                }

                $shop->address = $this->fix_address($shop->address);

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address,'"') + 1);
                    $name .= mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address,'"') + 1).", ".$name;
                }

                $shop->address = $this->address($shop->address);



                $base[] = $shop;
            }

        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/news.shtml";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<h4>(.+?)</h4>(.+?)(?=<h4>|</td>)#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->date_to_str($this->txt($news_value[1]));
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;

            if($news_item->date == "" || $this->txt($news_item->contentShort) == "")
                continue;

            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
