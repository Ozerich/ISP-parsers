<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';
require_once PARSERS_BASE_DIR . '/parsers/addons/phpQuery.php';

/* Для сайта accessorize.ru нужно создать следующий класс.
 * Сюда записывается всё, что связано с парсингом сайта tervolina.ru.
 * НЕОБХОДИМО реализовать 2 функции:
 * 		loadItems - парсинг товаров
 * 		loadPhysicalPoints - парсинг торговых точек
 */
class ISP_accessorize_ru extends ItemsSiteParser_Drakon
{
	protected $shopBaseUrl = 'http://accessorize.ru'; // Адрес главной страницы сайта
        const SITEMAP_URL = "http://accessorize.ru/monsoon/sitemap/";
        private $itemList;
        private $shopList;

	public function loadNews ()
	{
		$base = array ();
		$base_url	= 'http://www.accessorize.ru/';
		$short_url	= $base_url.'news/';
		$news_data	= $this->httpClient->getUrlText ($short_url);
		
		preg_match_all('#<div[^>]*>[^<]*<p[^>]*>([\d]{2}\.[\d]{2}\.[\d]{4})</p>[^<]*<p[^>]*><strong><a href="(([^"]+)/)">([^<]*)</a></strong></p>#sui',$news_data,$news_all,PREG_SET_ORDER);
		
		// print_r($news_all);
		// die();
		
		foreach ($news_all as $one_news)
		{
			$news_url	= $short_url.$one_news[2];
			$news_data	= $this->httpClient->getUrlText ($news_url);
			
			if (preg_match('#(<h2>([^<]|<)*)<p><a href="/news/">Вернуться в новости</a></p>#sui',$news_data,$details))
			{
				$base[]	= $newsElem 	= new ParserNews();	
				$newsElem->id			= $one_news[3];
				$newsElem->urlShort     = $short_url;
				$newsElem->urlFull      = $news_url;
				$newsElem->contentFull	= $details[1];
				$newsElem->contentShort = $one_news[4];
				$newsElem->header 		= $one_news[4];
				$newsElem->date			= $one_news[1];
			}
			else
				$this->parseWarning("Unknown news format at url '{$news_url}'");
		}
		
		
		return $this->saveNewsResult ($base); /* Есть на сайте нет новостей, заменить
			этот код на return null; */
	}
	
	protected function get_month_number($name)
	{
		$months	= array('Январь'=>1 ,'Февраль'=>2 ,'Март'=>3,'Апрель'=>4,'Май'=>5,'Июнь'=>6,'Июль'=>7,'Август'=>8,'Сентябрь'=>9,'Октябрь'=>10,'Ноябрь'=>11,'Декабрь'=>12);
		if (isset($months[$name]))
			return $months[$name];
		else
			return 0;
	}
        
        private function getSiteMap()
        {
            $page = $this->httpClient->getUrlText(self::SITEMAP_URL);

            $results = phpQuery::newDocumentHTML($page, 'cp1251');
            $elements = $results->find('li > a[href="/women/"]')->parent()->next("ul")->find('ul > li');
            $i = 0;
            foreach ($elements as $element)
            {
                $list['women'][$i] = pq($element)->find('a')->attr('href');
                $i++;
            }
            $elements = $results->find('li > a[href="/children/"]')->parent()->next("ul")->find('ul > ul > li');
            $i = 0;
            foreach ($elements as $element)
            {
                $list['children'][$i] = pq($element)->find('a')->attr('href');
                $i++;
            }
            $elements = $results->find('li > a[href="/accessorize/"]')->parent()->next("ul")->find('ul > li > a');
            $i = 0;
            foreach ($elements as $element)
            {
                $list['accessorize'][$i] = pq($element)->attr('href');
                $i++;
            }
            $this->itemList = $list;
            $elements = $results->find('li > a[href="/city/"]')->parent()->next("ul")->find('ul > li > a');
            $i = 0;
            $list = array();
            print_r($elements);exit();
            foreach ($elements as $element)
            {
                $city = explode("/",pq($element)->attr('href'));
                $list[$city[2]][$i] = pq($element)->attr('href');
                $i++;
            }
            $this->shopList = $list;
        }


        function  __construct($savePath)
        {
            parent::__construct($savePath);
            $this->httpClient->setConfig (array('curloptions' => array (CURLOPT_TIMEOUT => 600)));
            $this->getSiteMap();
        }

