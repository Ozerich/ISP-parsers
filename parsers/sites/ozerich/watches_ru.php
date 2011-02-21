<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_watches_ru extends ItemsSiteParser_ozerich
{
	protected $shopBaseUrl = 'http://www.watches.ru/'; // Адрес главной страницы сайта
        protected $delay = 1;

        public function loadItems ()
        {
            $base = array ();
            $targetString = $this->httpClient->getUrlText ($this->shopBaseUrl);
            $trRX = '~<tr bgcolor="[^"]+".*?>(.*?)</tr>~is';
            $collectionName = '';
            if (preg_match_all($trRX, $targetString, $trs)) {
                foreach ($trs[1] as $i=>$tr) {
                    if (preg_match('~<h2>(.*?)</h2>~is',$tr, $collection)) {
                        $collectionName = $collection[1];
                    } else {
                        $categoryRX = '~<a href="/([^"]+)".*?class="ref5".*?>(.*?)</a>~is';
                        if (preg_match($categoryRX, $tr, $category)) {
                            $curUrl = $this->shopBaseUrl . $category[1];
                            $catName = $category[2];
                            $base[] = $this->_parseCollection($curUrl, $collectionName, $catName); 
                            
                        }
                        
                       
                        
                    }
                }
            } else {
                $this->parseError('Can not parse collections');
            }
            $base = $this->_fixCollections($base);
            return $this->saveItemsResult ($base);
	}

        protected function _fixCollections($base)
        {
            $lastCollection = '';
            foreach ($base as $i=>$collectionObj) {
                if ($lastCollection == $collectionObj->name) {
                    $collection = $base[$collectionIndex];
                    $collection->items = array_merge($collection->items, $collectionObj->items);
                    unset($base[$i]);
                } else {
                    $collectionIndex = $i;
                    $lastCollection = $collectionObj->name;
                }
                
            }
            return $base;
        }

        protected function _parseCollection($url, $name, $catName)
        {
            $targetString = $this->httpClient->getUrlText($url);
            $totalRX = '~<font class="text15">(\d+)</font>~is';
            if (preg_match($totalRX,$targetString, $total)) {
                $totalModels = (int)$total[1];
                $totalPages = ceil($totalModels/20);

            } else {
                echo $targetString;
                $this->parseError("Can't parse total");
            }
            if (preg_match('~<a.*?class="ref10">(.*?)</a>~', $targetString, $match)) {
                $collection = new ParserCollection();
                list($chunk, $mark, $rasp) = explode('&', $url);
                $collection->id = $mark;
                $collection->name = $name;
               // $collection->url = $url;
                $collection->descr = strip_tags($match[1]);
                $items = array();
                for ($j=0; $j < $totalPages; $j++) {
                    if ($j) {
                        $p = $j+1;
                        $curUrl = $url .'&m_sp=' . $p;
                       // sleep($this->delay);//ЧТобы не лег сервак
                       usleep(200000);
                        $targetString = $this->httpClient->getUrlText($curUrl);
                    }
                    $itemsArr = $this->_parseItems($targetString, $catName);
                    $items = array_merge($items, $itemsArr);
                }
                $collection->items = $items;
                return $collection;

            } else {
                $this->parseError("Can't parse collection description at url:$url" );
            }

        }

        protected function _parseItems($string, $catName)
        {
            $items = array();
            if (!preg_match('~<!-- begin content -->(.*?)<!-- end content -->~is', $string, $res)){
                $this->parseError('Can not find main content');
            }
            $string = $res[1];
            if (preg_match_all('~<a href="/index\.php\?page=(\d+)&mod=(\d+)">\s*<img.*?src="([^"]+)"~is', $string, $watches)) {
                $pages = $watches[1];
                $ids = $watches[2];
                $imgs = $watches[3];
                foreach ($ids as $i=>$id) {
                    $images = array();
                    $url = $this->shopBaseUrl . "index.php?page={$pages[$i]}&mod={$ids[$i]}";
                   /* $imgUrl = $this->shopBaseUrl . ltrim($imgs[$i], '/');
                    $this->httpClient->getUrlBinary ($imgUrl);
//                    if ($this->httpClient->getLastCtype () != 'image/jpeg')
//                            $this->parseError("Content-type header not image/jpeg at url '$imgUrl'!");
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
                    $image->id = trim(str_replace('picsfrontsmall.php?id=','', $imgs[$i]), '/');
                    $images[] = $image;*/
                    $itemInfo = new ParserItem ();
		    $itemInfo->url   	= $url;
		    $itemInfo->id    	= $ids[$i];
                    $itemInfo->images = $images;
                    $this->_getItemInfo($url, $itemInfo, $catName);
                    $items[] = $itemInfo;
                }
                return $items;
            } else {
                $this->parseError('Can not parse product description');
            }
        }

        protected function _getItemInfo($url, &$itemInfo, $category)
        {
            $targetString = $this->httpClient->getUrlText($url);
            if (preg_match('~<h1>(.*?)</h1>~is', $targetString, $m)) {
                $itemInfo->name = $m[1];
            } else {
                $this->parseError('Can not parse product Name, url=' .$url);
            }
            if (preg_match_all('~<TD ALIGN="LEFT" VALIGN="TOP" CLASS="text\d+">(.*?)</TD><TD ALIGN="LEFT" VALIGN="TOP" CLASS="text\d+">(.*?)</TD>\s*</TR>~i', $targetString, $match)) {
                $properties = $match[1];
                $values = $match[2];
                $vars = array_combine($properties, $values);
                if (isset ($vars['Цена']))
                	$itemInfo->price 	= str_replace(' руб.', '', $vars['Цена']);//preg_replace('~(\d+).*?~', '$1', $vars['Цена']);
                if (isset ($vars['Скидка']))
                {
                    $itemInfo->discount 	= str_replace(' %', '', $vars['Скидка']);
                    $itemInfo->price 	= str_replace(' руб.', '', $vars['Цена']);
                }

                $itemInfo->categ 	= $category;
                $itemInfo->articul 	= isset($vars['Модель'])? $vars['Модель'] : '';
                $itemInfo->name = str_replace(' '. $vars['Модель'], '', $itemInfo->name);
            	$itemInfo->colors   = isset($vars['Цвет циферблата'])? $vars['Цвет циферблата'] : '' ;
            	$itemInfo->material	= isset($vars['Корпус'])? $vars['Корпус'] : '';
                $itemInfo->sizes    = "";
                $itemInfo->bStock   = (isset ($vars['Наличие']) and (preg_match('~есть~i', $vars['Наличие'])))
			? 1 : 0;
                $itemInfo->made_in = $vars['Страна-производитель'];
                unset($vars['Цена']);        
                unset($vars['Модель']);
                unset($vars['Страна-производитель']);
                unset($vars['Цвет циферблата']);
                unset($vars['Корпус']);
                unset($vars['Наличие']);
                $desc = array_values($vars);
                $itemInfo->descr = strip_tags(implode(', ', $desc));
                $imgRX = '~(picsfront\.php\?id=\d+)"~is';
                if (preg_match($imgRX, $targetString, $img)) {
                    $imgUrl = $this->shopBaseUrl . ltrim($img[1], '/');
                    $this->httpClient->getUrlBinary ($imgUrl);
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
                    $image->id = str_replace('picsfront.php?id=','', $img[1]);
                    array_push($itemInfo->images, $image);
                } else {
                    $this->parseError('Can not parse big photo');
                }
            } else {
                $this->parseError('Can not parse product full info');
            }
         }

	public function loadPhysicalPoints ()
	{
		$base = array ();
		$url = 'http://www.watches.ru/index.php?page=3';
		$russiaShops = $this->httpClient->getUrlText($url);
                if (preg_match_all('~<table border="0" width="100%" cellspacing="5" cellpadding="0">(.*?)</table>~is', $russiaShops, $sh)) {
                $moscowStr = $sh[1][0];
                $regionStr = $sh[1][1];
//                die(print_r($moscowStr));
                if (preg_match_all('~<div class="vcard">\s*<div>\s*<a class="fn org url ref15" href="http://www.watches.ru/index.php\?page=3&sh=(\d+)">(.*?)</a>\s*</div>\s*<div class="adr text9">.*?<span class="locality text11">(.*?)</span>,\s*<span class="street-address text11">(.*?)</span>\s*</div>\s*<div class="tel text9">.*?<abbr class="value text11" title="([^"]+)">.*?</abbr></div><div class="text9">.*?<span class="workhours text11">(.*?)</span>~is', $moscowStr, $m)) {
                    foreach ($m[1] as $i=>$shopId) {
                        $phys = new ParserPhysical();
                        $phys->id = $m[1][$i];
                        $phys->city = $m[3][$i];
                        $phys->address = $m[4][$i];
                        $phys->phone     = $m[5][$i];
                        $phys->timetable = $m[6][$i];
                        $phys->b_closed = 0;
                        $phys->b_stock = 0;
                        $base[] = $phys;
                    }
                }else {
                    $this->parseError('Can not parse  Moscow points');
                }
                if (preg_match_all('~<div class="vcard">\s*<div>\s*<a class="fn org url ref15" href="http://www.watches.ru/index.php\?page=3&sh=(\d+)">(.*?)</a>\s*</div>\s*<div class="adr text9">.*?<span class="locality text11">(.*?)</span>,\s*<span class="street-address text11">(.*?)</span>\s*</div>\s*<div class="tel text9">.*?<abbr class="value text11" title="([^"]+)">.*?</abbr></div><div class="text9">.*?<span class="workhours text11">(.*?)</span>~is', $regionStr, $m)) {
                    foreach ($m[1] as $i=>$shopId) {
                        $phys = new ParserPhysical();
                        $phys->id = $m[1][$i];
                        $phys->city = $m[3][$i];
                        $phys->address = $m[4][$i];
                        $phys->phone     = $m[5][$i];
                        $phys->timetable = $m[6][$i];
                        $phys->b_closed = 0;
                        $phys->b_stock = 0;
                        $base[] = $phys;
                    }
                }else {
                    $this->parseError('Can not parse  region points');
                }
//                $this->_d($base);
		return $this->savePhysicalResult($base);
                } else {
                     $this->parseError('Can not parse physical points step 1');
                }
	}

    public function loadNews ()
	{
		$base = array ();
		$baseUrl = 'http://www.watches.ru/index.php?page=12';
		$news = $this->httpClient->getUrlText ($baseUrl);

		$pregNews = "~<p class='news_on_main_page'><span>(\d{2}\.\d{2}\.\d{4})</span><br/><a href='(/index\.php\?page=\d+&news=(\d+))'\s+class='lmenu1'>(.*?)</a><br></p>~si";
		preg_match_all ($pregNews, $this->txt($news), $newsResult);
        if (!$newsResult) {
            $this->parseError("Can't parse news list");
        }
		$dates = $newsResult[1];
        $hrefs = $newsResult[2];
        $ids = $newsResult[3];
        $titles = $newsResult[4];
		foreach ($hrefs as $i=>$href) {

			$base[] = $newsElem = new ParserNews();
            $url = 'http://www.watches.ru' . $href;
            usleep(200000);
            $contentFull = $this->httpClient->getUrlText ($url);
            
			$newsElem->id           = $ids[$i];
			$newsElem->date         = $dates[$i];
			//$newsElem->contentShort = $blockParsed[1];
			$newsElem->urlShort     = $baseUrl;
			$newsElem->urlFull      = $url;
            $newsElem->header       = $titles[$i];
            $pregFullText = '~<!-- begin content -->(.*?)<!-- end content -->~is';
			if ( ! preg_match ($pregFullText, $this->trimTags($contentFull), $parsedFull)){
				$this->parseError ("Can't parse full news text at url '$url'!");
			}
            $text = str_replace('<tr><td align="right" class="text8"><a href="/index.php?page=12"><nobr>Вернуться к списку новостей</nobr></a></td></tr>', '', $parsedFull[1]);
            $text = str_replace('<TR HEIGHT="20"><TD CLASS="text13"><IMG SRC="img/spacer.gif" WIDTH="7" HEIGHT="1"><a href="/" class="text13">Главная</a> \ Новости</TD></TR>', '', $text);
			$newsElem->contentFull = $text;
		}

		return $this->saveNewsResult ($base);
	}
}

