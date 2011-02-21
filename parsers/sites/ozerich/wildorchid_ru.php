<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_wildorchid_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://shop.wildorchid.ru/'; // Адрес главной страницы сайта
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
        $this->httpClient->setRequestsPause (0.5);

        $this->rasdels = array(
                '1884' => array('id'=>1, 'items' =>1, 'name'=>'Дикая орхидея'),
                '5229' => array('id'=>2, 'items' =>1, 'name'=>'Бюстье'),
                '22491' => array('id'=>4, 'items' =>1, 'name'=>'VI Легион'),
                '22492' => array('id'=>7, 'items' =>1,'name'=>'Дефиле'),
                '1030' => array('id'=>'bazar','items' =>0, 'name'=>'Бельевой базар')
            );
    }
        public function loadItems ()
        {
            $base = array ();

            $dst = $this->getDstShopId();
            $dst = $this->rasdels[$dst];

            if(!$dst['items'])return null;

            $text = $this->httpClient->getUrlText("http://shop.wildorchid.ru/index.aspx?shop=".$dst['id']);
            
                if(!preg_match('~<ul class="category_menu">(.*?)</div>~is', $text, $ulInner)) {
                    $this->parseError('Can not parse category menu');
                }
                if (!preg_match_all('~<li\s*><a href="/catalog/catalog\.aspx\?shop=(\d+)&categoryid=(\d+)">(.*?)</a></li>~is', $ulInner[1], $collections, PREG_SET_ORDER)) {
                    $this->parseError('Can not parse category links');
                }

                foreach($collections as $collection_value)
                {

                    $collection = new ParserCollection();
                    $collection->id = $collection_value[2];
                    $collection->name = $collection_value[3];
                    //$collection->url = "http://shop.wildorchid.ru/index.aspx?shop=".$dst['id']."&categoryid=".$collection->id;
                    $collection->url = "http://shop.wildorchid.ru/catalog/catalog.aspx?shop=".$dst['id']."&categoryid=".$collection->id;

                    $text = $this->httpClient->getUrlText($collection->url);


                    $url = "http://shop.wildorchid.ru/Catalog/Card.aspx?shop=".$dst['id']."&categoryid=".$collection->id;
                    
                    $page = 0;
                    $k = 0;
                    $first =true;
                    while (true) {
                        if ($page) {
                            if (preg_match('~CurrentPageLabel">(\d+)</span>~is',$text, $p)) {
                                $page = $p[1];
                                if( $k > 1) {
                                    $first = false;
                                }
                                if (!$first && $page == 1) break;//Если первая страница, значит постраничка окончилась
                            } else {
                                break;
                            }
                            if (!preg_match('~lbNext"~is',$text)) {//Последняя страница
//                                echo 'Last page;';
                                $page = 0;
                                break;
                            }


                            
                            preg_match('~id="__VIEWSTATE" value="([^"]+)"~is', $text, $form);
                            preg_match('~id="__EVENTVALIDATION" value="([^"]+)"~is', $text, $form2);
                            
                            $text = $this->httpClient->getUrlText(
                                $url,
                                array(
                                    '__VIEWSTATE'=>$form[1],
                                    '__EVENTVALIDATION'=>$form2[1],
                                    '__EVENTTARGET' => 'ctl00$ContentPlaceHolder1$dpStyles$ctl00$lb21',
                                    '__EVENTARGUMENT' => '',
                                    '__LASTFOCUS' => '',
                                    'ctl00$ContentPlaceHolder1$tbSearch'=>'',
                                    'ctl00$ContentPlaceHolder1$ddlSort1'=>'',
                                    'ctl00$ContentPlaceHolder1$ddlSort2'=>'',
                                    'ctl00$ContentPlaceHolder1$btnSearchSale'=>'',
                                    'ctl00$tbEmail'=>''

                                )
                            );
                            $this->httpClient->resetParameters();
                        } else {
//                            echo 'GET Category:';
                            $text = $this->httpClient->getUrlText($url);
                            $page = 1;
                        }
                        $k++;


                        if (preg_match_all('~<a href=\'/Catalog/StyleCard\.aspx\?&shop=\d+&categoryid=\d+&style=(\d+)\'~is', $text, $items)) {


  
                             foreach ($items[1] as $j=>$styleId) {
                                 $itemUrl = "http://shop.wildorchid.ru/Catalog/StyleCard.aspx?&shop=".$dst['id']."&categoryid=".$collection->id."&style=".$styleId;

                                 //$itemUrl = "http://shop.wildorchid.ru/Catalog/StyleCard.aspx?&shop=1&categoryid=2046&type=%d2%f0%f3%f1%fb&style=19329";
                                 //usleep(200000);
//                                 echo 'GET item:';
//$itemUrl = "http://shop.wildorchid.ru/Catalog/StyleCard.aspx?&shop=1&categoryid=203&style=14871";
                                 $itemHTML = $this->httpClient->getUrlText($itemUrl);
                                 $item = new ParserItem ();
                                 $item->id = $styleId;
                                 $item->url = $itemUrl;

                                 preg_match('#<ul class="preview-photo">(.+?)</ul>#sui', $itemHTML, $images_text);
                                 preg_match_all('#src="(.+?)"#sui', $images_text[1], $images_url);
                                 foreach($images_url[1] as $image_url)
                                 {
        
                                    $imgUrl = $this->urlencode_partial(str_replace('_k','',$image_url));
                                    $this->httpClient->getUrlBinary($imgUrl);
                                    $image = new ParserImage();
                                    $image->url = $imgUrl;
                                    $image->path = $this->httpClient->getLastCacheFile();
                                    switch ($this->httpClient->getLastCtype()) {
                                        case 'image/jpeg':
                                            $image->type = 'jpeg';
                                            break;
                                        case 'image/gif';
                                            $image->type = 'gif';
                                            break;
                                        case 'image/png';
                                            $image->type = 'png';
                                            break;
                                        default:
                                            $image->type = 'jpeg';
                                            break;
                                        }
                                    $item->images[] = $image;
                                }
                                
                                preg_match('~<h1>(.*?)</h1>~is', $itemHTML, $title);
                                preg_match('~<h3>(.*?)</h3>~is', $itemHTML, $title2);
                                $item->name = $title[1];
                                $item->brand = $title2[1];
                                preg_match('~<p>(Линия:.*?)</p>~is', $itemHTML, $line);
                                preg_match('~<p class="desc">(.*?)</p>~is', $itemHTML, $desc);
                                $item->descr = $line[1] . '. ' . strip_tags($desc[1]);
                                preg_match('~<p>Стиль:(.*?)</p>~is', $itemHTML, $style);
                                preg_match('~<p>Страна-производитель:(.*?)</p>~is', $itemHTML, $producer);
                                preg_match('~<p>Материал:(.*?)</p>~is', $itemHTML, $structure);
                                $item->structure = $structure[1];
                                $item->made_in = $producer[1];
                                $item->articul = $style[1];
                                preg_match_all('~<div class="tooltip">(.*?)</div>~is', $itemHTML, $colors);
                                $colorNames = array();
                                foreach ($colors[1] as $color) {
                                    $colorNames[] = strip_tags($color);
                                }
                                $item->colors = $colorNames;

                                preg_match('#<ul class="size">(.+?)</ul>#sui', $itemHTML, $size_text);
                                preg_match_all('#value="(.*?)"#sui', $size_text[1], $sizes);
                               
                                foreach($sizes[1] as $size)
                                    if($size != '')
                                        $item->sizes[] = $size;
                                
                                preg_match('~<div id="price".*?>Цена: (.*?)</div>~is', $itemHTML, $price);
                                $intPrice = str_replace(array('р.', '&nbsp;'), array('', ''), strip_tags($price[1]));
                                $intPrice = preg_replace ("/ /u", "", $intPrice);
                                $item->price = $intPrice;
                                $collection->items[] = $item;
                                //print_r($item);exit();
                               // print_r($item);exit();
                             }
                         }
                    }
                $base[] = $collection;
            }

            
            return $this->saveItemsResult($base);

	}

	public function loadPhysicalPoints ()
	{
            $base = array ();
            $dst = $this->getDstShopId();
            $dst = $this->rasdels[$dst]['id'];

            if($dst == 'bazar')
            {
                $text = $this->httpClient->getUrlText("http://www.beba.ru/store/");
                preg_match('#<div id="Content">(.+?)</table>#sui', $text, $text);
                preg_match_all('#<h1>(.+?)</h1>(.+?)<space></space>#sui', $text[1], $cities, PREG_SET_ORDER);
                foreach($cities as $city)
                {
                    $city_name = $city[1];
                    preg_match_all('#<p><a href="./((\d+).html)">(.+?)</a></p>#sui', $city[2], $shops, PREG_SET_ORDER);
                    foreach($shops as $shop_value)
                    {
                        $shop = new ParserPhysical();

                        $shop->id = $shop_value[2];
                        $shop->city = $city_name;
                        $shop->address = $this->address($shop_value[3]);

                        $text = $this->httpClient->getUrlText("http://www.beba.ru/store/".$shop_value[1]);

                        preg_match('#<p>Часы работы:(.+?)</p>#sui', $text, $timetable);
                        if($timetable)$shop->timetable = $this->txt($timetable[1]);

                        preg_match('#<p>тел.:(.+?)</p>#sui', $text, $phone);
                        if($phone)$shop->phone = $this->txt($phone[1]);
                    
                        $base[] = $shop;
                    }
                }
            }
            else
            {
            
            $url = 'http://shop.wildorchid.ru/Info/Shops.aspx?country=1&city=&td='.$dst;
            $text = $this->httpClient->getUrlText($url);
            preg_match('#<table cellspacing="0" id="ContentPlaceHolder1_gvShops" style="border-collapse:collapse;">(.+?)</table>#sui', $text, $text);
            preg_match_all('#<tr>\s*<td>(.+?)</td><td>.+?</td><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td><td>(.+?)</td>\s*</tr>#sui', $text[1], $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                    $shop = new ParserPhysical();

                    $shop->city = $shop_value[1];

                    $shop_value[3] = mb_substr($shop_value[3], 0, mb_strpos($shop_value[3], ","));
                    $shop->address = $this->txt($this->txt($shop_value[2]).", ".$shop_value[3]);
                    $shop->phone = $this->txt($shop_value[4]);
                    $shop->timetable = $this->txt($shop_value[5]);

                    if($shop->address[0] == ',')$shop->address = mb_substr($shop->address, 2);

                    if($shop->address == '')continue;
        
                    $base[] = $shop;
                }
            }
            return $this->savePhysicalResult($base);
	}

	function loadNews ()
	{        
		return null;
	}
}

