<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_francesco_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.francesco.ru/";
    
	public function loadItems () 
	{
        $base = array();
        //$this->httpClient->setRequestsPause(0.5);

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog.php");
        preg_match('#<td id="submenupart">(.+?)</td>#sui', $text, $text);
        preg_match_all('#<a href="(http://www.francesco.ru/catalog.php(\?mt_id=(\d+))*)">(.+?)</a>#sui', $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->id = ($collection_value[3] != "") ? $collection_value[3] : 1;
            $collection_item->name = $collection_value[4];
            $collection_item->url = $collection_value[1];

            $offset = 0;
            $url = $collection_item->url;
            while(true)
            {
                $text = $this->httpClient->getUrlText($url);

                if(mb_strlen($text) < 200)continue;

                preg_match_all('#<p class="imgblock" align="center"><a href="(http://www.francesco.ru/catalog.php\?id=(\d+))"#sui', $text, $items, PREG_SET_ORDER);
                preg_match('#<div class=\'pages*\'>.+?<a href="(.+?)"><img src=\'images/site/page_right.gif\'#sui', $text, $url);

                foreach($items as $item_value)
                {

                    $item = new ParserItem();

                    $item->id = $item_value[2];
                    $item->url = $item_value[1];
                    //$item->url = "http://www.francesco.ru/catalog.php?id=1394";

                    $text = $this->httpClient->getUrlText($item->url);
                    if(mb_strlen($text) < 100)continue;

                    preg_match('#<b style="color:\#6b1514">(.+?)</b>#sui', $text, $name);
                    $item->name = $name[1];

                    preg_match('#<p style="width:300px; height:86px; padding-bottom: 14px; padding-top:10">(.+?)</p>#sui', $text, $descr);
                    $descr_text = $descr[1];
                    $item->descr =$this->txt($descr[1]);

                    $item->descr = $this->txt(str_replace($item->name, '', $item->descr));

                    preg_match('#<i>Производство: (.+?)</i>#sui', $descr_text, $made_in);
                    $item->made_in = $this->txt($made_in[1]);

                    preg_match('#<i>Верх: .+?</i><br />\s*<i>Подкладка: .+?</i>#sui', $descr_text, $material);
                    $item->material = $this->txt($material[0]);

                    preg_match('#<i>Тип: (.+?)</i>#sui', $descr_text, $categ);
                    $item->categ = $this->txt($categ[1]);

                    $image = new ParserImage();
                    $image->url = $this->shopBaseUrl."images/userimages/model_".$item->id.".jpg";
                    $image->id = $item->id;
                    $image->type = "jpg";
                    
                   $this->httpClient->getUrlBinary($image->url);
                    $image->path = $this->httpClient->getLastCacheFile();

                    $item->images[] = $image;
                    
                    $collection_item->items[] = $item;
                }

              //  print_r($url);
                if(!$url)break;
                $url = $this->shopBaseUrl."catalog.php".$url[1];
               // print_r("\n\n".$url."\n\n");

            }
            //print_r($collection_item);exit();
            $base[] = $collection_item;
        }
    
        return $this->saveItemsResult($base);
	}

    private function parse_shop($id, $city)
    {

        $shop = new ParserPhysical;

        $shop->id = $id;
        $shop->city = $city;
        
        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops.php",array("action"=>"getAddress","id"=>$id));
        preg_match('#<td colspan="2" class="popbody">(.+?)</td>#sui', $text, $address);
        $shop->address = $this->address($address[1]);

        if($shop->address == "")return null;

        preg_match('#г\. (.+?),#sui', $shop->address, $city);
        if($city)
        {
            $shop->city = $city[1];
            $shop->address = str_replace($city[0], '', $shop->address);
        }

        preg_match('#Телефон:(.+?)(?:,|$)#sui', $shop->address, $phone);
        if($phone)
        {
            $shop->phone = $phone[1];
            $shop->address = str_replace($phone[0], '', $shop->address);
        }

        preg_match('#Время работы:(.+?)(?:,|$)#sui', $shop->address, $timetable);
        if($timetable)
        {
            $shop->timetable = $timetable[1];
            $shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address,"Время работы"));
        }

        if(mb_substr($shop->address, mb_strlen($shop->address) - 2, 1) == ",")
            $shop->address = mb_substr($shop->address, 0,  -2);

        if($this->address_have_prefix($shop->address))
        {
            $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ","));
            $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ",") + 1).", ".$name;
        }
        
        return $shop;
    }
    
	public function loadPhysicalPoints () 
	{        
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops.php");
        preg_match('#<td id="submenu">(.+?)</td>#sui', $text, $text);
        preg_match_all('#mId=(\d+)#sui', $text[1], $rasdels);
        foreach($rasdels[1] as $rasdel_id)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops.php?mId=".$rasdel_id);
            if($rasdel_id == 7)$city_name = "Москва";
            else if($rasdel_id == 6)$city_name = "Санкт-Петербург";
            else $city_name = "";
            preg_match_all('#<a href="\#" onclick="javascript: get_info\((\d+).+?>(.+?)</a>#sui', $text, $cities, PREG_SET_ORDER);
            foreach($cities as $city)
            {
                $city_id = $city[1];
                if($rasdel_id != 6 && $rasdel_id != 7)$city_name = $city[2];
                $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops.php",array("action"=>"getAddress","id"=>$city_id));
                preg_match_all('#get_navinfo\((\d+)\)#sui', $text, $shops);
                $shops = $shops[1];
                $shops[] = $city_id;


                foreach($shops as $shop_id)
                {
                    $shop = $this->parse_shop($shop_id, $city_name);
                    if($shop)
                        $base[] = $shop;
                }
            }
        }
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
        $base = array();

        $url = $this->shopBaseUrl."news.php";
        $text = $this->httpClient->getUrlText($url);
        
        preg_match_all('#<td class="bigdate">(\d+)</td>\s*<td class="date">/(\d+)</td>.+?<p class="bigdate">(.+?)</p>.+?<a href="(http\://www.francesco.ru/news.php\?id=(\d+))"#sui', $text, $news, PREG_SET_ORDER);


        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[5];
            $news_item->urlShort = $url;
            $news_item->urlFull = $news_value[4];
            $news_item->contentShort = $news_value[3];
            $news_item->header = $this->txt($news_value[3]);
            $news_item->date = $this->date_to_str($news_value[1]." ".$news_value[2]);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<p class="bigdate">(.+?)</td>#sui', $text, $content);
            $news_item->contentFull = $content[1];
            
            $base[] = $news_item;
        }


        $news = null;
        
        $url = $this->shopBaseUrl."news.php?action=all_actions";
        $text = $this->httpClient->getUrlText($url);
        
        preg_match_all('#<td class="bigdate">(\d+)</td>\s*<td class="date">/(\d+)</td>.+?<p class="bigdate">(.+?)</p>.+?<a href="(http\://www.francesco.ru/news.php\?id=(\d+))"#sui', $text, $news, PREG_SET_ORDER);


        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[5];
            $news_item->urlShort = $url;
            $news_item->urlFull = $news_value[4];
            $news_item->contentShort = $news_value[3];
            $news_item->header = $this->txt($news_value[3]);
            $news_item->date = $this->date_to_str($news_value[1]." ".$news_value[2]);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<p class="bigdate">(.+?)</td>#sui', $text, $content);
            $news_item->contentFull = $content[1];
            
            $base[] = $news_item;
        }
        

        return $this->saveNewsResult($base);
	}
}
