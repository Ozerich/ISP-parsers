<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_sweetmama_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.sweetmama.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }

    private function parse_item($url, $category_name)
    {
        $item = new ParserItem();

        $item->url = $url;
        $item->id = mb_substr($item->url, mb_strrpos($item->url, '/') + 1, -5);
        $text = $this->httpClient->getUrlText($item->url);

        preg_match('#<h2>(.+?)</h2>#sui', $text, $name);
        $item->name = $this->txt($name[1]);

        preg_match('#<p class="art">артикул: (.+?)</p>#sui', $text, $articul);
        if($articul)$item->articul = $this->txt($articul[1]);

        $new_price = $old_price = '';
        preg_match('#<span class="red"><strong>(.+?)р.</strong>#sui', $text, $price);
        if($price)$new_price = $this->txt($price[1]);
        preg_match('#<th><s>(.+?)р.</s>#sui', $text, $price);
        if($price)$old_price = $this->txt($price[1]);

        if($old_price != '')
        {
            $item->price = $old_price;
            $item->discount = $this->discount($old_price, $new_price);
        }
        else if($new_price != '')
            $item->price = $new_price;

        preg_match('#<ul id="slide">(.+?)</ul>#sui', $text, $image_text);
        if($image_text)
        {
            preg_match_all("#<img src='(.+?)'#sui", $image_text[1], $images);
            foreach($images[1] as $image)
                $item->images[] = $this->loadImage($this->shopBaseUrl.$image);
        }

        preg_match('#<td class="size">(.+?)</td>#sui', $text, $size_text);
        if($size_text)
        {
            preg_match_all('#<span>(.+?)</span>#sui', $size_text[1], $sizes);
            foreach($sizes[1] as $size)
                $item->sizes[] = $this->txt($size);
        }
        
        preg_match('#<td class="color">(.+?)</td>#sui', $text, $color_text);
        if($color_text)
        {
            preg_match_all("#alt='(.+?)'#sui", $color_text[1], $colors);
            foreach($colors[1] as $color)
                $item->colors[] = $this->txt($color);
        }

        if($item->sizes && $item->sizes[0] == 'универс.')
            $item->sizes = null;

        if($category_name != '')
            $item->categ = $category_name;

        preg_match('#<p class="art">.+?</p>(.+?)<table class="model">#sui', $text, $descr);
        if($descr)$item->descr = $this->txt($descr[1]);

        return $item;
    }

	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."fashion/shop/");
        preg_match('#<div id="menu">(.+?)</div>#sui', $text, $text);

        preg_match_all("#<h4>(<a href=/(.+?)/>)*(.+?)</h4><ul>(.*?)</ul>#sui", $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->name = $this->txt($collection_value[3]);
            if($collection_value[2] != '')
            {
                $collection->url = $this->shopBaseUrl.$collection_value[2]."/";
                $collection->id = mb_substr($collection_value[2], mb_strrpos($collection_value[2],'/') + 1);
            }
            preg_match_all("#<a href='/(.+?)/'.*?>(.+?)</a>#sui", $collection_value[4], $categories, PREG_SET_ORDER);
            if(!$categories)
                $categories = array(array('1'=>$collection_value[2],'2'=>''));
            if(!$collection->url)
            {
                $collection->url = $this->shopBaseUrl.mb_substr($categories[0][1], 0, mb_strrpos($categories[0][1],'/'));
                $collection->id = mb_substr($collection->url, mb_strrpos($collection->url, '/') + 1);
            }

            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]."/");
                preg_match_all("#<div class=\"lot\">\s*<a href='/(.+?)'>#sui", $text, $items);
                foreach($items[1] as $url)
                    $collection->items[] = $this->parse_item($this->shopBaseUrl.$url, $category_name);
            }

            $base[] = $collection;
        }
		return $this->saveItemsResult ($base);
	}

    private function parse_shop($url, $city)
    {
        $text = $this->httpClient->getUrlText($url);
        $text = str_replace('<p>М. Курская (кольцевая), 2 мин. пешком от метро.<br>','',$text);
        
        $shop = new ParserPhysical();

        $shop->city = $this->txt($city);

        preg_match('#Адрес\s*: (.+?)(?:<br />|</p>|</div>|<br>|\n)#sui', $text, $address);
        if(!$address)preg_match('#<div id="main">\s*(?:<p>)*(.+?)(?:<br />|\n|</p>)#sui', $text, $address);
        if(!$address)preg_match('#<div id="main">(.+?)</div>#sui', $text, $address);
        if($address)$shop->address = str_replace('Адрес магазина:','',$this->txt($address[1]));


        preg_match('#Часы работы:(.+?)</p>#sui', $text, $timetable);
        if(!$timetable)preg_match('#(?:Время работы магазина|Часы работы)(.+?)(?:</p>|\n)#sui', $text, $timetable);
        if($timetable)$shop->timetable = $this->txt($timetable[1]);

        preg_match('#Телефоны*:(.+?)(?:<br\s*/*>|</p>|\n)#sui', $text, $phone);
        if(!$phone)preg_match('#Тел\.\s*\:*(.+?)(?:<br\s*/*>|</p>|\n)#sui', $text, $phone);
        if(!$phone)preg_match('#Тел\.*\s*\:(.+?)(?:<br\s*/*>|</p>|\n)#sui', $text, $phone);
        if($phone)$shop->phone = $this->txt($phone[1]);


        if($shop->address == '' || mb_strpos($shop->address,'ВНИМАНИЕ')!==false || mb_strpos($shop->address, "Телефон") !== false
            || mb_strpos($shop->address, 'Адрес:') !== false)
                return null;

        preg_match('#г\.(.+?),#sui', $shop->address, $city_name);
        if($city_name)
        {
            $shop->city = $this->txt($city_name[1]);
            $shop->address = str_replace($city_name[0],'',$shop->address);
        }

        $shop->city = str_replace(array('(РБ)','Курской области'),array('',''),$shop->city);

        $shop->address = str_replace($shop->city,'',$shop->address);
        $shop->address = $this->fix_address($shop->address);
        $shop->address = $this->address($shop->address);

        $shop->phone = $this->address($shop->phone);

        return $shop;
    }

	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."sweetmama/about/shops/");
        preg_match('#<div id="menu">(.+?)</div>#sui', $text, $text);

        preg_match_all("#<a href='/(.+?)'><h4>(.+?)</h4></a>(<ul>(.*?)</ul>)*#sui", $text[1], $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            if($shop_value[4] == '')
            {
                $shop = $this->parse_shop($this->shopBaseUrl.$shop_value[1], $shop_value[2]);
                if($shop)$base[] = $shop;
            }

            else
            {
                preg_match_all("#<a href='/(.+?)'#sui", $shop_value[4], $sub_shops);
                foreach($sub_shops[1] as $subshop_value)
                {
                    $shop = $this->parse_shop($this->shopBaseUrl.$subshop_value, $shop_value[2]);
                    if($shop)$base[] = $shop;
                }
            }
        }


		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."sweetmama/companynews/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<th width="50"><span class="date">(.+?)</span></th>\s*<th align="left" width="90%">(.+?)</th>.+?<td colspan=2>(.+?)</td>#sui',
            $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->urlShort = $url;
            $news_item->contentShort = $news_value[3];

            $base[] = $news_item;
        }


		return $this->saveNewsResult($base);
	}
}
