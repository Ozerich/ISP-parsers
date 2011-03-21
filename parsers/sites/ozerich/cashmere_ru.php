<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_cashmere_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.cashmere.ru/";
	
	public function loadItems () 
	{
		$base = array();

        $this->shopBaseUrl = "http://shop.cashmere.ru/";
        $text = $this->httpClient->getUrlText($this->shopBaseUrl);

        preg_match_all('#<span>\s*<a href="/(Catalog/Index.aspx\?cid=(\d+))">(.+?)</a>\s*</span>\s*<ul class="sub_menu hidden">(.+?)</ul>#sui', $text, $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[2];
            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->name = $this->txt($collection_value[3]);

            preg_match_all('#<a href="/(.+?)">(.+?)</a>#sui', $collection_value[4], $categories, PREG_SET_ORDER);
            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $url = $this->shopBaseUrl.$category[1];
                $text = $this->httpClient->getUrlText($url);

                
                $params = array("__EVENTTARGET"=>'ctl00$ctl00$ContentPlaceHolder1$CPH2$lbShowAll',
                                "ctl00_ctl00_UserName"=>'',
                                "__EVENTARGUMENT"=>'',
                                "ctl00_ctl00_Password"=>'',
                                "auth.logon"=>'do'
                );

                $param_names = array('__VIEWSTATE','__EVENTVALIDATION');
                foreach($param_names as $name)
                {
                    preg_match('#id="'.$name.'" value="(.*?)"#sui', $text, $param);
                    $params[$name] = $param[1];
                }

                preg_match_all('#<div class="description">\s*<a href="/(.+?)">(.+?)</a>\s*</div>\s*<div class="price" >(.+?)</div>#sui', $text, $items, PREG_SET_ORDER);
                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->url = $this->shopBaseUrl.$item_value[1];

                    //$item->url = "http://shop.cashmere.ru/Catalog/Style.aspx?cid=1&tid=634&bid=&sid=12429";
                    
                    $item->name = $item_value[2];
                    $item->categ = $category_name;
                    $item->id = mb_substr($item->url, mb_strrpos($item->url, "=") + 1);

                    $price = $item_value[3];
            
                    if(mb_strpos($price, "price_old") !== false)
                    {
                        preg_match('#<s>(.+?)р.</s> <div class="price_old".+?>(.+?)р.#sui', $price, $price);
                        $item->price = str_replace(chr(194).chr(160), "", $price[2]);
                        $item->discount = $this->discount($price[1], $price[2]);
                    }
                    else
                    {
                        preg_match('#(.+?)р.#sui', $price, $price);
                        $item->price = str_replace(chr(194).chr(160), "", $price[1]);
                    }

                    $item->price = $this->txt(str_replace(" ", "", $item->price));
                    

                    $text = $this->httpClient->getUrlText($item->url);

                    preg_match("#<p><span>Бренд:</span>(.+?)</p>#sui", $text, $brand);
                    preg_match("#<p><span>Материал:</span>(.+?)</p>#sui", $text, $material);
                    preg_match("#<p><span>Производство:</span>(.+?)</p>#sui", $text, $made_in);
                    preg_match("#<p><span>Артикул:</span>(.+?)</p>#sui", $text, $articul);

                    if($brand)$item->brand = $this->txt($brand[1]);
                    if($material)$item->material = $this->txt($material[1]);
                    if($made_in)$item->made_in = $this->txt($made_in[1]);
                    if($articul)$item->articul = $this->txt($articul[1]);

                    preg_match('#<select name="colors" class="colors">(.+?)</select>#sui', $text, $color_text);
                    if($color_text)
                    {
                        preg_match_all('#<option.+?>(.+?)</option>#sui', $color_text[1], $colors);
                        foreach($colors[1] as $color)
                            $item->colors[] = $this->txt($color);
                    }

                    preg_match('#<table class="size".+?>(.+?)</table>#sui', $text, $size_text);
                    if($size_text)
                    {
                        preg_match_all('#div class="clickable".+?>(.+?)</div>#sui', $size_text[1], $sizes);
                        foreach($sizes[1] as $size)
                            $item->sizes[] = $this->txt($size);
                    }

                    preg_match('#<table id="ctl00_ctl00_ContentPlaceHolder1_CPH2_rPictures_groupPlaceholderContainer" class="photo-preview">(.+?)</table>#sui', $text, $image_text);
                    $images_hash = array();
                    preg_match_all("#<a href='/(.+?)'>\s*<img src=\"/(.+?)\"#sui", $image_text[1], $images, PREG_SET_ORDER);
                    foreach($images as $image)
                    {
                        $url1 = $this->shopBaseUrl.$image[1];
                        $url2 = $this->shopBaseUrl.$image[2];
                        if(in_array($url1, $images_hash))
                            $image = $this->loadImage($url2);
                        else
                        {
                            $image = $this->loadImage($url1);
                            $images_hash[] = $url1;
                        }

                        if($image)
                            $item->images[] = $image;
                    }



                    $collection->items[] = $item;
                }


                
            }
        
            $base[] = $collection;
        }
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $this->add_address_prefix("Крокус Сити Молл");
        $this->add_address_prefix("Lotte Plaza");
        $this->add_address_prefix('"Смоленский пассаж"');
        $this->add_address_prefix('"Мегацентр Италия"');

        $text = $this->httpClient->getUrlText("http://cashmere.ru/shops.shtml");
        preg_match_all("#typesFullArr\['cities'\]\[\d+]\['brands'\]\[\d+\]\['cities'\]\[\d+\]\['city'\]\['sName'\] = '(.+?)';(.+?)typesFullArr\['cities'\]\[0\]\['brands'\]\[0\]\['cities'\]\[\d+\] = new Object\(\);#sui", $text, $cities, PREG_SET_ORDER);

        foreach($cities as $city)
        {
            $city_name = $city[1];
            $text = $city[2];

            preg_match_all("#typesFullArr\['cities'\]\[\d+\]\['brands'\]\[\d+\]\['cities'\]\[\d+\]\['shops'\]\[\d+\]\['nId'\] = (\d+);#sui", $text, $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->id = $shop_value[1];
                $shop->city = $city_name;

                //$shop->id = 137;
                $text = $this->httpClient->getUrlText("http://cashmere.ru/shops/getshop.shtml?nId=".$shop->id."&lang=ru");

                preg_match('#shopPhone:"<p>(.+?)</p>"#sui', $text, $phone);
                if($phone)$shop->phone = $this->txt($phone[1]);

                preg_match('#shopContacts:"(.+?)",shopDesc#sui', $text, $info);
                $info = str_replace("<br />", "<br/>", $info[1]);
                $pos = mb_strrpos($info, "<br/>");
                if(mb_strrpos($info, "</p><p>") !== false && mb_strrpos($info, "</p><p>") > $pos)$pos = mb_strrpos($info, "</p><p>");

                if($pos === false)
                    $info = array("1"=>$info);
                else
                    $info = array("1"=>mb_substr($info,0,$pos),"2"=>mb_substr($info, $pos));
               
                $info[1] = str_replace('<br/>',', ', $info[1]);
                if(isset($info[1]))$shop->address = $this->address($info[1]);
                if(isset($info[2]))$shop->timetable = $this->txt($info[2]);


                $shop->address = $this->fix_address($shop->address);

                    
                $base[] = $shop;
            }
        }


		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = "http://cashmere.ru/news.shtml";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<a class="galleryContentHeader" href="/(.+?)">(.+?)</a><a class="galleryContentText" href=".+?">(.+?)</a>#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = "http://cashmere.ru/".$news_value[1];
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[3];
            $news_item->id = $this->getFileName($news_item->urlFull);
            $news_item->date = mb_substr($news_item->id, 0, 2).".".mb_substr($news_item->id, 2, 2).".".mb_substr($news_item->id, 4);

            if(mb_strrpos($news_item->date, '.') == mb_strlen($news_item->date) - 3)
                $news_item->date = mb_substr($news_item->date, 0, mb_strrpos($news_item->date, '.')).".20".mb_substr($news_item->date, mb_strrpos($news_item->date, '.') + 1);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#contentArr\[0\]="(.+?)";#sui', $text, $content);
            if($content)$news_item->contentFull = str_replace('\"','"',$content[1]);
            
            $base[] = $news_item;
        }
		
		return $this->saveNewsResult($base);
	}
}
