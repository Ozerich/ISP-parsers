<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';
require_once PARSERS_BASE_DIR . '/parsers/addons/phpQuery.php';

/* Для сайта baon.ru нужно создать следующий класс.
 * Сюда записывается всё, что связано с парсингом сайта baon.ru.
 * НЕОБХОДИМО реализовать 2 функции:
 * 		loadItems - парсинг товаров
 * 		loadPhysicalPoints - парсинг торговых точек
 */

class ISP_baon_ru extends ItemsSiteParser_Ozerich {

    protected $shopBaseUrl = 'http://baon.ru/'; // Адрес главной страницы сайта
    protected $stokLink = "idreg/30/stok/true/";
    private $itemList;
    private $shopList;
    private $items;
    private $thumb = array();

	public function loadNews ()
	{
		$base = array ();
		$news_url	= 'http://www.baon.ru/index/news/';
		$news_data	= $this->httpClient->getUrlText ($news_url);;
		
		preg_match_all('#<div class="news">\s*<p class="date">(.+?)<br />(.+?)</p>#sui',$news_data,$news_all,PREG_SET_ORDER);
		
		foreach ($news_all as $one_news)
		{
            $news_item = new ParserNews();

            $news_item->date = $this->date_to_str($one_news[1]);
            $news_item->contentShort = $one_news[2];
            $news_item->header = $this->txt($news_item->contentShort);
            $news_item->urlShort = $news_url;

            $base[] = $news_item;
        }

		return $this->saveNewsResult ($base); /* Есть на сайте нет новостей, заменить
			этот код на return null; */
	}

    function __construct($savePath) {
        parent::__construct($savePath);
    }

    private function parse_item($url, $categ)
    {
        $text = $this->httpClient->getUrlText($url);

        $item = new ParserItem();

        $item->url = $url;
        $item->categ = $categ;
        $url = mb_substr($url, 0, -1);
        $item->id = mb_substr($url, mb_strrpos($url, '/') + 1);

        preg_match('#<b>Артикул:</b>(.+?)\n#sui', $text, $articul);
        if($articul)$item->articul = $this->txt($articul[1]);

        preg_match('#<b>Цена:</b>\s*<span style="font-size: 15pt;">(.+?)</span>#sui', $text, $price);
        if($price)$item->price = $this->txt($price[1]);

        preg_match_all('#<div class="coloritem" style="width: auto;">(.+?)</div>#sui', $text, $colors);
        foreach($colors[1] as $color)
            $item->colors[] = $this->txt($color);

        preg_match_all('#span class="ctsize" id=".+?">(.+?)</span>#sui', $text, $sizes);
        foreach($sizes[1] as $size)
            $item->sizes[] = $this->txt($size);

        preg_match('#<div class="model-leftcol">\s*<a href="/(.+?)"#sui', $text, $image);
        if($image)
        {
            $image = $this->loadImage($this->shopBaseUrl.$image[1]);
            if($image)
                $item->images[] = $image;
        }
        
        preg_match('#<div class="material block">(.+?)</div>#sui', $text, $structure);
        if($structure)
            $item->structure = $this->txt($structure[1]);


        return $item;
    }

    public function loadItems() {

        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<h1 class="collection-name".+?>(.+?)</h1>(.+?)<h1 class="collection-name"#sui', $text, $col_name);
        $text = $col_name[2];
        preg_match_all('#<li><h1>(.+?)</h1></li>(.+?)</ul>#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();
            $collection->name = $col_name." - ".$collection_value[1];
            $collection->url = $this->shopBaseUrl;
            $text = $collection_value[2];

            $this->items = array();

            preg_match_all('#<li><h2>(.+?)</h2></li>(.+?)(?:<li><h2>|$)#sui', $text, $categories, PREG_SET_ORDER);
            foreach($categories as $category)
            {
                preg_match_all('#<li><a class="window-href-catalog" href="/(.+?)">(.+?)</a></li>#sui', $category[2], $sub_categories, PREG_SET_ORDER);
                foreach($sub_categories as $sub_category)
                {
                    
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl.$sub_category[1]);
                    $categ = array($category[1], $sub_category[2]);
                    preg_match('#<ul class="catalog">(.+?)</ul>#sui', $text, $text);
                    preg_match_all('#<li><a href="/(.+?)" class="window-href-model">#sui', $text[1], $items);
                    
                    foreach($items[1] as $item_url)
                        $collection->items[] = $this->parse_item($this->shopBaseUrl.$item_url, $categ);
                }
            }

            $collection->items = $this->items;
            $base[] = $collection;
        }

        
        
    
        return $this->saveItemsResult($base);
    }

    public function loadPhysicalPoints() {
        $base = array();
        $text = $this->httpClient->getUrlText($this->shopBaseUrl.'shop/index/');
        preg_match('#<ul class="collection-items" style="list-style: none;"(.+?)</ul>#sui', $text, $text);
        preg_match_all('#<li><a href="/(shop.+?)" class="window-href with-image">(.+?)</a></li>#sui', $text[1], $cities, PREG_SET_ORDER);
        
        foreach ($cities as $city)
        {
            $city_name = $this->txt($city[2]);
            $link = $this->shopBaseUrl.$city[1];
            $page = $this->httpClient->getUrlText($link);
            preg_match_all('#<a name="shop(\d+)"></a>\s*<div class="shopitem"\s*>(.+?)</div>\s*</div>#sui', $page, $elements, PREG_SET_ORDER);
            foreach ($elements as $element)
            {
                $text = $element[2];
                $data = array();
                $data['b_stock'] = ($link == $this->shopBaseUrl. 'shop/index/' . $this->stokLink) ? true : false;
                $data['id'] = $element[1];
                $data['city'] = $city_name;

                preg_match('#<span>Телефоны:\s*</span>(.+?)(</div>|$)#sui', $text, $phone);
                if($phone)$data['phone'] = $this->txt($phone[1]);

                preg_match('#<span>Время работы:\s*</span>(.+?)(</div>|$)#sui', $text, $timetable);
                if($timetable)$data['timetable'] = $this->txt($timetable[1]);

                preg_match('#<span>Адрес:\s*</span>(.+?)(</div>|$)#sui', $text, $address);
                if($address)$data['address'] = $this->txt($address[1]);

                $data['address'] = $this->address($data['address']);
                $data['address'] = $this->fix_address($data['address']);

                preg_match('#г\.(.+?),#sui', $data['address'], $city);
                if($city)
                {
                    $data['city'] = $this->txt($city[1]);
                    $data['address'] = str_replace($city[0], '', $data['address']);
                }

                
                if(!empty($data['address']) && $data['address'] != $data['city'])
                {
                    $phys = new ParserPhysical($data);
                    $base[] = $phys;
                }
            }
        }
        return $this->savePhysicalResult($base);
    }

}

