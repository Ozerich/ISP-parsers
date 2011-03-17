<?php

/* Подключаем класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';

/* Для сайта tervolina.ru нужно создать следующий класс.
 * Сюда записывается всё, что связано с парсингом сайта tervolina.ru.
 * НЕОБХОДИМО реализовать 2 функции: 
 * 		loadItems - парсинг товаров
 * 		loadPhysicalPoints - парсинг торговых точек
 */
class ISP_tervolina_ru extends ItemsSiteParser_Drakon
{
	protected $shopBaseUrl = 'http://tervolina.ru/'; // Адрес главной страницы сайта 
	
	function parseTervolinaGoodsPage ($url, $itemId)
	{
		$page = $this->httpClient->getUrlText ($url);

		$preg1 = "#<div class='sled'>(.+?)</div>#uis";
		preg_match_all ($preg1, $page, $regs, PREG_SET_ORDER);
		if ( ! isset ($regs[0][1]))
			$this->parseError("Can't find path at url '$url'!");
			
		$sled = $regs[0][1];
		
		$preg1 = '#<a[^>]*>(.+?)</a>#sui';
		preg_match_all ($preg1, $sled, $regs, PREG_SET_ORDER);
		if (count ($regs) != 6)
			$this->parseError("Bad regs count: " . count ($regs) . " at url '$url'");
		$path = array ($regs[3][1], $regs[4][1], $regs[5][1]);
		
		$preg2 = '#<td\s+class="tablev4-l">\s*(.+?)\s*</td>\s*<td>\s*(.+?)\s*</td>#sui';
		preg_match_all ($preg2, $page, $regs, PREG_SET_ORDER);
		$attributesSrc = $regs;
		
		$preg3 = '#<strong>Стоимость:</strong>\s*<span class="red">(.+?)(руб\.\s*)?</span>#sui';
		if ( ! preg_match ($preg3, $page, $regs))
			$this->parseError("Can't find price field at url '$url'!");
		$price = $regs[1];
			
		$attrs = array ();
		foreach ($attributesSrc as $a)
		{
			$a[1] = preg_replace ("/:$/u", "", $a[1]);
			$attrs[trim($a[1])] = trim($a[2]);
		}

		$itemInfo = new ParserItem ();
		$itemInfo->url   	= $url;
		$itemInfo->price 	= str_replace (",", ".", $price);
		$itemInfo->categ 	= $path;
		$itemInfo->id    	= $itemId;
		$itemInfo->articul 	= $attrs['Артикул'];
		$itemInfo->colors   = explode ("/", $attrs['Цвет']);
		$itemInfo->material	= $attrs['Материал'];
		$itemInfo->sizes    = preg_split ("/\s*,\s*/", trim ($attrs['Доступные размеры'])); 
		$itemInfo->bStock   = (isset ($attrs['Наличие в продаже']) and $attrs['Наличие в продаже'] == 'Да')
			? 1 : 0;  
		
		$pregImg = "#<img width='\d+' id='big_img' src='([^']+)'#sui";
		if ( ! preg_match ($pregImg, $page, $regs))
			$this->parseError("Can't find image at url '$url'!");
		$imgUrl = 'http://tervolina.ru' . $this->urlencode_partial($regs[1]);

		$this->httpClient->getUrlBinary ($imgUrl);
		if ($this->httpClient->getLastCtype () != 'image/jpeg')
			$this->parseError("Content-type header not image/jpeg at url '$imgUrl'!");
		$image = new ParserImage();
		$image->url = $imgUrl;
		$image->path = $this->httpClient->getLastCacheFile();
		$image->type = 'jpeg';
		$itemInfo->images[] = $image;
		return $itemInfo;
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		$this->httpClient->getUrlText ('http://tervolina.ru/', null, false);
		$this->httpClient->getUrlText ('http://tervolina.ru/mainpage.aspx', null, false);
		
		$sitemap = $this->httpClient->getUrlText ('http://tervolina.ru/webmap.aspx');
		$pregCollectionUrl = '#href="(/main/mainpage/collections/c/(\d+)\.aspx)">(.*?)</a>#sui';
		preg_match_all ($pregCollectionUrl, $sitemap, $collectionUrls, PREG_SET_ORDER);
		
		$pregGoods = '#<a\s+href="(/collections/goodsview/g/(\d+)\.aspx)"#sui';
		
		foreach ($collectionUrls as $colUrlInfo)
		{
			$url = "http://tervolina.ru" . $colUrlInfo[1];
			
			$items = array ();
			do
			{
				$page = $this->httpClient->getUrlText ($url);
				preg_match_all ($pregGoods, $page, $regsGoods, PREG_SET_ORDER);
				if (empty ($regsGoods))
					break;

				foreach ($regsGoods as $r)
				{
					$urlGoodsPage = 'http://tervolina.ru' . $r[1];
					$itemInfo = $this->parseTervolinaGoodsPage ($urlGoodsPage, $r[2]);
					if ($itemInfo === false)
					{
						$this->parseError ("Can't parse goods page '$urlGoodsPage'");
						return;
					}
					$items[] = $itemInfo;
				}
				
			} while (preg_match ("#<a href='(/collections/c/\d+/p/\d+\.aspx)'>»</a>#sui", $page, $regsPage)
				and $url = 'http://tervolina.ru' . $regsPage[1]);
				
			if (empty ($items))
				continue;
				
			$collectionName = trim($items[0]->categ[0]) . ' ' . mb_strtolower(trim($items[0]->categ[1]));

			if (isset ($base[$collectionName]))
			{
				foreach ($items as $item)
					$base[$collectionName]->items[] = $item;
			}
			else
			{
				$collection = new ParserCollection();
				$collection->id   = $colUrlInfo[2];
				$collection->url  = $url;
				$collection->name = /*$colUrlInfo[3]*/$collectionName;
				$collection->items = $items;
				$base[$collectionName] = $collection;
			}
		}
		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$url = 'http://tervolina.ru/russiacity.ashx?83';
		$russiaShops = $this->httpClient->getUrlText ($url);
		if (strpos ($this->httpClient->getLastCtype(), 'text/xml') !== 0)
			$this->parseError("Received not xml content-type at url '$url'!");
		$xml = @ simplexml_load_string($russiaShops);
		if ($xml === false)
			$this->parseError("Can't load xml at url '$url'!");
			
		$cities = $xml->xpath ('/root_map_project/cities/city');
		if ($cities === false)
			$this->parseError ("No russia offices found at url '$url'!");
			
		$cityShopsPreg = "#<div class='(r|l)'>\s*<p><a href='/showroom/id/(\d+)\.aspx' " 
			. "target='_blank'>(.+?)</a></p>\s*<p>(.*?)</p>\s*<p>(.*?)</p>\s*" 
			. "<p>(.*?)</p>\s*</div>#sui";
			
		foreach ($cities as $city)
		{
			$attrs = $city->attributes();
			$cityId = (string) $attrs->id; 
			$cityName = (string) $attrs->name_ru;
			$url = "http://tervolina.ru/russia/t/$cityId.aspx";

			$cityShopsData = $this->httpClient->getUrlText ($url);
			preg_match_all($cityShopsPreg, $cityShopsData, $physPoints, PREG_SET_ORDER);
						
			foreach ($physPoints as $physInfo)
			{
				$phys = new ParserPhysical();
				$phys->id 		 = $physInfo[2];
				$phys->city 	 = $cityName;
				$phys->address   = $physInfo[4];
				
				if (trim ($physInfo[5]) != 'Тел.:')
					$phys->phone     = preg_replace ("/тел\.:/ui", "", $physInfo[5]);
				$phys->timetable = preg_replace ("/График работы:/ui", "", $physInfo[6]);
				$base[] = $phys;
			}
		}
		
		$url = 'http://tervolina.ru/img/data.xml?8647';
		
		$moscowShops = $this->httpClient->getUrlBinary ($url);
		
		if (strpos ($this->httpClient->getLastCtype(), 'text/xml') !== 0)
			$this->parseError("Received not xml content-type at url '$url'!");
		$xml = @ simplexml_load_string($moscowShops);
		if ($xml === false)
			$this->parseError("Can't load xml at url '$url'!");

		$offices = $xml->xpath ('/root_map_project/officies/office');
		if ($offices === false)
			$this->parseError ("No moscow offices found at url '$url'!");
		foreach ($offices as $office)
		{
			$attrs = $office->attributes();
			
			$phys = new ParserPhysical();
			$phys->id 		 = (string) $attrs->id;
			$phys->city      = 'Москва';
			$phys->address   = (string) $attrs->addr;
			$phys->phone     = (string) $attrs->tel;
			$phys->timetable = (string) $attrs->worktime;
			
			$base[] = $phys;
		}
		
		return $this->savePhysicalResult ($base);
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$baseUrl = 'http://tervolina.ru/news.aspx';
		$news = $this->httpClient->getUrlText ($baseUrl);
		
		$pregNews = '#<td\s+class="t_m43-r">(.+?)</td>#sui';
		preg_match_all ($pregNews, $news, $newsResult, PREG_SET_ORDER);
		$pregParseBlock = '#<p>\s*<p>(.+?)</p>\s*<a\s+href="([^"]+)">(.+?)</a>#sui';
		$pregParseUrl   = '#/(\d+)\.aspx$#ui';
		$pregDate       = '#<p\s+class="date">\s*([\d\.]+)\s*</p>#sui';
		
		$pregFullText   = '#<p\s+class="date">.+?</p>\s*(<p>.*?</p>\s*)?<p>(.+?)</p>#sui';
		
		foreach ($newsResult as $block)
		{
			if ( ! preg_match ($pregParseBlock, $block[1], $blockParsed))
				$this->parseError ("Can't parse news block!");
				
			if ( ! preg_match ($pregDate, $block[1], $dateParsed))
				$this->parseError ("Can't find date!");

			$url = 'http://tervolina.ru' . $blockParsed[2];
				
			$base[] = $newsElem = new ParserNews();
			if ( ! preg_match ($pregParseUrl, $url, $parsedUrl))
				$this->parseError ("Can't find id in url '$url'!");
				
			$newsElem->id           = $parsedUrl[1];
			$newsElem->date         = $dateParsed[1];
			$newsElem->contentShort = $blockParsed[1];
			$newsElem->urlShort     = $baseUrl;
			$newsElem->urlFull      = $url;
			
			$contentFull = $this->httpClient->getUrlText ($url);
			if ( ! preg_match ($pregFullText, $contentFull, $parsedFull))
			{
				$this->parseError ("Can't parse full news text at url '$url'!");
			}
			$newsElem->contentFull = $parsedFull[2];
		}
		
		return $this->saveNewsResult ($base);	
	}
}
