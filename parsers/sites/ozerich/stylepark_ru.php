<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_stylepark_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.s-t-p.ru/";

    public function __construct($savePath)
    {
        parent::__construct($savePath);
        $this->httpClient->setRequestsPause (1);
        $this->httpClient->setIgnoreBadCodes();

    }

    public function loadItems()
    {
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<li.*?><a href="/(set_gender/(\d+)/)".+?>(.+?)</a></li>#sui', $text, $collections, PREG_SET_ORDER);
        $this->httpClient->getUrlText("http://www.s-t-p.ru/bitrix/ajax/CatalogConf.php?action=setonpage&value=6000", null, false);

        foreach ($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->url = $this->shopBaseUrl . $collection_value[1];
            $collection_item->id = $collection_value[2];
            $collection_item->name = $collection_value[3];

            $text = $this->httpClient->getUrlText($collection_item->url);
            preg_match('#<div class="list_menu">(.+?)</div>#sui', $text, $text);
            preg_match_all('#<li class="">\s*<a href="/(.+?)">(.+?)<.+?<ul class="root-item">(.+?)</ul>#sui', $text[1], $categories, PREG_SET_ORDER);

            foreach ($categories as $category)
            {
                $category_name = $category[2];

                $text = $this->httpClient->getUrlText($this->shopBaseUrl . $category[1]);
                preg_match("#<div class='list_menu'>(.+)#sui", $text, $text);
                preg_match('#<li class="active">.+?<ul class="root-item">(.+?)</ul>#sui', $text[1], $text);
                preg_match_all('#<a href="/(.+?)">(.+?)<#sui', $text[1], $sub_categories, PREG_SET_ORDER);
                foreach ($sub_categories as $sub_category)
                {
                    $categ = array($category_name, $sub_category[2]);
                    $url = $sub_category[1];
                    while ($url != "")
                    {
                        $base_text = $this->httpClient->getUrlText($this->shopBaseUrl . $url);
                        preg_match('#<a class="page_next" href="/(.+?)"#sui', $base_text, $next);
                        $url = ($next) ? $this->txt($next[1]) : "";
                        if ($url) continue;
                        preg_match_all('#<td class="element">(.+?)</td>#sui', $base_text, $texts);
                        foreach ($texts[1] as $text)
                        {
                            preg_match('#<div class="name">\s*<a href="/(.+?)/" title="">(.+?)</a>.+?<div class="price">(.+?)\.#sui', $text, $info);
                            $item = new ParserItem();

                            $item->id = mb_substr($info[1], mb_strrpos($info[1], "-") + 1);
                            $item->url = $this->shopBaseUrl . $info[1] . "/";
                            $item->name = $this->txt($info[2]);
                            $item->categ = $categ;
                            $item->price = str_replace(" ", "", $this->txt($info[3]));

                            $text = $this->httpClient->getUrlText($item->url);

                            preg_match('#<span class="articul">Артикул:(.+?)</span>#sui', $text, $articul);
                            $item->articul = $this->txt($articul[1]);

                            preg_match('#<p> Бренд:(.+?)</p>#sui', $text, $brand);
                            if ($brand) $item->brand = $this->txt($brand[1]);

                            preg_match('#<p>\s*Состав:(.+?)</p>#sui', $text, $structure);
                            if ($structure) $item->structure = $this->txt($structure[1]);

                            preg_match_all('#<div class="chooseSize">\s*<a.+?>(.+?)</a>#sui', $text, $sizes);
                            if ($sizes)
                                foreach ($sizes[1] as $size)
                                    $item->sizes[] = $this->txt($size);

                            preg_match('#select id="Id_Cvet"(.+?)</select>#sui', $text, $colors);


                            if ($colors) {
                                preg_match_all('#<option value=".+?">(.+?)</option>#sui', $colors[1], $colors);
                                for ($i = 1; $i < count($colors[1]); $i++)
                                    $item->colors[] = $this->txt(mb_substr($colors[1][$i], 0, mb_strpos($colors[1][$i], "(")));
                            }

                            preg_match('#<div class="element_mini_text">(.+?)</div>\s*</div>#sui', $text, $descr);
                            $item->descr = $this->txt($descr[1]);

                            preg_match('#<div class="zoom-desc">(.+?)</div>#sui', $text, $image_text);
                            if ($image_text) {
                                preg_match_all('#<a href="/(.+?)"#sui', $image_text[1], $images);
                                foreach ($images[1] as $image)
                                    if (mb_strpos($image, "noimage") === false)
                                        $item->images[] = $this->loadImage($this->shopBaseUrl . $image);
                            }
                            else
                            {
                                preg_match('#<div class="zoom-small-image" style="position:relative; z-index:1;">\s*<a href="/(.+?)"#sui',
                                           $text, $image_url);
                                if ($image_url)
                                    $item->images[] = $this->loadImage($this->shopBaseUrl . $image_url[1]);
                            }

                            $collection_item->items[] = $item;
                        }
                    }


                }
            }


            $base[] = $collection_item;
        }


        return $this->saveItemsResult($base);
    }

    public function loadPhysicalPoints ()
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match('#<table cellspacing="0" cellpadding="0" border="0">(.+?)<td width="678" valign="top" colspan="3">(.+?)</table>#sui', $text, $text_);

        preg_match_all('#<td width="\d+" valign="top">(.+?)<br />(.+?)(?:<strong>|<b>)+(.+?)(?:</b>|</strong>)+\s*(</p>|<br />)#sui', $text_[1], $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            if($this->txt($shop_value[1]) == "")continue;

            $shop->city = "Москва";
            $shop->address = $this->address($shop_value[2].$shop_value[1]);

            $shop->address = str_replace(chr(194).chr(160), '', $shop->address);

            $shop->phone = $this->txt($shop_value[3]);
            
            $base[] = $shop;
        }

        preg_match_all('#<td width="\d+" valign="top">(.+?)</td>#sui', $text_[2], $tds);
        $city = '';
        foreach($tds[1] as $text)
        {
            preg_match_all('#<p>(.+?)</p>#sui', $text, $texts);
            foreach($texts[1] as $text)
            {
                if($this->txt($text) == "")continue;
                if(mb_strpos($text, "<b>") !== false)
                {
                    preg_match('#<b>(.+?)</b>(.+)#sui', $text, $info);
                    if($this->txt($info[1]) != "")
                        $city = str_replace('П Е Н З А','ПЕНЗА',$this->txt($info[1]));
                }
                else
                    $info[2] = $text;
                $shop = new ParserPhysical();



                $shop->city = $city;
                $shop->address = str_replace('. ,',',',$info[2]);

                $shop->address = str_replace(chr(194).chr(160), '', $shop->address);
                preg_match('#<strong>(.+?)</strong>#sui', $shop->address, $phone);
                if(!$phone)preg_match('#<br />\s*<b>(.+?)</b>#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = str_replace($phone[0], '', $shop->address);
                }

                $shop->address = $this->address($shop->address);
                $last_char = mb_substr($shop->address, mb_strlen($shop->address) - 1, 1);
                if($last_char == '.')$shop->address = mb_substr($shop->address, 0, -1);

                $pos = mb_strpos($shop->address, ",");
                if(mb_strpos($shop->address, '" ') !== false)$pos = min($pos, mb_strpos($shop->address, '" '));
                $name = mb_substr($shop->address, 0, $pos);
                $shop->address = trim(mb_substr($shop->address, $pos + 1)).", ".$name;
                
                $base[] = $shop;
            }
        }

        

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
        return null;
	}
}
