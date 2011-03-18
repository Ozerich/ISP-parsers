<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';
require_once PARSERS_BASE_DIR . '/parsers/addons/phpQuery.php';


/* Для сайта f5jeans.ru нужно создать следующий класс.
 * Сюда записывается всё, что связано с парсингом сайта f5jeans.ru.
 * 		loadItems - парсинг коллекции
 * 		parseF5jeansGoodsPage - парсинг товара
 * 		loadPhysicalPoints - парсинг торговых точек
 * 		loadNews - парсинг торговых точек
 */
class ISP_f5jeans_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://f5jeans.ru/'; // Адрес главной страницы сайта 

    private function parse_item($url, $categ)
    {
        $item = new ParserItem();

        $item->url = $url;
        $item->categ = $categ;

        $text = $this->httpClient->getUrlText($item->url);

        $item->id = mb_substr($url, mb_strrpos($url, '=') + 1);

        preg_match('#<span class="grey">ID: </span>(.+?)</p>#sui', $text, $id);
        if($id)$item->articul = $id[1];

        preg_match('#<span class="grey">Состав: </span>(.+?)</p>#sui', $text, $structure);
        if($structure)$item->structure = $this->txt($structure[1]);

        preg_match('#<span class="grey">Цвет: </span>(.+?)</p>#sui', $text, $colors);
        if($colors)$item->colors = $this->txt($colors[1]);

        preg_match('#<span class="grey">Ткань: </span>(.+?)</p>#sui', $text, $material);
        if($material)$item->material = $this->txt($material[1]);

        preg_match('#<p style="font-size:12px; font-weight: bold; margin-bottom: 12px;">(.+?)</p>#sui', $text, $name);
        if($name)$item->name = $this->txt($name[1]);

        preg_match('#<a href="(img.+?)"#sui', $text, $image);
        if($image)
        {
            $image = $this->loadImage($this->shopBaseUrl.'catalog/'.$image[1]);
            if($image)
                $item->images[] = $image;
        }

        preg_match('#<p style="font-size:11px; font-weight: bold;">(.+?)<p>\&nbsp;</p>#sui', $text, $descr);
        if($descr)$item->descr = $this->txt($descr[1]);

        return $item;
    }

    
	public function loadItems () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<h1 class="red">(.+?)</h1>#sui', $text, $col_name);
        preg_match_all('#<h2 class="red">(.+?)</h2>(.+?)</ul>#sui', $text, $collections, PREG_SET_ORDER);
        $id = 1;
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->name = $this->txt($col_name[1]." - ".$this->txt($collection_value[1]));
            $collection->id = $id++;
            $collection->url = $this->shopBaseUrl;

            preg_match_all('#<li><a href="/(.+?)"\s*>(.+?)</a></li>#sui',$collection_value[2], $categories, PREG_SET_ORDER);
            foreach($categories as $category)
            {
                $categ_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]);

                preg_match_all('#<a href="/(catalog/detail.php\?ID=(\d+))">#sui', $text, $items, PREG_SET_ORDER);
                foreach($items as $item)
                    $collection->items[] = $this->parse_item($this->shopBaseUrl.$item[1], $categ_name);
                
            }

            $base[] = $collection;
        }
        
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $metros = array('Академическая / Тульская','Алтуфьево','Водный стадион','Проспект Мира','Фили / Багратионовская', 'Теплый стан');
		$base = array ();

		$text = $this->httpClient->getUrlText($this->shopBaseUrl."buy/");
        preg_match_all('#<tr><td style="padding-top: 4px; padding-right: 4px; padding-bottom: 4px; padding-left: 4px; font-size: 85%; ">(.+?)</tr>#sui', $text, $shops);
        
        foreach($shops[1] as $shop_text)
        {
            preg_match('#<strong>(.+?)</strong>(.+?)</td><td style="padding-top: 4px; padding-right: 4px; padding-bottom: 4px; padding-left: 4px; font-size: 85%; ">(.+?)</td>#sui', $shop_text, $info);

            $shop = new ParserPhysical();

            $shop->city = $this->txt($info[1]);
            $shop->address = $this->txt(mb_substr($info[2],2));
            $shop->phone = $this->txt($info[3]);

            foreach($metros as $metro)
                $shop->address = str_replace('м. '.$metro, '', $shop->address);
            $shop->address = $this->address($shop->address);
            if(mb_substr($shop->address, 0, 4) == '"F5"')
                $shop->address = mb_substr($shop->address, 5);
            if(mb_substr($shop->address, 0, 5) == '"999"')
                $shop->address = mb_substr($shop->address, 6);
            $shop->address = str_replace(array('ТРЦ Кит','"Мульти"'),array('ТРЦ Кит,',''),$shop->address);
            $shop->address = $this->fix_address($shop->address);
            $shop->address = $this->address($shop->address);
            if(mb_substr($shop->address, 0, 2) == 'F5')
                $shop->address = mb_substr($shop->address, 3);
            if(ord($shop->address[0]) == 194)
                $shop->address = mb_substr($shop->address, 1);
            $shop->address = trim($shop->address);
            $base[] = $shop;
        }

        return $this->savePhysicalResult ($base);
	}
	
	public function loadNews ()
	{
		$base = array ();
		$url = 'http://f5jeans.ru/blog/';
		$news = $this->httpClient->getUrlText ($url);
		$document=phpQuery::newDocument($news);
		
		$content=$document->find('.grid_10 > .content > .content-nc > div:eq(2)');
		if ($content == "") $this->parseError ("Can't parse news content div\n");
		pq($content)->find('.system-nav-orange')->remove();
		
		$countNews=pq($content)->find('.h-note-head.margin-top2')->size();
		if ($countNews==0) $this->parseError ("Number of news = 0");
		for ($i=0;$i<$countNews;$i++)
		{
			//заголовок
			$h2=pq($content)->find('.h-note-head.margin-top2:eq('.$i.') > .grid_8.alpha > h2');
			$header=trim(pq($h2)->find('a')->text());
			if ($header=="") $this->parseError ("Can't parse news header or header is empty\n");
			
			//ссылка на новость
			$urlFull=$this->shopBaseUrl . pq($h2)->find('a')->Attr('href');
			if ($urlFull=="") $this->parseError ("Can't parse news full url\n");
			
			//id
			if (!preg_match('/post_id=(\d+)/',$urlFull,$r))
				$this->parseError ("Can't parse news id\n");
					
			//краткое содержание
			$contentShort=pq($content)->find('.b-blog-note:eq('.$i.')');
			if ($contentShort=="") $this->parseError ("Can't parse news short content\n");
			pq($contentShort)->find('img')->remove();
			$contentShort=preg_replace('/<br><p><a href="\/blog\/\?page=post&blog=o_mode&post_id=\d+">Подробнее[^<]+<\/a>/','',pq($contentShort)->html());
			$contentShort = preg_replace("|[\s]+|s", " ", trim($contentShort));
			$contentShort = preg_replace("|^(<br>)+|s", "", $contentShort);
						
			//дата
			$date=pq($content)->find('.b-note-footer1:eq('.$i.')')->text();
			if ($date=="") $this->parseError ("Can't parse news date\n");
			
			$newsFull = $this->httpClient->getUrlText ($urlFull);
			$documentFull=phpQuery::newDocument($newsFull);
			//полное содержание
			$contentFull=trim($documentFull->find('.b-blog-note')->html());
			if ($contentFull=="") $this->parseError ("Can't parse news full content\n");
			$contentFull = preg_replace("|[\s]+|s", " ", $contentFull);
			
			$base[] = $newsElem = new ParserNews();
						
			$newsElem->id           = $r[1];
			$newsElem->date         = $date;
			$newsElem->contentShort = $contentShort;
			$newsElem->contentFull  = $contentFull;
			$newsElem->urlShort     = $url;
			$newsElem->urlFull      = $urlFull;
			$newsElem->header       = $header;
		}
		
		return $this->saveNewsResult ($base);	
	}
}
