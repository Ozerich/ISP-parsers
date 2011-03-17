<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';
require_once PARSERS_BASE_DIR . '/parsers/addons/phpQuery.php';


/* Для сайта f5jeans.ru нужно создать следующий класс.
 * Сюда записывается всё, что связано с парсингом сайта f5jeans.ru.
 * 		loadItems - парсинг коллекции
 * 		parseF5jeansGoodsPage - парсинг товара
 * 		loadPhysicalPoints - парсинг торговых точек
 * 		loadNews - парсинг торговых точек
 */
class ISP_f5jeans_ru extends ItemsSiteParser_Drakon
{
	protected $shopBaseUrl = 'http://f5jeans.ru'; // Адрес главной страницы сайта 
	protected $catalogUrl = 'http://f5jeans.ru/catalog2010/';
	
	function parseF5jeansGoodsPage ($url, $itemId, $catalogUrl)
	{
		$contentItem = $this->httpClient->getUrlText ($url);
		$contentItem = str_replace("&nbsp;","",$contentItem);
		$documentItem=phpQuery::newDocument($contentItem);
	
		//категории / иерархический путь
		$coll=$documentItem->find('.l-nav-items > h1 > span:eq(0)');
		if ($coll == "") $this->parseError ("Can't parse name of collection\n");
		$collSub=$documentItem->find('.l-nav-items > h1 > span:eq(1)');
		if ($collSub == "") $this->parseError ("Can't parse name of subcollection\n");
		$path = array (pq($coll)->text(), pq($collSub)->text());
		
		$divGrid_10=$documentItem->find('.grid_10 > .bg-grey2');
		if ($divGrid_10 == "") $this->parseError ("Can't parse el with class='bg-grey2'\n");
		
		//Изображение
		$imgDiv=pq($divGrid_10)->find('.l-photo-det');
		$imagepath=pq($imgDiv)->find('a > img')->Attr('src');
		if ($imagepath == "") $this->parseError ("Can't parse image\n");
		$imgUrl=$catalogUrl . $imagepath;
								
		pq($divGrid_10)->find('.l-photo-det')->remove();
					
		//артикул
		$art=pq($divGrid_10)->find('div:first > p:eq(0)')->text();
		if ($imagepath == "") $this->parseError ("Can't parse articul\n");
		//категория
		$name=mb_strtolower(pq($divGrid_10)->find('div:first > p:eq(1)')->text());
		if ($name == "") $this->parseError ("Can't parse name\n");
		//$name=mb_convert_encoding($name, 'UTF-8', 'CP1251');
		//ID
		$id=pq($divGrid_10)->find('div:first > p:eq(2)');
		if ($id == "") $this->parseError ("Can't parse ID\n");
		pq($id)->find('span')->remove();
		$id=pq($id)->text();
		//цвет
		$color=pq($divGrid_10)->find('div:first > p:eq(3)');
		pq($color)->find('span')->remove();
		$color=pq($color)->text();
		//ткань
		$material=pq($divGrid_10)->find('div:first > p:eq(4)');
		pq($material)->find('span')->remove();
		$material=pq($material)->text();
		//состав
		$structure=pq($divGrid_10)->find('div:first > p:eq(5)');
		pq($structure)->find('span')->remove();
		$structure=pq($structure)->text();
		//размеры
		//$sizes=pq($divGrid_10)->find('div:first > .setka');
		$sizesTrFirstTd=pq($divGrid_10)->find('div:first > .setka > tr:eq(0) > td:gt(0)');
		
		$hor=array();
		foreach ($sizesTrFirstTd as $td)
		{
			$hor[]=pq($td)->text();
		}	
		$countHor=count($hor);
		$sizesTrOthers=pq($divGrid_10)->find('div:first > .setka > tr:gt(0)');		
		$vert=array();
		foreach ($sizesTrOthers as $tr)
		{
			$v=trim(pq($tr)->find('td:eq(0)')->text());
			if ($v!="") $vert[]=$v;
		}
		$countVert=count($vert);
		
		if ($countHor>0)
		{
			$sizesArr=array();
			for ($i=0;$i<$countHor;$i++)
			{
				if ($countVert>0)
					for ($j=0;$j<$countVert;$j++) {$sizesArr[]=$hor[$i].'/'.$vert[$j];}
				else 
					$sizesArr[]=$hor[$i];
			}
			//print_r($sizesArr);
		}
		else 
			$sizesArr='';	
		/*
		echo '<br>articul: <b>'.$art.'</b><br>';
		echo 'name: <b>'.$name.'</b><br>';
		echo 'ID: <b>'.$id.'</b><br>';
		echo 'color: <b>'.$color.'</b><br>';
		echo 'material: <b>'.$material.'</b><br>';
		echo 'structure: <b>'.$structure.'</b><br>';
		//echo 'sizes: <br>'.$sizes.'<br>';
		echo '<a href="'.$this->catalogUrl.$imagepath.'" target=_blank>открыть</a><br>';
		*/
		
		$this->httpClient->setRequestsPause (1); 
		
		$itemInfo = new ParserItem ();
		$itemInfo->id    	= $itemId;
		$itemInfo->url   	= $url;
		$itemInfo->name   	= $name;
		$itemInfo->articul 	= $art;
		$itemInfo->categ 	= $path;
		$itemInfo->material	= $material;
		$itemInfo->structure= $structure;
		$itemInfo->sizes    = $sizesArr;	
		$itemInfo->colors   = $color;
		$itemInfo->descr='ID: '.$id;
		
		//	$this->parseError("Can't find image at url '$url'!");
					
		$this->httpClient->getUrlBinary ($imgUrl);
		if ($this->httpClient->getLastCtype () != 'image/png')
			$this->parseError("Content-type header not image/png at url '$imgUrl'!");
		$image = new ParserImage();
		$image->url = $imgUrl;
		$image->path = $this->httpClient->getLastCacheFile();
		$image->type = 'png';
		$itemInfo->images[] = $image;
		
		return $itemInfo;
	}
	
