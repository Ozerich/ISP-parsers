<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_deffinesse_net extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.deffinesse.net/";
	
	public function loadItems () 
	{
        $this->shopBaseUrl =  "http://www.boutique-online.ru/";
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<nobr><a href="/(catalog/(.+?)/)" ><font color="\#000000">(.+?)</font></a></nobr>#sui',
            $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->id = $collection_value[2];
            $collection->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($collection->url);
            preg_match('#<div class="menuCat">(.+?)</div></div></div>#sui', $text, $text);
            preg_match_all('#<div class="wBlock".+?><a href="/(.+?)">(.+?)</a></div>#sui', $text[1], $categories,
                           PREG_SET_ORDER);
            if(!$categories)
                $categories = array(array("1"=>$collection_value[1], "2"=>""));
            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($this->urlencode_partial($this->shopBaseUrl.$category[1]."?page=all&sortBy="));

                preg_match_all('#><div style="display: table-cell; vertical-align:bottom;"><a href="/(thing/(.+?)/)" class="type2">(.+?)</a><br>.+?</b><br><br></div><div class="gPrice_new">(.+?)руб.</div></td>#sui',
                    $text, $items, PREG_SET_ORDER);
                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->id = $item_value[2];
                    $item->url = $this->shopBaseUrl.$item_value[1];
                    $item->name = $this->txt($item_value[3]);
                    $item->price = $this->txt($item_value[4]);

                    $text = $this->httpClient->getUrlText($item->url);

                    preg_match('#<div class="mBlock_g">Код товара:<div class="mInfoBlock">(.+?)</div>#sui', $text, $articul);
                    $item->articul = $this->txt($articul[1]);

                    preg_match('#<img src="/img/basket_white.gif".+?<td width="80%" valign="middle" style="vertical-align:middle; padding: 10px">(.+?)</td>#sui',
                        $text, $descr);
                    $item->descr = $this->txt($descr[1]);

                    preg_match('#Состав:(.+?)<br>#sui', $text, $structure);
                    $item->structure = $this->txt(str_replace('Данные не доступны.','',$structure[1]));

                     preg_match('#<img id="largeImg".+?src="(.+?)"#sui', $text, $image);
                    $image = $this->loadImage($image[1]);
					if($image)
						$item->images[] = $image;

                   preg_match('#<ul id="mycarousel" class="jcarousel-skin-tango">(.+?)</ul>#sui', $text, $images_text);
                    preg_match_all('#<a href="(.+?)"#sui', $images_text[1], $images);
                    foreach($images[1] as $image)
					{
                        if(mb_strpos($image, "resize_jpg") !== false)
                           $image = $this->loadImage(mb_substr($image, mb_strpos($image, "image=") + 6));
                        else
                           $image = $this->loadImage($image);
						if($image)
							$item->images[] = $image;
					}

                    preg_match('#<span id="mthingSpan" class="jcarousel-skin-tango">(.+?)</span>#sui', $text, $images_text);
                    if($images_text)
                    {
                        preg_match_all('#<a href="(.+?)"#sui', $images_text[1], $images);
                        foreach($images[1] as $image)
                        {
                            if(mb_strpos($image, "resize_jpg") !== false)
                                $image = $this->loadImage(mb_substr($image, mb_strpos($image, "image=") + 6));
                            else
                                $image = $this->loadImage($image);
                            if($image)
                                $item->images[] = $image;
                        }
                    }
                    $collection->items[] = $item;
                }
            }

            $base[] = $collection;

        }

        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $this->shopBaseUrl =  "http://www.deffinesse.net/";
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#<p><span style="color: rgb\(255, 255, 255\);">(.+?)</p>#sui', $text, $texts, PREG_SET_ORDER);
        for($i = 0; $i <= 1; $i++)
        {
            $text = $texts[1][$i];
            preg_match_all('#<br />(.+?)(?=<br />)#sui', $text, $shops_text);
            foreach($shops_text[1] as $text)
            {
                $text = str_replace(';','',$this->txt($text));
                $shop = new ParserPhysical();

                $shop->city = "Москва";
                $shop->address = $this->address($text);
                $shop->address = $this->fix_address($shop->address);

                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
        $this->shopBaseUrl =  "http://www.deffinesse.net/";

        $base = array();

        $url = $this->shopBaseUrl."news";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td style="color:\#fff;padding-bottom:10px;text-align:left;">(.+?)</td>#sui', $text, $texts);
        foreach($texts[1] as $text)
        {

            preg_match('#(.+?)&nbsp;<strong>(.+?)</strong><br/>(.+)#sui', $text, $info);

            $news = new ParserNews();

            $news->date = $info[1];
            $news->header = $this->txt($info[2]);
            $news->urlShort = $url;
            $news->contentShort = $info[3];

            $base[] = $news;
        }
            
		return $this->saveNewsResult($base);
	}
}
