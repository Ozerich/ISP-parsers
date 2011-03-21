<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_loccitane_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.loccitane.ru/";

    private function parse_item($url)
    {
        $item = new ParserItem();

        $this->httpClient->setCookieJar(false);
        $item->url = $url;

        $text = $this->httpClient->getUrlText($this->urlencode_partial($item->url));

        preg_match('#<h1>(.+?)</h1>#sui', $text, $name);
        $item->name = $this->txt($name[1]);

        preg_match('#<div style="margin:10px 0px;">\s*Артикул\&nbsp;:\&nbsp;(.+?)</div>#sui', $text, $articul);
        $item->articul = $this->txt($articul[1]);

        preg_match('#<div id="maskimage">(.+?)<div id="mask">\s*</div>#sui', $text, $image_text);
        preg_match('#<img src="(http://img.loccitane.com/.+?)"#sui', $image_text[1], $image);
        if(!$image)preg_match('#<div id="ctl00_ContentPlaceHolder1_ctl00_productImg">\s*<img src="(.+?)"#sui', $text, $image);
        $image = $this->loadImage($image[1]);
        if($image)$item->images[] = $image;

        preg_match('#,(\d+)\.htm#sui', $url, $id);
        $item->id = $id[1];

        $old_price = $new_price = "";

        preg_match('#class="standardPrice">(.+?),.+?</span>#sui', $text, $price);
        if(!$price)preg_match('#<p>Стоимость:(.+?)руб.</p>#sui', $text, $price);
        if($price)$new_price = str_replace(array(' ',' '),array('',''), $this->txt($price[1]));

        preg_match('#class="discountedPrice">(.+?),.+?</span>#sui', $text, $price);
        if($price)$old_price = str_replace(' ','', $this->txt($price[1]));

        if($old_price)
        {
            $item->discount = $this->discount($old_price, $new_price);
            $item->price = $old_price;
        }
        else
            $item->price = $new_price;


        preg_match('#<div id="ctl00\_ContentPlaceHolder1\_ctl00\_RatingStatic">(.+?)</div>\s*</div>\s*</div>#sui',
                $text, $descr);
        $item->descr = $this->txt($descr[1]);

        preg_match("#<div id='breadcrumb'>(.+?)</div>#sui", $text, $categ_text);
        preg_match_all('#<a href=".+?".*?>(.+?)</a>#sui', $categ_text[1], $items);
        for($i = 2; $i < count($items[1]) - 1; $i++)
            $item->categ[] = $this->txt($items[1][$i]);
        $item->name = $this->txt($items[1][count($items[1]) - 1]);


        return $item;
    }

	public function loadItems () 
	{
	    $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<div id="main_menu" class="clear">\s*(.+?)Бестселлеры#sui', $text, $text);
        preg_match_all("#<li subid='megaddsubid(\d+)' class='megadditem'><a href=\"(.+?)\">(.+?)</a></li>#sui", $text[1],
            $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[1];
            $collection->url = (mb_strpos($collection_value[2],'locci') !== false)? $collection_value[2] : $this->shopBaseUrl.$collection_value[2];
            $collection->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($this->urlencode_partial($collection->url));

            $categories = array();

            if($collection->id == 383)
            {
                preg_match_all('#<li class=\'\'><a href="(.+?)"><span>(.+?)</span></a></li>#sui', $text, $categories_, PREG_SET_ORDER);

                foreach($categories_ as $category)
                {
                    if($this->txt($category[2]) == 'Подбор подарка')
                        break;
                    $category[1] = mb_substr($category[1], mb_strlen($this->shopBaseUrl));
                    $categories[] = $category;
                }
            }
            else
            {
                preg_match("#<div id='megaddsubid".$collection->id."' class=\"megaddsub megaddSubElem\"'>(.+?)</div>#sui", $text, $text);
                if($text)
                    preg_match_all('#<a href="/(.+?)">(.+?)</a>#sui', $text[1], $categories, PREG_SET_ORDER);
            }
            if(!$categories)
                $categories = array(array("1"=>mb_substr($collection->url,mb_strlen($this->shopBaseUrl)), "2"=>""));
            foreach($categories as $category)
            {

                $text = $this->httpClient->getUrlText($this->urlencode_partial($this->shopBaseUrl.$category[1]));
                $page_text = $text;
                $category_name = $this->txt($category[2]);

                preg_match("#<ul><li class=' selected'>.+?</li>(.*?)(?:</div>|</ul>)#sui", $page_text, $text);
                $subcategories = array();
                if($text)
                    preg_match_all('#<a href="(.+?)">(.+?)</a>#sui', $text[1], $subcategories, PREG_SET_ORDER);
                else
                    preg_match_all('#<li class=\' selected\'><a href="(.+?)"><span>(.+?)</span></a></li>#sui', $page_text, $subcategories, PREG_SET_ORDER);
                if(!$subcategories)
                    $subcategories = array(array("1"=>$this->shopBaseUrl.$category[1], "2"=>""));

                foreach($subcategories as $subcategory)
                {
                    $url = (mb_strpos($subcategory[1],$this->shopBaseUrl) !== false) ? $subcategory[1] : $this->shopBaseUrl.$subcategory[1];
                    $text = $this->httpClient->getUrlText($this->urlencode_partial($url));
                    preg_match_all('#<div class=\'\' equalize=\'producttitlelink\'>.+?href="(.+?)">(.+?)</a></div>#sui',
                        $text, $items, PREG_SET_ORDER);

                    foreach($items as $item_url)
                    {
                        $item = $this->parse_item($item_url[1]);
                        $collection->items[] = $item;
                    }
                }
            }

            $base[] = $collection;
        }

        return $this->saveItemsResult($base);
	}

	public function loadPhysicalPoints () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."site-map,9,2,395,7029.htm");
        preg_match('#<a class=\'bold\' href="http://www.loccitane.ru/our-shops,9,2,370,0.htm">Магазины</a>(.+?)Бутики#sui',
            $text, $text);
        preg_match_all('#<div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a class=\'\' href="(.+?)">(.+?)</a></div>#sui',
                       $text[1], $cities, PREG_SET_ORDER);
        foreach($cities as $ind=>$city)
        {
            if($ind == 0)continue;
            $url = mb_substr($city[1], 0, mb_strrpos($city[1], ',')).".htm";
            $text = $this->httpClient->getUrLText($url);

            $city_name = $this->txt($city[2]);

            if($city_name == 'Санкт-Петербург' || $city_name == 'Москва')
                preg_match_all('#<li><strong>(.+?)</li>#sui', $text, $shops);
            else
            {
                preg_match_all('#<span style="font-size: x-small;">(.+?)</li>#sui', $text, $shops);
                if(!$shops[1])preg_match_all('#<span style="font-size: 8.5pt;">(.+?)</li>#sui', $text, $shops);
                                if(!$shops[1])preg_match_all('#</div>\s*<strong>(.+?)(?:</div>|</li>)#sui', $text, $shops);
            }

            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                $shop->address = $text;
                $shop->city = $this->txt($city[2]);

                if(mb_strpos($shop->address,'</strong>') !== false)
                    $shop_item = mb_substr($shop->address, 0, mb_strpos($shop->address,'</strong>'));
                else $shop_item = "";
                $shop_item = $this->txt(str_replace('*','',$shop_item));
                if(mb_strpos($shop_item, 'АДРЕСА') !== false)$shop_item = '';

                $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '</strong>'));

                $info = explode('<br />', $shop->address);
                if(count($info) > 1)
                {
                    preg_match('#<strong>(.+?)</strong>#sui', $shop->address, $shop_prefix);

                    if($shop_prefix)
                    {
                        $shop->address = str_replace($shop_prefix[0],'',$shop->address);
                        $shop_prefix = $this->txt($shop_prefix[1]);
                    }
                    else
                        $shop_prefix = '';

                    $text = $this->txt($shop->address);
                    $shop->address = mb_substr($text, 0, mb_strpos($text, 'тел.'));

                    preg_match('#тел\.:*([\s|+|\(|\)|\d|\-]+)#sui', $text, $phone);
                    if($phone)$shop->phone = $this->txt($phone[1]);

                    preg_match('#(пн-.+?)(?:м\.|схема|$)#sui', $text, $timetable);
                    if($timetable)$shop->timetable = $this->txt($timetable[1]);
                }
                else
                    $shop->address = $this->txt($shop->address);
                if($shop->address == "")continue;

                preg_match('#тел\.:*([\s|+|\(|\)|\d|\-]+)#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $this->txt($phone[1]);
                    $shop->address = str_replace($phone[0], '', $shop->address);
                }

                if($shop->address[0] == '*')
                    $shop->address = trim(mb_substr($shop->address, 1));

                preg_match('#г\.(.+?),#sui',$shop->address, $city_name);
                if($city_name)
                {
                    $shop->city = $this->txt($city_name[1]);
                    $shop->address = str_replace($city_name[0],'',$shop->address);
                }

                if(mb_strpos($shop->address, 'обл.') !== false)
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, 'обл.') + 5);

                $this->add_address_prefix("L'OCCITANE");
                $shop->address = str_replace(array('(вход с Невского пр-та)*',"L'OCCITANE",'МО','пр-тВернадского'),
                                             array('',"L'OCCITANE,",'','пр-т Вернадского'), $shop->address);

                $shop->address = $this->fix_address($shop->address);

                if($shop_prefix)$shop->address .= ", ".$shop_prefix;
                if($shop_item)$shop->address .= ", ".$shop_item;
                $base[] = $shop;
            }
        }

        return $this->savePhysicalResult($base);
    }
	
	public function loadNews ()
	{
        return null; 
	}
}