	public function loadItems () 
	{
		$base = array ();
		
		//$this->httpClient->getUrlText ('http://f5jeans.ru/', null, false);
		$content = $this->httpClient->getUrlText ($this->shopBaseUrl);
		
		$document=phpQuery::newDocument($content);
		
		//по списку пунктов меню
		$menuList=$document->find('.grid_2 > #left-menu > li');
		if ($menuList == "") $this->parseError ("Can't parse menu List\n");
		foreach ($menuList as $menuLi)
		{
			$span=pq($menuLi)->find('span')->text();
			if ($span == "") $this->parseError ("Can't parse span with type of collections\n");
			if (stripos($span,'МУЖСКАЯ')!==false || stripos($span,'ЖЕНСКАЯ')!==false)
			{
				$allCatLinks=pq($menuLi)->find('ul > li > a');
				if ($allCatLinks == "") $this->parseError ("Can't parse collection urls\n");
				foreach ($allCatLinks as $catLink)
				{
					$catUrl=$this->shopBaseUrl.pq($catLink)->Attr('href');
					if (!preg_match("/list\.php\?SECTION_ID=(\d+)/",$catUrl,$r))
						$this->parseError ("Can't parse collection SECTION_ID\n");
					
					$catId=$r[1];
					
					$catName=pq($catLink)->text();
					
					$contentCat = $this->httpClient->getUrlText ($catUrl);
					$documentCat=phpQuery::newDocument($contentCat);
								
					$allDivItems=$documentCat->find('.content2 > .b-item-photo');
					if ($allDivItems == "") $this->parseError ("Can't parse div.b-item-photo\n");
					
					$catalogUrl = preg_replace("/\/[-A-Za-z0-9_]+\.php.*/","/",$catUrl);
					
					$items=array();
					foreach ($allDivItems as $divItem)
					{
						$itemUrl=$this->shopBaseUrl.pq($divItem)->find('a')->Attr('href');
						//$itemArt=pq($divItem)->find('.b-item-text')->text();
						//echo $itemArt.'<br>';
											
						if (!preg_match("/detail\.php\?ID=(\d+)/",$itemUrl,$r))
							$this->parseError ("Can't parse item id\n");
							
						//парсинг страницы товара
						$itemInfo = $this->parseF5jeansGoodsPage ($itemUrl, $r[1], $catalogUrl);
						if ($itemInfo === false)
						{
							$this->parseError ("Can't parse goods page '".$itemUrl."'");
							return;
						}
						$items[] = $itemInfo;						
					}
										
					//---------------------------------
					if (isset ($base[$catName]))
					{
						foreach ($items as $item)
							$base[$catName]->items[] = $item;
					}
					else
					{
						$collection = new ParserCollection();
						$collection->id    = $catId;
						$collection->url   = $catUrl;
						$collection->name  = $catName;
						$collection->items = $items;
						$base[$catName] = $collection;
					}
					//unset($documentCat);
				}	
			}
		}		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$url = 'http://f5jeans.ru/buy/';
		$russiaShops = $this->httpClient->getUrlText ($url);
		
		$document=phpQuery::newDocument($russiaShops);
		
		$content=$document->find('.grid_10 > .content');
		if ($content=="") $this->parseError ("Can't parse PhysicalPoints content\n");
		
		$replace1=array("м. Академическая / Тульская, ", "м. Алтуфьево ", "м. Водный стадион ", "м. Проспект Мира ", "м. Теплый Стан, ", "м. Фили / Багратионовская, ", "\"F5\", ", "\"F5\"", "F5", "\"Мульти\", ", "\"мульти\", ", "\"999\", ");
		
		for ($i=0;$i<=1;$i++)
		{
			$head=pq($content)->find('strong:eq('.$i.')')->text();
			if ($head=="") $this->parseError ("Can't parse PhysicalPoints head\n");
			$tableAllTr=pq($content)->find('table:eq('.$i.') > tbody > tr');	
			if ($tableAllTr=="") $this->parseError ("Can't parse PhysicalPoints tables\n");
			foreach ($tableAllTr as $tr)
			{
				$city=pq($tr)->find('.h_city')->text();
				if ($city=="") $this->parseError ("Can't parse PhysicalPoints city\n");
				$city=trim(str_replace('г.','',$city));
				$city=str_replace(' -','-',$city);
				
				$address=trim(pq($tr)->find('.h_addr')->text());
				if ($address=="") $this->parseError ("Can't parse PhysicalPoints address\n");
				$address = preg_replace("|[\s]+|s", " ", trim($address));
				$address = str_replace(" ,", ",", $address);
				$address = preg_replace("|,([А-Яа-я])|", ", \\1", $address);
				$address = preg_replace("|ул\.([А-Я])|", "ул. \\1", $address);
				
				//удаление метро
				//$address = preg_replace("|м\.?\s?[-А-Яа-я\s\/]+,|", "", $address);
				$address = trim(str_ireplace($replace1, "", $address));
				
				if (preg_match("/^(ТЦ|ТРЦ)/",$address,$a))
				{
					if (strpos($address,"ул.")!==false)
						$address = preg_replace("#(.+)(ул\..+)#", "\\2, \\1", $address);	
					else if (strpos($address,"пр-т")!==false)
						$address = preg_replace("#^(ТЦ|ТРЦ)(.+), ([А-Я][^\s]+ пр\-т.+)#", "\\3, \\1\\2", $address);
				}
				
				$length=strlen($address);
				if ($address{$length-1}=="." || $address{$length-1}==",")
					$address = substr($address, 0, $length-1);
				
				$phone=trim(pq($tr)->find('.h_phone')->text());
				//if ($phone=="") $this->parseError ("Can't parse PhysicalPoints phone\n");
							
				$phys = new ParserPhysical();
				$phys->id 		 = '';
				$phys->city 	 = $city;
				$phys->address   = $address;
				$phys->phone     = $phone;
				if (strpos($address, "исконт")!==false) $phys->b_stock = 1;
				$phys->timetable = '';
				$base[] = $phys;
			}
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
