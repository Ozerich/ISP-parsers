<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_mothercare_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = "http://www.mothercare.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (1);

    }

    private function parse_item($url, $categ)
    {
        $item = new ParserItem();

        $item->url = $url;
        $item->categ = $categ;

        $item->id = mb_substr($item->url, mb_strrpos($item->url,'=') + 1);

        $text = $this->httpClient->getUrlText($item->url);

        preg_match('#<tr valign=top><td><img src="/(.+?)"#sui', $text, $image);
        if($image)
        {
            $image = $this->loadImage($this->shopBaseUrl.$image[1]);
            if($image)
                $item->images[] = $image;
        }

        preg_match('#<td width=100%><p><b>(.+?)</b><br>\#(.+?)</p>(.+?)</table>#sui', $text, $info);
        $item->name = $this->txt($info[1]);
        $item->articul = $this->txt($info[2]);
        $item->descr = str_replace('Цена',"\nЦена",$this->txt($info[3]));

        preg_match('#<b>Размеры:</b>(.+?)<#sui', $text, $size_text);
        if($size_text && mb_strpos($size_text[1],"мес") === false && mb_strpos($size_text[1],"год") === false)
        {
            $sizes = explode(',', $size_text[1]);
            foreach($sizes as $size)
                $item->sizes[] = $this->txt($size);
        }

        $old_price = $new_price = "";

        preg_match('#<b>Цена:</b>(.+?)<#sui', $text, $old_price_);
        if($old_price_)
            $old_price = $this->txt($old_price_[1]);
        preg_match('#<b>Цена со скидкой:</b>(.+?)<#sui', $text, $new_price_);
        if($new_price_)
            $new_price = $this->txt($new_price_[1]);

        if($new_price != '')
        {
            $item->price = $this->txt($old_price);
            $item->discount = $this->discount($this->txt($old_price), $this->txt($new_price));
        }
        else if($old_price != "")
            $item->price = $this->txt($old_price);



        preg_match("#<b>Цвет\(а\)\:</b>(.+?)<#sui", $text, $color);
        if($color)
        {
            $colors = explode(",",$color[1]);
            foreach($colors as $color_item)
                $item->colors[] = $this->txt($color_item);
        }

        if(mb_strpos($item->descr, "Цена") !== false)
            $item->descr = trim(mb_substr($item->descr, 0, mb_strpos($item->descr, "Цена")));

            
        return $item;
    }

	public function loadItems () 
	{
    	$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?mode=cat");
        preg_match('#<a href=\?mode=cat class=m0>КАТАЛОГ</a></td></tr>(.+?)</td><td>&nbsp;</td></form></tr>#sui', $text, $text);
        preg_match_all('#<td><a href=(\?id=(\d+)) class=m1>(.+?)</a></td>#sui', $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[2];
            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($collection->url);

            preg_match_all('#<td><a href=(\?id=\d+) class=m3><span class=mbul>\+</span>&nbsp;(.+?)</a></td>#sui', $text,
                $categories, PREG_SET_ORDER);
            if(!$categories)
                $categories = array(array("1"=>$collection_value[1], "2"=>""));
            foreach($categories as $category_value)
            {
                $category_name = $this->txt($category_value[2]);
                if($category_name != "")$categ = array($category_name);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category_value[1]);

                preg_match_all('#<td><a href=(\?id=\d+) class=m3>&nbsp;&nbsp;<span class=mbul>\+</span>&nbsp;(.+?)</a></td>#sui', $text,
                    $sub_categories, PREG_SET_ORDER);
                if(!$sub_categories)
                    $sub_categories = array(array("1"=>$category_value[1], "2"=>""));
                foreach($sub_categories as $sub_category_value)
                {
                    $sub_category_name = $this->txt($sub_category_value[2]);
                    if($sub_category_name != "")
                        $categ = array($category_name,$sub_category_name);
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl.$sub_category_value[1]);
                    preg_match_all('#<tr valign=top><td width=50%><a href=(.+?)>#sui', $text, $items);
                    foreach($items[1] as $url)
                    {
                        $item = $this->parse_item($this->shopBaseUrl.$url, $categ);
                        if($item)$collection->items[] = $item;
                    }
                }
            }


            $base[] = $collection;
        }


		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $this->add_address_prefix("МЕГА");
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?id=219");
        preg_match_all('#<strong>(.+?)</strong>.+?Адрес:(.*?)<br />\s*Магазин работает:(.*?)</div>#sui', $text, $shops, PREG_SET_ORDER);
        for($i = 0; $i < count($shops); $i+=2)
        {
            $shop_value = $shops[$i];
            
            $shop = new ParserPhysical();

            if($this->address_have_prefix($this->txt($shop_value[1])))
                $shop->address = str_replace($this->txt($shop_value[1]),"",$this->txt($shop_value[2]));
            else
                $shop->address = $this->txt($shop_value[2]);
            $shop->timetable = $this->txt($shop_value[3]);
            $shop->city = "Москва";

            $shop->address = str_replace("м. Рязанский проспект","",$shop->address);
            $shop->address = $this->address($shop->address);
            $shop->address = $this->fix_address($shop->address);

            if($this->address_have_prefix($this->txt($shop_value[1])))
                $shop->address .= ", ".$this->txt($shop_value[1]);

            preg_match("#г\.(.+?),#", $shop->address, $city);
            if($city)
            {
                $shop->city = $this->txt($city[1]);
                $shop->address = str_replace($city[0],"",$shop->address);

            }

            $base[] = $shop;
        }


        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?mode=stores");
        preg_match("#class=m0>Магазины</a>(.+?)<td>&nbsp;</td></tr><tr bgcolor=\#98CCF2><td colspan=4>&nbsp;</td></tr>#sui", $text, $text);
        preg_match_all('#<td><a href=(.+?) class=m1>(.+?)</a></td>#sui', $text[1], $cities, PREG_SET_ORDER);
        for($i = 1; $i < count($cities); $i++)
        {
            $city_name = $this->txt($cities[$i][2]);
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$cities[$i][1]);

            preg_match_all('#<td valign=top>\s*<a name=.+?></a>(.+?)</td>#sui', $text, $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                $text = str_replace(array('Посмотреть карту проезда','Уже открыт!','Нарвский'),array('','','Нарвский,'),$text);
                if(mb_strpos($text,'ткрытие') !== false)
                    continue;

                preg_match('#(?:Магазин работает|Часы работы магазина)(.+)#sui', $text, $timetable);

                if($timetable)
                {
                    $shop->timetable = $this->txt($timetable[1]);
                    $text = str_replace($timetable[0], '', $text);
                }

                preg_match('#(?:Телефон|тел\.)\:*(.+)#sui', $text, $phone);

                if($phone)
                {
                    $shop->phone = $this->txt($phone[1]);
                    $text = str_replace($phone[0], '', $text);
                }

                $shop->city = $city_name;
                $shop->address = $this->txt($text);

                if($this->address_have_prefix($shop->address))
                {
                    if(mb_strpos($shop->address, '"') !== false)
                    {
                        $name = mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                        $shop->address = mb_substr($shop->address, mb_strpos($shop->address,'"') + 1);
                        $name .= mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                        $shop->address = mb_substr($shop->address, mb_strpos($shop->address,'"') + 1).", ".$name;
                    }
                }
                $shop->address = $this->address($shop->address);
                $shop->address = str_replace($shop->city.",",'',$shop->address);
                $shop->address = $this->fix_address($shop->address);

                $base[] = $shop;
            }
        }

        

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."?mode=news";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<span class=n_dt>(.+?)</span>(.+?)<a href=(\?mode=news&id1=(.+?)&pg=1)>Подробнее</a>#sui', $text,
            $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = mb_substr($this->txt($news_value[1]), 0, -1);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[3];
            $news_item->id = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#'.$news_item->date.'.+?</p>(.+?)<table#sui', $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

        return $this->saveNewsResult($base);
	}
}
