<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_donatto_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.donatto.ru/";
	
	public function loadItems () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."assortment/");
        preg_match_all('#class="menu_item_3(?:_selected)*"><a href="/(assortment/(.+?)/)">(.+?)</a></div>#sui', $text, $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->id = $collection_value[2];
            $collection_item->url = $this->shopBaseUrl.$collection_value[1];
            $collection_item->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($collection_item->url);

            preg_match_all('#\#cloth_(\d+)#sui', $text, $ids);$ids = $ids[1];
            preg_match_all('#<div class="details">(.+?)<div class="cloth_image">\s*<img src="/(.+?)".+?</div>\s*</div>#sui', $text, $items, PREG_SET_ORDER);
            $i = 0;
            foreach($items as $item_value)
            {
                $item = new ParserItem();

                $item->images[] = $this->loadImage($this->shopBaseUrl.$item_value[2]);
                $item->id = $ids[$i++];

                $item->url = $collection_item->url;

                $text = $item_value[1];
                preg_match('#<div class="detail"><div class="title">Артикул</div><div class="detail_desc">(.+?)</div></div>#sui', $text, $articul);
                if($articul)$item->articul = $this->txt($articul[1]);

                preg_match('#<div class="detail"><div class="title">Описание</div><div class="detail_desc">(.+?)</div></div>#sui', $text, $descr);
                if($descr)$item->descr = $this->txt($descr[1]);

                preg_match('#<div class="detail"><div class="title">Состав</div><div class="detail_desc">(.+?)</div></div>#sui', $text, $structure);
                if($structure)$item->structure = $this->txt($structure[1]);

                $collection_item->items[] = $item;
            }
            
    
            $base[] = $collection_item;
        }

        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $this->add_address_prefix("ЦУМ");

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#<div style="float:left;display:none"><a href="/(.+?)">(.+?)</a></div>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $city[2];
            $text = $this->httpClient->getUrlText($this->urlencode_partial($this->shopBaseUrl.$city[1]));

            preg_match_all('#<div class="shop_address">(.+?)</div>#sui', $text, $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                preg_match('#<span>(.+?)</span>#sui', $text, $phone);
                if($phone)
                {
                    $shop->phone = $this->txt($phone[1]);
                    $text = str_replace($phone[0], '', $text);
                }
                
                $shop->city = $city_name;
                $shop->address = $this->txt($text);

                $shop->address = str_replace('" Аврора"','"Аврора"', $shop->address);
                $shop->address = str_replace('" ', '",',$shop->address);

                $shop->address = $this->fix_address($shop->address);

                $shop->address = str_replace("II очередь, ",'',$shop->address);
    
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
        
        preg_match_all('#<div class="news_item">.+?<span class="news_date">(.+?)</span>(.+?)<span class="news_title"><a href="/(.+?)">(.+?)</a></span>.+?<span class="news_anons"><a href=".+?">(.+?)</a></span>#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $news_value[1];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[3];

            $id = mb_substr($news_item->urlFull, 0, mb_strrpos($news_item->urlFull, "/"));
            $news_item->id = mb_substr($id, mb_strrpos($id, "/") + 1);

            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<span class="news_article">(.+?)</span>#sui', $text, $content);
            $news_item->contentFull = $content[1];
            
    
            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
