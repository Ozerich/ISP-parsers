<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_moda_comfort_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.moda-comfort.ru/";
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<select name="season_id">(.+?)</select>#sui', $text, $text);
        preg_match_all('#<option value="(.+?)">(.+?)</option>#sui', $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
            if($collection_value[1] != "0")
            {
                $collection_id = $collection_value[1];
                $collection_name = $collection_value[2];
                break;
            }
        $collections = array(array("url"=>$this->shopBaseUrl."goods.php?pol_id=1&season_id=$collection_id","name"=>$collection_name." Мужская коллекция","id"=>$collection_id."_1"),
                             array("url"=>$this->shopBaseUrl."goods.php?pol_id=2&season_id=$collection_id","name"=>$collection_name." Женская коллекция","id"=>$collection_id."_2"));
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->url = $collection_value['url'];
            $collection->id = $collection_value['id'];
            $collection->name = $collection_value['name'];

            $page = 1;
            while($page < 100)
            {
                $text = $this->httpClient->getUrlText($collection->url."&page=".$page);

                preg_match('#<span>(\d+)</span>#sui', $text, $cur_page);
                $cur_page = $cur_page[1];
                if($cur_page != $page)break;

                preg_match_all('#<div><nobr><a href="/gdsinfo.php\?id=(\d+)" target=#sui', $text, $ids);

                foreach($ids[1] as $id)
                {
                    $item = new ParserItem();

                    $item->id = $id;
                    $item->url = $this->shopBaseUrl."gdsinfo.php?id=".$item->id;

                    $text = $this->httpClient->getUrlText($item->url);

                    preg_match('#<strong>Артикул:(.+?)</strong>#sui', $text, $articul);
                    $item->articul = $this->txt($articul[1]);

                    preg_match('#Бренд:(.+?)</strong>#sui', $text, $brand);
                    $item->brand = $this->txt($brand[1]);

                    preg_match('#Тип:(.+?)</strong>#sui', $text, $categ);
                    $item->categ = $this->txt($categ[1]);

                    preg_match('#<td colspan="2" class="illust"><img src="/(.+?)"#sui', $text, $image);
                    $image = $this->loadImage($this->shopBaseUrl.$image[1]);
                    preg_match('#id=(\d+)#sui', $image->url, $image_id);
                    $image->id = $image_id[1];
                    $item->images[] = $image;

                    $collection->items[] = $item;
                }

                $page++;
            }
            $base[] = $collection;
        }
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."magaz.php");

        preg_match('#<div  id="msk_block".+?>(.+?)<div class="hr_1"></div>\s*</div>.+?<div id="spb_block".+?>(.+?)<div class="hr_1"></div>\s*</div>#sui', $text, $text);
        $cities = array(array("city"=>"Москва","text"=>$text[1]), array("city"=>"Санкт-Петербург","text"=>$text[2]));

        foreach($cities as $city)
        {
            $city_name = $city['city'];
            $text = $city['text'];

            preg_match_all('#<table cellpadding="0" cellspacing="0" class="shop_adress">.+?a href="/(magazinfo.php\?id=(\d+))".+?<p>(.+?)</p>#sui', $text, $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->id = $shop_value[2];
                $shop->address = $this->address($shop_value[3]);
                $shop->city = $city_name;

                $shop->address = trim(str_replace($city_name.",",'',$shop->address));

                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_value[1]);

                preg_match('#<p>тел.:(.+?)</p>#sui', $text, $phone);
                if($phone)$shop->phone = $this->txt($phone[1]);

                preg_match('#<p>Часы работы:(.+?)</p>#sui', $text, $timetable);
                if($timetable)$shop->timetable = $this->txt($timetable[1]);
                
                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<div class="mc_date">(.+?)</div>\s*<div class="mc_bck">\s*<a href="/(news.php\?id=(\d+))".+?>(.+?)</a>\s*</div>#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $news_value[1];
            $news_item->urlShort = $this->shopBaseUrl;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
            $news_item->id = $news_value[3];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#</strong>\s*<p class="content">(.+?)<br clear="all"><br><br>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }
        
		return $this->saveNewsResult($base);
	}
}
