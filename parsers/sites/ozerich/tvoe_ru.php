<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';

/* Для сайта tvoe.ru нужно создать следующий класс.
 * Сюда записывается всё, что связано с парсингом сайта tvoe.ru.
 * НЕОБХОДИМО реализовать 2 функции:
 * 		loadItems - парсинг товаров
 * 		loadPhysicalPoints - парсинг торговых точек
 */
class ISP_tvoe_ru extends ItemsSiteParser_Drakon
{
	var $baseShops = array();
	var $baseItems = array();

	protected $shopBaseUrl = 'http://tvoe.ru/'; // Адрес главной страницы сайта

	function loadItem($urlId,$nameCateg,$urlItem)
    {
    	$url = explode("page",$urlId);

    	$itemId = $url[0] . $urlItem;
    	$itemId = explode(".html",$itemId);
    	$itemId = $itemId[0];

    	$url = 'http://tvoe.ru/collection/' . $url[0] . $urlItem;
		//$url = "http://tvoe.ru/collection/woman/ny/koketka.html?3958#recomend";
		$itemPage = $this->httpClient->getUrlText ($url);
		
		$text = $itemPage;

	    $itemPage = explode("<dt id=\"recomend\">",$itemPage);

	    $name = explode("</dt>",$itemPage[1]);
	    $itemName = strip_tags(html_entity_decode($name[0]));

	    $descr = explode("<dd>",$name[1]);
	    $descr = explode("<p>",$descr[1]);
		$descr = explode("</p>",$descr[1]);
		
		preg_match('#<p id="share_info">(.+?)</p>#sui', $text, $descr);



		if (substr_count($descr[1],"<br>") >= 1) {
			$descr = explode("br>",$descr[1]);
			$material = $descr[1];
			$descr = $descr[0];
		}
		else
		{
			$descr = $descr[0];
		}

		preg_match('#<div id="share_price"><span>(.+?) р.</span></div>#sui', $text, $price);
		if($price)
			$itemPrice = $price[1];
		

		$sizes = array(); // Размеры
		$articul = ""; // Артикул
		$colors = array(); // Цвет

		//var_dump ($price);
		//echo "<br><br>";
		
		preg_match('#<p><span id="share_mark">Артикул: (.+?)</span></p>#sui', $text, $articul);
		if($articul)$articul = $articul[1];

		preg_match('#<p>Размеры: (.+?)</p>#sui', $text, $sizes);
		if($sizes)$sizes = explode('|',$sizes[1]);
		
		preg_match('# <p>Цвета: (.+?)(</p>|<br>)#sui', $text, $colors);
		

		if($colors)
		{
			$colors_ = explode('|',$colors[1]);
			$colors = array();
			foreach($colors_ as $color)
				$colors[] = $color;
		}
	

		$image = explode("<span class=\"img\"><img src=\"",$itemPage[1]);
		$imageUrl = explode("\"",$image[1]);
		$imageId = explode("full_",$imageUrl[0]);
		$imageId = explode(".",$imageId[1]);
		$imageId = $imageId[0];
		$imageUrl = 'http://tvoe.ru'. $imageUrl[0];

		$this->httpClient->getUrlBinary ($imageUrl);
		if ($this->httpClient->getLastCtype () != 'image/jpeg')
			$this->parseError("Content-type header not image/jpeg at url '$imageUrl'!");
   		$image = new ParserImage();
		$image->url = $imageUrl;
		$image->path = $this->httpClient->getLastCacheFile();
		$image->type = 'jpeg';
		$image->id = $imageId;

		$itemInfo = new ParserItem ();
		$itemInfo->url   	= $url;
		$itemInfo->price 	= $itemPrice;
		$itemInfo->name 	= $itemName;
		$itemInfo->descr 	= strip_tags(html_entity_decode($descr));

		if (substr_count($url,"woman") >= 1)
		{
			$categ = array('Женская коллекция',$nameCateg);
		}
		else
		{
			$categ = array('Мужская коллекция',$nameCateg);
		}

		$itemInfo->categ 	= $categ;
		$itemInfo->id    	= $itemId;
		$itemInfo->articul 	= $articul;
		$itemInfo->colors   = $colors;
		$itemInfo->structure	= $material;
		$itemInfo->sizes    = $sizes;
		$itemInfo->images[] = $image;
        //return $itemInfo;
		
		//print_r($itemInfo);exit();
        $this->baseItems[] = $itemInfo;
    }