        private function getItems($items,$path)
        {
            $output = array();
            foreach($items as $id => $item)
            {
                $output[$id] = $this->parseTervolinaGoodsPage($this->shopBaseUrl."/".$item, trim($item,"/"),$path);
                if($output[$id] === FALSE)
                    unset($output[$id]);
            }
            return $output;
        }
	function parseTervolinaGoodsPage ($url, $itemId,$path)
	{
		$page = $this->httpClient->getUrlText($url);
                $results = phpQuery::newDocumentHTML($page, 'cp1251');
                $data = array();
                $exist = $results->find(".tovar")->prev('h2')->text();
                if($exist != "")
                {
                    $data['title'] = $results->find(".tovar")->prev('h2')->text();
                    $id = explode("/",$itemId);
                    $id = array_reverse($id);
                    if(is_numeric($id[0]))
                        $id = intval($id[0]);
                    else
                        $id = $itemId;
                    $data['id'] = $id;
                    $data['data'] = array('custom' => array());
                    $data['custom'] = array();
                    $data['price'] = floatval(str_replace(":", "", strstr($results->find(".tovar")->find('tr > td > b')->text(),":")));
                    $imgpath = str_replace(" ", "%20", $results->find(".tovar")->parent()->prev('td')->find('img')->attr('src'));
                    if($imgpath != "")
                        $data['image_path'] = $this->shopBaseUrl.$imgpath;
                    else
                        $data['image_path'] = FALSE;
                    $custom = $results->find(".tovar")->next('ul')->find('li');
                    foreach($custom as $el)
                    {
                        $data['custom'][]  = pq($el)->text();
                    }
                    $categ = $results->find(".crumbtrail");
                    $data['categ']  = explode("/",trim(pq($categ)->text(),"/"));
                    unset($data['categ'][0]);
                    $data['categ'] = array_values($data['categ']);
                    foreach($data['categ'] as $i => $cat)
                    {
                        $data['categ'][$i] = trim($cat);
                    }
                    $dta = $results->find("body > table > tr > td > table > tr > td > table > tr > td > p");
                    foreach($dta as $element)
                    {
                        $text = pq($element)->text();
                        if(stripos($text, ":"))
                        {
                            
                            $value = trim(str_replace(":", "", strstr($text,":")));
                            $column = trim(trim(str_replace($value, "", $text)),":");
                        }
                        else
                        {
                            $array = array();
                            $array = explode(" ",$text);
                            $column = $array[0];
                            unset($array[0]);
                            $value = implode(" ",$array);
                        }
                        switch($column)
                        {
                            case "Артикул" :
                                $data['data']['articul'] = $value;
                                break;
                            case "Цвет" :
                                $data['data']['color'] = $value;
                                break;
                            case "Материал" :
                                $data['data']['material'] = $value;
                                break;
                            case "Длина":
                                $data['data']['size'] = $value;
                                break;
                            case 'custom':
                                $data['data']['custom'][] = $text;
                                break;
                            default:
                                $data['data']['custom'][] = $text;
                                break;
                        }
    
                    }
                }
                else
                {
                    return FALSE;
                }
		$itemInfo = new ParserItem ();
		$itemInfo->url   	= $url;
                $itemInfo->name         = $data['title'];
		$itemInfo->price 	= $data['price'];
		$itemInfo->categ 	= $data['categ'];
		$itemInfo->id    	= $data['id'];
		$itemInfo->articul 	= (isset($data['data']['articul']))?$data['data']['articul']:$itemId;
		$itemInfo->colors   =   (isset($data['data']['color']))?$data['data']['color']:null;
		$itemInfo->material	= (isset($data['data']['material']))?$data['data']['material']:null;
		$itemInfo->sizes    = (isset($data['data']['size']))?$data['data']['size']:null;
		$itemInfo->bStock   = null;
                $first = (is_array($data['data']['custom']))?@implode("; ",$data['data']['custom']):" ";
                $second = (is_array($data['custom']))?@implode("; ",$data['custom']):" ";
                $itemInfo->descr = $first." ".$second;
                if($data['image_path'])
                {
                    $imgUrl = $data['image_path'];

                    $this->httpClient->getUrlBinary ($imgUrl);
                    if ($this->httpClient->getLastCtype () != 'image/jpeg')
                            $this->parseError("Content-type header not image/jpeg at url '$imgUrl'!");
                    $image = new ParserImage();
                    $image->url = $imgUrl;
                    $image->path = $this->httpClient->getLastCacheFile();
                    $image->type = 'jpeg';
                    $itemInfo->images[] = $image;
                }
		return $itemInfo;
	}
    
	public function loadItems ()
	{
        return null;
		$base = array ();
                $this->getSiteMap();
		foreach ($this->itemList as $collName => $items)
		{
                        $page = $this->httpClient->getUrlText($this->shopBaseUrl."/".$collName."/");
                        $page = phpQuery::newDocumentHTML($page, 'cp1251');
                        $collAdd = $page->find("td.td1:first > p")->text();
                        $collAdd = trim($collAdd);
			$collection = new ParserCollection();
			$collection->id   = $collName." ".$collAdd;
			$collection->url  = $this->shopBaseUrl."/".$collName;
			$collection->name = $collName;
			$collection->items = $this->getItems($items,$collName);
			$base[] = $collection;
		}
		return $this->saveItemsResult ($base);
	}

    
	public function loadPhysicalPoints ()
	{
		$base = array ();

		foreach ($this->shopList as $city)
		{
                    foreach($city as $shop)
                    {
                        $data = array();
                        $url = $this->shopBaseUrl.$shop;
			$page = $this->httpClient->getUrlText($url);
                        $results = phpQuery::newDocumentHTML($page, 'cp1251');
                        $data['city'] = $results->find(".crumbtrail")->find('a:last')->text();
                        $name = $results->find(".h_content > strong")->text();
                        $elements = $results->find("body > table > tr > td > table > tr > td > p");
                        foreach($elements as $element)
                        {
                            $text = pq($element)->text();
                            if(stristr($text, "Тел.:"))
                            {
                                $data['phone'] =  $text;
                            }
                            if(stristr($text, "Наш адрес"))
                            {
                                $data['address'] = trim(str_replace("Наш адрес", "", stristr($text,"Наш адрес")),":")." ".$name;
                            }
                            if(stristr($text, "Время работы"))
                            {
                                $data['timetable'] = trim(str_replace("Время работы", "", strstr($text, "Время работы")),":");
                            }
                        }
        		$phys = new ParserPhysical($data);
			$phys->id        = trim($shop,"/");
			$base[] = $phys;
                    }
		}
		return $this->savePhysicalResult ($base);
	}
}

