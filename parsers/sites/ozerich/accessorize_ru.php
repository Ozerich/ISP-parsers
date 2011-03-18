<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';
require_once PARSERS_BASE_DIR . '/parsers/addons/phpQuery.php';

/* Для сайта accessorize.ru нужно создать следующий класс.
 * Сюда записывается всё, что связано с парсингом сайта tervolina.ru.
 * НЕОБХОДИМО реализовать 2 функции:
 * 		loadItems - парсинг товаров
 * 		loadPhysicalPoints - парсинг торговых точек
 */
class ISP_accessorize_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://accessorize.ru/'; // Адрес главной страницы сайта
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

        function  __construct($savePath)
        {
            parent::__construct($savePath);
            $this->httpClient->setConfig (array('curloptions' => array (CURLOPT_TIMEOUT => 600)));
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
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."monsoon/address/");
        preg_match('#<td style="padding-left:10px">(.+?)</table>#sui', $text, $text);
        preg_match_all('#<a href="/(.+?)/"><img src=".+?" border="0" alt="(.+?)"></a>#sui', $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[1];
            $collection->name = $collection_value[2];
            $collection->url = $this->shopBaseUrl.$collection_value[1].'/';
            
            $text = $this->httpClient->getUrlText($collection->url);
            preg_match('#<table cellspacing="0" cellpadding="0" border="0" width="100%" class="left_menu">(.+?)</table>#sui', $text, $text);
            preg_match_all('#href="/(.+?)"><div style="margin-right:20px">(.+?)</div>#sui', $text[1], $categories, PREG_SET_ORDER);
            foreach($categories as $category)
            {
                $category_name = $this->txt($category[2]);
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]);
                
                preg_match_all('#<td width="1%"><a href="/(.+?)">#sui', $text, $items);
                foreach($items[1] as $item)
                {
                    $id = mb_substr($item, 0, -1);
                    $item = $this->parseTervolinaGoodsPage($this->shopBaseUrl.$item,mb_substr($id, mb_strrpos($id,'/') + 1),1);
                    if($item)
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

        $text = $this->httpClient->getUrlText($this->shopBaseUrl.'monsoon/address/');
        preg_match_all('#<td class="h_content"><strong>Вы выбрали город (.+?)</strong></td>(.+?)</table>\s*</div>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $this->txt($city[1]);
            $text = $city[2];
            preg_match_all('#<tr.+?>(.+?)</tr>#sui', $text, $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();
                $shop->city = $city_name;

                preg_match_all('#<td.+?>(.+?)</td>#sui', $text, $info);
                $info = $info[1];
                $shop->address = $this->txt($info[2]).", ".$this->txt($info[1]);
                $shop->phone = $this->txt($info[3]);

                $shop->address = $this->address($shop->address);
                if($shop->phone == "СКОРО ОТКРЫТИЕ")
                    continue;
                
                preg_match('#г\.(.+?),#sui', $shop->address, $city_preg);
                if($city_preg)
                {
                    $shop->city = $this->txt($city_preg[1]);
                    $shop->address = str_replace($city_preg[0],'',$shop->address);
                }

                if(mb_substr($shop->phone, 0, 2) == 'т.')
                    $shop->phone = mb_substr($shop->phone, 3);

                if($this->address_have_prefix($shop->address))
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ',') + 2);
                
                $shop->address = str_replace('С-Петербург, ','',$shop->address);
                if($shop->address == 'Галерея')
                    continue;

                $base[] = $shop;
            }
        }
        
        return $this->savePhysicalResult ($base);
	}
}