	function loadListItem($urlId,$nameCateg)
	{
		$url = 'http://tvoe.ru/collection/' . $urlId;
		$allItems = $this->httpClient->getUrlText ($url);

        $parseUrlItem = explode("<dt><a href=\"",$allItems);
		foreach($parseUrlItem as $value)
		{
			$urlItem = explode("\" title",$value);
			if (strlen($urlItem[0]) < 100) {
					$this->loadItem($urlId,$nameCateg,$urlItem[0]);
				}
		}
	}

	function loadMainPageListItem($urlId,$nameCateg)
	{
		$url = 'http://tvoe.ru/collection/' . $urlId;
		$allPages = $this->httpClient->getUrlText ($url);

		$this->loadListItem($urlId."page1.html",$nameCateg);

		preg_match('#<p>1\s\|\s(.+?)</p>#sui', $allPages, $pages);

		if($pages)
		{
			$pages = substr_count($pages[1], "html");
			$pages++;
			while($pages > 1)
			{
				$this->loadListItem($urlId."page".$pages.".html",$nameCateg);
				$pages--;
			}
		}
	}


	public function loadItems ()
	{
		$base = array();

		$url = 'http://tvoe.ru/collection/';
		$allCategs = $this->httpClient->getUrlText ($url);

		$parseAllCategs = explode("<ul class=\"list\">", $allCategs);

		$parseLeftListCategs = explode("<div id=\"right\">", $parseAllCategs[2]);

		$parseAllCategs = $parseAllCategs[1] . $parseLeftListCategs[0];



		$parseAllCategs = explode("href=\"", $parseAllCategs);

		foreach($parseAllCategs as $value)
		{

			if (substr_count($value,"/\">") >= 1)
			{
				$parseCateg = explode("\">",$value);

				$urlId = $parseCateg[0];
				if (substr_count($urlId,"propose") >= 1) { continue; } // Удаляем предложение недели
				$nameCateg = explode("</a>",$parseCateg[1]);
				$nameCateg = $nameCateg[0];

				$this->loadMainPageListItem($urlId,$nameCateg);
			}
		}

		$url = 'http://tvoe.ru/collection/woman/propose/'; // Ссылка для получения src коллекции
		$allCategs = $this->httpClient->getUrlText ($url);
		$idCollection = explode("<div class=\"title\"><img src=\"/",$allCategs);
		$idCollection = explode("\"",$idCollection[1]);
		$idCollection = $idCollection[0];


		$collection = new ParserCollection();
		$collection->id   = $idCollection;
		$collection->url  = $url;
		$collection->items = $this->baseItems;
		$base[] = $collection;

		return $this->saveItemsResult ($base);

	}



