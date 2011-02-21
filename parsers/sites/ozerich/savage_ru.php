<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_savage_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = "http://www.savage.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<div id="initem" onmouseover="Collection\(\);".+?>(.+?)</div>#sui', $text, $text);
        preg_match_all('#<a href="/(.+?)".+?>(.+?)</a>#sui', $text[1], $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->name = $this->txt($collection_value[2]);
            $collection->id = mb_substr($collection_value[1], 0, mb_strpos($collection_value[1],'/'));

            $text = $this->httpClient->getUrlText($collection->url);

            preg_match_all('#<td id="link_\d+"><span onmouseover="submenu.+?>(.+?)</span>(.+?)</td>#sui', $text, $categories,
                PREG_SET_ORDER);
            foreach($categories as $category_value)
            {
                $category_name = $this->txt($category_value[1]);
                preg_match_all("#<a href='/(.+?)'>(.+?)</a>#sui", $category_value[2], $sub_categories, PREG_SET_ORDER);
                foreach($sub_categories as $sub_category)
                {
                    $categ = array($category_name, $this->txt($sub_category[2]));
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl.$sub_category[1]);

                    preg_match('#<noindex>.+?<a href="/(.+?)" rel="nofollow">Все</a>#sui', $text, $url);
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl.$url[1]);

                    preg_match_all('#<div id="catItem">(.+?)добавить в гардероб#sui', $text, $items);
                    foreach($items[1] as $text)
                    {
                        $item = new ParserItem();
                        preg_match("#onclick=\"window.open\('/(.+?)/'#sui", $text, $url);

                        preg_match('#Артикул: : (.+?) <br />#sui', $text, $articul);
                        if($articul)
                            $item->articul = $this->txt($articul[1]);

                        preg_match('#<b>Цена:(.+?)руб#sui', $text, $price);
                        if($price)
                            $item->price = str_replace(" ", "", $price[1]);

                        preg_match('#<small>\s*Размеры:(.+?)</small>#sui', $text, $size_text);
                        if($size_text)
                        {
                            $sizes = explode(',', $this->txt($size_text[1]));
                            foreach($sizes as $size)
                                $item->sizes[] = $this->txt($size);
                        }


                        $item->url = $this->shopBaseUrl.$url[1]."/";
                        $item->id = mb_substr($url[1], mb_strrpos($url[1], "/") + 1);
                        $item->categ = $categ;

                        $text = $this->httpClient->getUrlText($item->url);

                        preg_match('#<b>(.+?)</b>#sui', $text, $name);
                        if($name)$item->name = $this->txt($name[1]);

                        preg_match('#<div valign="top" align="right" class="svg_cmp_inner_catalog_detail_image">\s*<img src="/(.+?)"#sui', 
                            $text, $image);
                        if($image)$item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

                        preg_match('#<div class="svg_cmp_inner_catalog_detail_info svg_cmn_png_for_ie">(.+?)<div class="svg_cmp_inner_catalog_detail_clr">#sui',
                            $text, $descr);
                        if($descr)$item->descr = $this->txt($descr[1]);

                        if(mb_strpos($item->descr,"Состав одежды") !== false)
                        {
                            $item->structure = mb_substr($item->descr, mb_strpos($item->descr, "Состав одежды:") + mb_strlen("Состав одежды:"));
                            //$item->descr = mb_substr($item->descr, 0, mb_strpos($item->descr, "Состав одежды"));
                        }
                        else if(mb_strpos($item->descr,"Ткань") !== false)
                        {
                            $item->structure = mb_substr($item->descr, mb_strpos($item->descr, "Ткань"));
                           // $item->descr = mb_substr($item->descr, 0, mb_strpos($item->descr, "Ткань"));
                        }

                        $item->descr = str_replace('Артикул: '.$item->articul, '',$item->descr);

                        $collection->items[] = $item;

                    }
                }
            }

            $base[] = $collection;
        }

		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/?country_id=1137");
        preg_match('#<select name="city_id".+?</option>(.+?)</select>#sui', $text, $text);
        preg_match_all('#<option value="(.+?)".*?>(.+?)</option>#sui', $text[1], $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $this->txt($city[2]);
            $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/?country_id=1137&city_id=".$city[1]);

            preg_match_all('#<td  class="svg_cmp_shops_lst_info">\s*<a href="/(.+?)">(.+?)</a>.+?<td width="200" class="svg_cmp_shops_lst_cont">(.+?)</td>#sui',
                $text, $shops, PREG_SET_ORDER);

            foreach($shops as $shop_value)
            {
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_value[1]);

                $shop = new ParserPhysical();

                $shop->city = $city_name;
                $shop->b_stock = (mb_strpos($shop_value[2], "ДИСКОНТ") !== false) ? 1 : 0;

                preg_match('#<td class="svg_cmp_shop_adress_td">\s*Адрес:(.+?)</tr>#sui', $text, $address);
                if($address)
                    $shop->address = $this->txt($address[1]);

                preg_match('#<td class="svg_cmp_shop_adress_td">\s*Телефон:(.+?)</tr>#sui', $text, $phone);
                if($phone)
                    $shop->phone = $this->txt($phone[1]);

                preg_match('#<td class="svg_cmp_shop_adress_td">\s*Режим работы:(.+?)</tr>#sui', $text, $timetable);
                if($timetable)
                    $shop->timetable = $this->txt($timetable[1]);

                if($shop->address == "")
                    $shop->address = $this->txt($shop_value[2]);

                $base[] = $shop;
            }

        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."press/news/index.php";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<br><br>(.+?)<br>\s*<a href="/(press/news/(\d+)/)">(.+?)</a>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->urlShort = $url;
            $news_item->id = $news_value[3];
            $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<span class="news-date-time">.+?</span>(.+?)<div style="clear:both"></div>#sui', $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }


        
        return $this->saveNewsResult($base);
	}
}
