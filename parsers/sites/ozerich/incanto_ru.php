<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/sema.php';

class ISP_incanto_ru extends ItemsSiteParser_Sema
{	
	protected $shopBaseUrl = 'http://incanto.ru'; // Адрес главной страницы сайта 
		
	public function loadNews() { 
		$base = array ();
		$url		= $this->shopBaseUrl.'/ru/';
		$url_short	= $url.'news.htm';
		
		
		$news = $this->httpClient->getUrlText ($url_short);
		
		while ($start=strpos($news,'<!--'))
		{
			$end	= strpos($news,'-->',$start)+strlen('-->');
			if ($end==strlen('-->'))
				$news	= substr($news,0,$start);
			else
				$news	= substr($news,0,$start).substr($news,$end);
		}
		
		$pregNews = '#href="(news([\d]+).htm)">([^<]+)<#sui';
		
		preg_match_all ($pregNews, $news, $newsResult, PREG_SET_ORDER);
		
		foreach ($newsResult as $one_news)
		{
			$news_url	= $url.$one_news[1];
			$news_data	= $this->httpClient->getUrlText($news_url);
			while ($start=strpos($news_data,'<!--'))
			{
				$end		= strpos($news_data,'-->',$start)+strlen('-->');
				$news_data	= substr($news_data,0,$start).substr($news_data,$end);
			}
			$news_data=preg_replace('/<[^>]*id="close"[^>]*>[^<]*<\/[^>]*>/','',$news_data);
			
			$base[]	= $newsElem 	= new ParserNews();	
			$newsElem->id           = $one_news[2];
			$newsElem->header		= $this->get_text_data($one_news[3]);
			$newsElem->urlShort     = $url_short;
			$newsElem->urlFull      = $news_url;
			
			$p	= strpos($news_data,'div id="text"');
			if ($p>0)
			{
				$content		= $this->getData('<div id="text"','div',$news_data,$p-1).'>';
				
				$start			= strpos($content,'>')+1;
				$end			= strpos($content,'<p>',$start);
				$contentShort	= substr($content,$start,$end-$start);
			}
			else
			{
				$content		= $this->getData(' id="content_table"','table',$news_data,0);
				$start			= strpos($content,'</div>');
				$content		= $this->getData('<div','div',$content,$start).'>';
				
				$start			= strpos($content,'<p>')+strlen('<p>');
				$end			= strpos($content,'</p>',$start);
				$contentShort	= substr($content,$start,$end-$start);
			}
			$newsElem->contentFull	= $content;
			
			$newsElem->contentShort = $contentShort;
		}
	
		
		return $this->saveNewsResult ($base);
	}
	
	function load_collection($id,$name,$collection_url)
	{
		$items_info			= array();
		$collection_data	=$this->httpClient->getUrlText ($collection_url);
		
		
		if (preg_match('#dir_big_foto="([^"]*)";#sui',$collection_data,$dir_foto))
		{
			$img_url='http://incanto.ru/img/'.$dir_foto[1].'/';
			preg_match_all("#details\[([\d]+)\]\+?='<b>([^<]*)</b> ([^ ]*) ([^']*)';#sui",$collection_data,$items,PREG_SET_ORDER);
			
			
			foreach ($items as $item)
			{
				$item[4]			= $this->get_text_data($item[4]);
				if ($item[4][strlen($item[4])-1]==',')
					$item[4][strlen($item[4])-1] = ' ';
				$itemInfo 			= new ParserItem ();
				$itemInfo->url   	= $collection_url;
				$itemInfo->name		= $this->get_text_data($item[2]);
				$itemInfo->id    	= $item[4];
				$itemInfo->articul	= $item[4];
				$itemInfo->brand	= $this->get_text_data($item[3]);
				
				$itemInfo->images[]=$this->item_image_form($img_url.$item[1].'.jpg');
				
				$items_info[]=$itemInfo;
			}
			
			$collection 		= new ParserCollection();
			$collection->id		= $id;
			$collection->url	= $collection_url;
			$collection->name	= $name;
			$collection->items	= $items_info;
			return $collection;
		}
		else
			$this->parseWarning("Unknown format of collection at url '$collection_url'");
		return null;	
	}
	
	
	public function loadItems () 
	{
		$base = array ();
		
		$base[] = $this->load_collection("INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 Белье","INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 Белье","http://incanto.ru/ru/underwear.htm");
		
		// print_r($base);
		// die();
		
		$base[] = $this->load_collection("INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 Домашняя одежда","INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 Домашняя одежда","http://incanto.ru/ru/homewear.htm");
		
		$base[] = $this->load_collection("INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 Одежда","INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 Одежда","http://incanto.ru/ru/clothing.htm");
		
		//$base[] = $this->load_collection("INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 ","INCANTO ОСЕНЬ-ЗИМА 2010 – 2011 ","http://incanto.ru/ru/");
		
		return $this->saveItemsResult ($base);
	}
	
	function get_text_data($html)
	{
		return html_entity_decode(trim(preg_replace("/(<[^>]*>)/",' ',$html)),ENT_QUOTES,'UTF-8');
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$url		= $this->shopBaseUrl.'/ru/shop.php';
		$url_post	= $this->shopBaseUrl.'/ru/shop_address.php';
		//$url_post	= 'http://sema2.arvixe.ru/test.php';
		$countries_data	= $this->httpClient->getUrlText ($url);
		
		$countries_data	= $this->getData('<select id="shop_country">','select',$countries_data,0);
		
		preg_match_all("/<option>([^<]*)<\/option>/",$countries_data,$countries,PREG_SET_ORDER);
		
		foreach ($countries as $country)
		if (strpos($country[1],'ыберите')==0)
		{
			$post_data	= array('country'=>$country[1]);
			
			$cities_data	= $this->httpClient->getUrlText($url_post,$post_data);
			
			
			preg_match_all("/<option>([^<]*)<\/option>/",$cities_data,$cities,PREG_SET_ORDER);
			
			
			
			foreach ($cities as $city)
			if (strpos($city[1],'ыберите')==0)
			{
				
				$post_data	= array('city'=>$city[1]);
				
				$shops_data	= $this->httpClient->getUrlText($url_post,$post_data);
				
				preg_match_all("/<div [^>]*>([^<]+)(<div>([^<]+)<\/div>)?(<\/div>)/",$shops_data,$salons,PREG_SET_ORDER);
				
				foreach ($salons as $salon)
				{
					$phys = new ParserPhysical();
					$phys->city 	= $city[1];
					$phys->address	= $this->get_text_data($salon[1]);
					if ($salon[3]=='дисконт')
						$phys->b_stock	= 1;
					else
						$phys->b_stock	= 0;
					if ($salon[3]=='не открыт')
						$phys->b_closed	= 1;
					else
						$phys->b_closed	= 0;
					
					$p	= substr($phys->address,0,4);
					if (($p=="ТЦ") || ($p=="ТР") ||($p=="СТ"))
					{
						$pos			= strpos($phys->address,',');
						if ($pos===false)
							$pos		= strpos($phys->address,' ',strpos($phys->address,' ')+2);
						else
							$pos		+=2;
							
						$phys->address	= substr($phys->address,$pos).", ".substr($phys->address,0,$pos);
						if (strlen($phys->address)-strrpos($phys->address,',')==strlen(","))
							$phys->address	= substr($phys->address,0,strrpos($phys->address,','));
					}
				
					$phys->address = preg_replace ("/,\s*$/u", "", $phys->address);
					$base[]=$phys;
				}
			}
		}
		
		return $this->savePhysicalResult ($base);
	}
}

?>