	function loadPhysicalPoint($cityName, $numberRegion)
	{
		$url = 'http://tvoe.ru/shops/region'. $numberRegion .'.html';
		//$this->httpClient->setDefaultSrcCharset ('windows-1251');
		$allShopsText = $this->httpClient->getUrlText ($url);
		//$allShopsText = iconv("windows-1251", "utf-8//IGNORE", $allShopsText);

		$allShopsText = explode("<div class=\"row\">", $allShopsText);
        unset($allShopsText[0]);

        $allShops = array();

		foreach($allShopsText as $value)
		{
			$allShopsValue = explode("<dl id=\"shop_", $value);
			foreach($allShopsValue as $valueOne)
			{
				if (substr_count($valueOne,"id=\"bubble_") == 1)
				{
					array_push($allShops,$valueOne);
				}
			}
		}

		//echo print_r($allShops)."<br>";

		foreach($allShops as $value)
		{
				$shopID = explode("\"", $value);
		        $shopID = $shopID[0];

				$value_address = explode("<dt>", $value);
				$value_address = explode("</dt>", $value_address[1]);
				$address = strip_tags(html_entity_decode($value_address[0])); /* Удаляем теги и декодируем символы типа &quot; */

				$value_timetable = explode("<dd>", $value);
				$value_timetable = explode("<p>Часы работы:", $value_timetable[1]);
				$value_timetable = explode("</p>", $value_timetable[1]); // [0]
				$timetable = strip_tags(html_entity_decode($value_timetable[0]));

				$telephone = ""; // Телефон магазина

    			if (!empty($value_telephone[1])) {
					$value_telephone = explode("<p>Телефон:", $value_timetable[1]);
					if (strlen($value_telephone[1]) < 200) { // Проверка, для ситуаций когда нету телефона
						$telephone = strip_tags(html_entity_decode($value_telephone[1]));
					}
				}

				$phys = new ParserPhysical();
				$phys->id 		 = (string) $shopID;
				$phys->city      = (string) $cityName;
				$phys->address   = (string) $address;
				$phys->phone     = (string) $telephone;
				$phys->timetable = (string) $timetable;

				$this->baseShops[] = $phys;

            /*
			if (substr_count($value, "photo".$shopID."html") == 1)
			{
				$url_image_page = "http://tvoe.ru/shops/photo".$shopID.".html";
				$imagePage = $this->httpClient->getUrlText ($url_image_page);

				$imageUrl = explode("class=\"photo\"><img src=\"",$imagePage);
				$imageUrl = explode("\"",$imagePage[0]);

				if (substr_count($imagePage[1],"href=") > 1) {
						$imageUrl = array();

					}

				$imageUrl = $imageUrl[0];

    			substr_count
			}
            */

		}
	}

	public function loadPhysicalPoints ()
	{
		$url = 'http://tvoe.ru/shops/all.html';
		//$this->httpClient->setDefaultSrcCharset ('windows-1251');
		$allShops = $this->httpClient->getUrlText ($url);

		$parseRegions = explode("<div id=\"new_googlemaps\"", $allShops);
		$parseRegions = $parseRegions[1];

		$parseRegion = explode("region", $parseRegions);

		foreach($parseRegion as $value)
		{
			$numberRegion = explode(".html\">",$value);
			if (count($numberRegion)>1) {
					$cityName = $numberRegion[1];
					$numberRegion = $numberRegion[0];
					$cityName = explode("</a></li>",$cityName);
					$cityName = $cityName[0];
					if (substr_count($cityName,'Дисконт') != 1) {
						$this->loadPhysicalPoint($cityName, $numberRegion);
					}
				}
		}

		return $this->savePhysicalResult ($this->baseShops);
	}

	public function loadNews()
	{
		$base = array();

		$url = "http://tvoe.ru/news/";
		$parserNews = $this->httpClient->getUrlText ($url);

		$parserNews = explode("<dt><span>",$parserNews);
		unset($parserNews[0]);

		foreach($parserNews as $value)
		{

			$parserBlockNew = explode("</a></p></dd>",$value);
			$parserBlockNew = explode("<p><a href=\"",$parserBlockNew[0]);

			$urlShort = $parserBlockNew[1];
			$parserBlockNew = $parserBlockNew[0];

			$urlShort = explode("\">",$urlShort);
			$urlShort = "http://tvoe.ru/news/" . $urlShort[0];

			$idNews = explode("detail",$urlShort);
			$idNews = explode(".html",$idNews[1]);
			$idNews = $idNews[0];

			$contentShort = explode("</span></dt>",$parserBlockNew);
			$contentShort = $contentShort[0].$contentShort[1];

			$parserFullDsr = $this->httpClient->getUrlText ($urlShort);

			$parserFullDsr = explode("<dd>",$parserFullDsr);
			$parserFullDsr = explode("</dd>",$parserFullDsr[1]);
			$parserFullDsr = $parserFullDsr[0];



			$base[] = $newsElem = new ParserNews();

			$newsElem->id           = $idNews;
			$newsElem->contentShort = $contentShort;
			$newsElem->contentFull  = $parserFullDsr;
			$newsElem->urlFull      = $urlShort;
			$newsElem->urlShort     = $url;
		}

		return $this->saveNewsResult ($base);
	}


}