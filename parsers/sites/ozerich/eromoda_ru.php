<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_eromoda_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.eromoda.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }

    private function parse_item($url, $categ)
    {
        $item = new ParserItem();

        $item->url = $url;
        $item->categ = $categ;
        $item->id = mb_substr($item->url, mb_strrpos($item->url, "=") + 1);

        $text = $this->httpClient->getUrlText($item->url);
        $text = $this->delete_comments($text);

        preg_match('#<p><b></b></p>(.+?)<tr>#sui', $text, $descr);
        if($descr)
            $item->descr = $this->txt($descr[1]);


        preg_match('#<td class="main">&nbsp;&nbsp;&nbsp;<b>Производитель:</b>(.+?)</td>#sui', $text, $brand);
        if($brand)
            $item->brand = $this->txt($brand[1]);

        preg_match('#<td class="main">&nbsp;&nbsp;&nbsp;<b>Состав:</b>(.+?)</td>#sui', $text, $structure);
        if($structure)
            $item->structure = $this->txt($structure[1]);

        preg_match('#<td class="main">&nbsp;&nbsp;&nbsp;<b>Страна-изготовитель:</b>(.+?)</td>#sui', $text, $made_in);
        if($made_in)
            $item->made_in = $this->txt($made_in[1]);

        preg_match('#<td class="main"><b>Характеристики товара:</b></td>(.+?)<td><img src="images/pixel_trans.gif"#sui',
            $text, $descr);
        if($descr)
        {
            $descr = $this->txt($descr[1]);
            $item->descr .= "\nХарактеристики товара:\n".$descr;
        }

        preg_match('#<td class="pageHeading" valign="top">(.+?)</td>#sui', $text, $info);
        if($info)
        {
            preg_match('#\((.+?)\)(.+?)$#sui', $this->txt($info[1]), $info);
            if($info)
            {
                $item->name = $this->txt($info[2]);
                $item->articul = $this->txt($info[1]);
            }
        }

        preg_match('#<td class="pageHeading" align="right" valign="top">(\d+)\.(<span class="productSpecialPrice">(\d+)\.)*#sui', $text, $price);
        if($price)
        {
            $item->price = $this->txt($price[1]);
            if(isset($price[3]) && $price[3] != "")
                $item->discount = $this->discount($item->price, $this->txt($price[3]));
        }

        if(mb_strpos($item->brand,',') !== false)
            $item->brand = mb_substr($item->brand, 0, mb_strrpos($item->brand, ','));

        preg_match_all('#<noscript>\s*<a href="(.+?)"#sui', $text, $images);
        foreach($images[1] as $url)
        {
            $url = preg_replace('#.\.jpg#sui', 'm.jpg', $url);
            $image = $this->loadImage($url);
            if($image != null)
                $item->images[] = $image;
        }

        if($item->brand == "РОССИЯ")
        {
            $item->brand = "";
            $item->made_in = "Россия";
        }

        return $item;
    }

	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<table border="0" width="100%" cellspacing="0" cellpadding="0" class="templateinfoBox_right">(.+?)</table>#sui',
            $text, $text);
        preg_match_all('#<a href="(.+?)".*?>(.+?)</a>#sui', $text[1], $collections, PREG_SET_ORDER);
        for($i = 0; $i < count($collections) - 1; $i++)
        {
            $collection = new ParserCollection();

            $collection->url = (mb_strpos($collections[$i][1],"http:") !== false) ? $collections[$i][1] :
                    $this->shopBaseUrl.$collections[$i][1];
            $collection->name = $this->txt($collections[$i][2]);
            $collection->id = mb_strpos($collection->url, "=") !== false ? mb_substr($collection->url, mb_strrpos($collection->url,"=") + 1):
                mb_substr($collection->url, mb_strrpos($collection->url, '/') + 1, -4);

            $text = $this->httpClient->getUrlText($collection->url);
            preg_match_all('#&nbsp;&nbsp;<a href="(.+?)">.+?<span class="headerNavigation">(.+?)</a>#sui', $text,
                $categories, PREG_SET_ORDER);
            if(!$categories)
                $categories = array(array("1"=>$collection->url, "2"=>""));
            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($category[1]);
                preg_match_all('#&nbsp;&nbsp;&nbsp;&nbsp;<a href="(.+?)">.+?<span class="headerNavigation">(.+?)</a>#sui', $text,
                    $subcategories, PREG_SET_ORDER);
                if(!$subcategories)
                    $subcategories = array(array("1"=>$category[1], "2"=>""));

                foreach($subcategories as $subcategory)
                {
                    $subcategory_name = $this->txt($subcategory[2]);
                    $categ = array();
                    if($category_name != '')$categ[] = $category_name;
                    if($subcategory_name != '')$categ[] = $subcategory_name;

                    $text = $this->httpClient->getUrlText($subcategory[1]);
                    preg_match('#<td class="productListing-heading">&nbsp;<a href=".+?Cpath=(.+?)&#sui', $text, $id);
                    if(!$id)continue;
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl."index.php?cPath=".$id[1]."&row_by_page=1000");

                    preg_match_all('#<td align="center" class="productListing-data">&nbsp;<a href="(.+?)">#sui', $text, $items);
                    foreach($items[1] as $url)
                    {
                        $item = $this->parse_item($url, $categ);
                        if($item)
                            $collection->items[] = $item;
                    }

                }
            }

            if(!$collection->items)continue;

            $base[] = $collection;
        }


        return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."information.php?pages_id=14");
        preg_match('#<span>Москва</span>(.+?)Магазины-партнеры#sui', $text, $text);

        preg_match_all('#<font size="1">(?:<span>)*\d(.+?)(?=<font size="1">(?:<span>)*\d|$)#sui', $text[1], $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $shop->city = "Москва";

            $text = $this->txt($shop_value[1]);
            $text = mb_substr($text, mb_strpos($text, 'м.') + 2);

            $pos = mb_strpos($text, ", ");
            if($pos === false || mb_strpos($text, ". ") < $pos)
                $pos = mb_strpos($text, ". ");
            $text = mb_substr($text, $pos + 2);
            $text = str_replace('НАШ НОВЫЙ МАГАЗИН,','',$text);

            if(mb_strpos($text, 'Проезд') !== false)
                $text = mb_substr($text, 0, mb_strpos($text, 'Проезд'));
            else if(mb_strpos($text, 'Схема проезда') !== false)
                $text = mb_substr($text, 0, mb_strpos($text, 'Схема проезда'));

            preg_match("#тел\.\:*(.+?)\.#sui", $text, $phone);
            if($phone)
            {
                $shop->phone = $this->txt($phone[1]);
                $text = str_replace($phone[0], '', $text);
            }

            preg_match('#Часы работы\:*(.+?)$#sui', $text, $timetable);
            if($timetable)
            {
                $shop->timetable = $this->txt($timetable[1]);
                $text = str_replace($timetable[0], '', $text);
            }

            if(mb_strpos($text, '(495)') !== false)
            {
                $shop->phone = mb_substr($text, mb_strpos($text, '(495)'));
                $text = mb_substr($text, 0, mb_strpos($text, '(495)'));
            }

            $text = trim(mb_substr(trim($text), 0, -1));
            if($this->address_have_prefix($text))
            {
                $name = mb_substr($text, 0, mb_strpos($text, ','));
                $text = mb_substr($text, mb_strpos($text, ',') + 1).", ".$name;
            }

            $shop->address = $text;

            $base[] = $shop;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<a class="smallText" href="(http://www.eromoda.ru/newsdesk_info.php\?newsdesk_id=(\d+))">(.+?)</a>#sui',
            $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $this->shopBaseUrl;
            $news_item->urlFull = $news_value[1];
            $news_item->id = $news_value[2];
            $news_item->contentShort = $news_value[3];
            $news_item->header = $this->txt($news_value[3]);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<table border="0" cellpadding="4" width="100%"><tbody><tr><td><p><font size="2">(.+?)</table>#sui',
                $text, $content);
            if(!$content)
                preg_match('#<table border="0" cellpadding="2" width="(?:100%|520)" id="table1">(.+?)</table>#sui',$text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
