<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_td_charm_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.td-charm.ru/";
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText("http://www.grodes.ru/?menu=ru-products-goods");
        preg_match_all('#<td align="center"><a href="/(\?menu=ru-products-goods-(.+?))">#sui', $text, $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[2];
            $collection->url = "http://www.grodes.ru/".$collection_value[1];

            $text = $this->httpClient->getUrlText($collection->url);

            preg_match('#<h4>(.+?)</h4>#sui', $text, $name);
            if(!$name)continue;
            $collection->name = $name[1];

            preg_match('#</h4><table width="100%" border="0" cellspacing="0" cellpadding="0">(.+?)</table>#sui', $text, $text);
            preg_match_all('#<td align="center"><p>&nbsp;</p><img src="(.+?)" alt="" width="180" height="180" border="0"><br>(.+?)</td>#sui', $text[1], $items, PREG_SET_ORDER);

            foreach($items as $item_value)
            {
                $item = new ParserItem();

                $item->descr = $this->txt($item_value[2]);
                $item->images[] = $this->loadImage("http://www.grodes.ru/".$item_value[1]);

                $item->url = $collection->url;

                preg_match('#Арт.: (.+?),#sui', $item->descr, $articul);
                $item->articul = $articul[1];
                $item->descr = trim(str_replace($articul[0],'',$item->descr));

                preg_match('#вес: ([\d\.]+)#sui', $item->descr, $weight);
                if($weight)$item->weight = $weight[1];


                $info = explode("<br>",$item_value[2]);

                $item->material = $this->txt($info[1]);

                if($info[2] != "&nbsp")$item->structure = $this->txt($info[2]);
                
                $item->id = $item->articul;
                 
                
                $collection->items[] = $item;
            } 
            $base[] = $collection;
        }
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText("http://www.zoppini.ru/?menu=ru-shopping");
        preg_match('#<table border="0" cellspacing="0" cellpadding="0">(.+?)</table>#sui', $text, $text);
        preg_match_all('#<b>(.+?)</b>(.+?)(</td><td width="4%" style="padding:10;">&nbsp;</td>|<p id="normal">&nbsp;</p>)#sui', $text[1], $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $city[1];
            $text = str_replace("<li>","</li><li>",$city[2]);
         //   print_r($text);
                
            if(mb_strpos($text, "<ul>") !== false)
                preg_match_all('#<li>(.+?)(?:</li>|</ul>)#sui', $text, $shops, PREG_SET_ORDER);
            else
                preg_match_all('#<p id="normal">(.+?)(</p>|$)#sui', $text, $shops, PREG_SET_ORDER);

            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->city = $city_name;
                $shop->address = $shop_value[1];
                if(mb_strpos($shop->address, "интернет-магазин") !== false)continue;

                preg_match('#г\. (.+?),#sui', $shop->address, $city);
                if($city)
                {
                    $shop->city = $city[1];
                    $shop->address = str_replace($city[0], '', $shop->address);
                }

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ","));
                    $shop->address = trim(mb_substr($shop->address, mb_strpos($shop->address, ",") + 1)).", ".$name;
                }

                preg_match('#т(?:ел)*\.(.+?)(?:,|$)#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = str_replace($phone[0], '', $shop->address);
                }

                $shop->address = $this->address($shop->address);
                                
                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}

    private function reverse_date($date)
    {
        preg_match('#(.+?)[?:\.\-](.+?)[?:\.\-](.+?)$#sui', $date, $date);
        return $date[3].".".$date[2].".".$date[1];
    }
    
	public function loadNews ()
	{
		$base = array();
        $url = "http://www.grodes.ru/";
        
        $text = $this->httpClient->getUrlText($url);
        preg_match_all('#<tr valign="top"><td><p><b>(.+?)</b></p><p>(.+?)</p><p><a href="/(\?menu=ru-main-news-(\d+))"#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[4];
            $news_item->date = $this->reverse_date($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $url.$news_value[3];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<br>&nbsp;</p>(.+?)<p><br>&nbsp;</p>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }

       

        $text = $this->httpClient->getUrlText("http://www.grodes.ru/?menu=ru-events");
        preg_match_all('#<h4>(.+?)</h4><h2 style="text-align:left; color:\#D00;">(.+?)</h2></p><p><a href="/(\?menu=ru-main-news-(\d+))"#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[4];
            $news_item->date = $this->reverse_date($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $url.$news_value[3];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<br>&nbsp;</p>(.+?)<p><br>&nbsp;</p>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }

        $url = "http://www.zoppini.ru/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td><p><b>(.+?)</b></p><p>(.+?)</p><p><a href="/(\?menu=ru-main-news-(\d+))"#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[4];
            $news_item->date = $this->reverse_date($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $url.$news_value[3];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<p>&nbsp;</p><p id="normal">(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }      
        
        
		return $this->saveNewsResult($base);
	}
}
