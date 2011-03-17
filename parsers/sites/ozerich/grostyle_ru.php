<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';

class ISP_grostyle_ru extends ItemsSiteParser_Drakon {
	protected $shopBaseUrl = 'http://grostyle.ru/'; 
	

	function parseGrostyleGoodsPage($title, $articul, $material, $img, $descr,$url) {
		
		$itemInfo = new ParserItem ();
		
		$itemInfo->id = $articul;
		$itemInfo->articul = $articul;	
		$itemInfo->url = $url;		
		$itemInfo->material = preg_replace("/%/","% ",$material);
		$itemInfo->name = $title;
		$itemInfo->descr = preg_replace ( "/\">/", "", strip_tags ( $descr ) );
		
		$imgUrl = $img;
		
		$this->httpClient->getUrlBinary ( $imgUrl );
		if ($this->httpClient->getLastCtype () != 'image/jpeg')
			$this->parseError ( "Content-type header not image/jpeg at url '$imgUrl'!" );
		$image = new ParserImage ();
		$image->url = $imgUrl;
		$image->path = $this->httpClient->getLastCacheFile ();
		$image->type = 'jpeg';
		$itemInfo->images[] = $image;
		return $itemInfo;
	}
	
	public function loadItems() {
		$base = array ();
		
		$this->httpClient->getUrlText ( 'http://grostyle.ru/', null, false );
		
		$body = $this->httpClient->getUrlText ( 'http://grostyle.ru/catalog.php' );
		
		$mainp = '#<ul class="subnav" id="menu">(.+?)<div class="maincol">#sui';
		preg_match_all ( $mainp, $body, $main );
		
		$main_p_link = '#<a href=\'(.+?)\' >(.+?)</a>#sui';
		preg_match_all ( $main_p_link, $main[1][0], $main_link );
		foreach ( $main_link[0] as $links ) {
			$linkp = '#<a href=\'(.+?)\' >(.+?)</a>#sui';
			preg_match_all ( $linkp, $links, $all_link );
			
			

			$body_collp = $this->httpClient->getUrlText ( $all_link[1][0] );
			
			$pagep = '#&\#8594;</a><a href="(.+?)">(.+?)</a>#sui';
			preg_match_all ( $pagep, $body_collp, $pages );
			
			for($i = 1; $i <= $pages[2][0]; $i ++) {
				
				$body_coll = $this->httpClient->getUrlText ( preg_replace ( "/1/", "$i", $all_link[1][0] ) );
				$coll_p = '#<li class="bgproduct_list padding_R10 margin_T10">(.+?)</li>#sui';
				preg_match_all ( $coll_p, $body_coll, $coll_i );
				$items = array ();
				
				foreach ( $coll_i[0] as $item ) {
					$titlep = '#<font class="title_product">(.+?)</font>	#sui';
					preg_match_all ( $titlep, $item, $title );
					
					$artp = '#<strong>(.+?) | <#sui';
					preg_match_all ( $artp, $item, $art );
					
					$materialp = '#<font class="desc_product">(.+?)</font>#sui';
					preg_match_all ( $materialp, $item, $material );
					
					$descp = '#<a href=\'(.+?)\' class="zoom" rel="group" title="(.+?)">#sui';
					preg_match_all ( $descp, $item, $desc );
					
					$itemInfo = $this->parseGrostyleGoodsPage ( $title[1][0], $art[1][0], $material[1][0], $desc[1][0], $desc[2][0], preg_replace ( "/1/", "$i", $all_link[1][0] ) );
					if ($itemInfo === false) {
						$this->parseError ( "Can't parse goods page '$urlGoodsPage'" );
						return;
					}
					$items[] = $itemInfo;
				}
				
				if (empty ( $items ))
					continue;
				
				$collectionName = $all_link[2][0];
				
				if (isset ( $base[$collectionName] )) {
					foreach ( $items as $item )
						$base[$collectionName]->items[] = $item;
				} else {
					$collection = new ParserCollection ();
					$collection->id = ereg_replace ( "[^0-9]", "", $all_link[1][0] );
					$collection->url = $all_link[1][0];
					$collection->name = $all_link[2][0];
					$collection->items = $items;
					$base[$collectionName] = $collection;
				}
			}
		}
		
		return $this->saveItemsResult ( $base );
	}
	
	public function loadPhysicalPoints() {
		$base = array ();
		
		$url = 'http://grostyle.ru/shops.php';
		$Shops = $this->httpClient->getUrlText ( $url );
		
		$mainp = '#<ul class="subnav" id="menu">(.+?)<div class="maincol">#sui';
		preg_match_all ( $mainp, $Shops, $main );
		
		$main_p_link = '#<a href=\'(.+?)\'  >#sui';
		preg_match_all ( $main_p_link, $main[1][0], $main_link );
		
		foreach ( $main_link[1] as $links ) {
			
			$body_shop = $this->httpClient->getUrlText ( $links );
			
			$shopp = '#<H3>(.+?)</H3><P>(.+?)<BR>(.+?)<#sui';
			preg_match_all ( $shopp, $body_shop, $main_link2 );
			foreach ( $main_link2[0] as $s ) {
				$shoppy = '#<H3>(.+?)</H3><P>(.+?)<BR>(.+?)<#sui';
				preg_match_all ( $shoppy, $s, $inf );
				
				$phys = new ParserPhysical ();
				
				$phys->city = $inf[1][0];
				$phys->address = preg_replace ( '/""/', '"', $inf[3][0] . ", " . preg_replace ( "/,/", "", $inf[2][0] ) );
				$phys->city = preg_replace ( "/^\s*г\./", "", $phys->city );
				$base[] = $phys;
			}
		}
		
		return $this->savePhysicalResult ( $base );
	}
	
	public function loadNews() {
		$base = array ();
		
		$baseUrl = 'http://grostyle.ru/news.php';
		$body_collp = $this->httpClient->getUrlText ( $baseUrl );
		
		$pagep = '#&\#8594;</a><a href="(.+?)">(.+?)</a>#sui';
		preg_match_all ( $pagep, $body_collp, $pages );
		
		for($i = 0; $i <= $pages[2][0]; $i ++) {
			
			$body = $this->httpClient->getUrlText ( 'http://grostyle.ru/news/' . $i . '/' );
			$np = '#<span class="cat"(.+?)</a></p>#sui';
			preg_match_all ( $np, $body, $nps );
			foreach ( $nps[0] as $nas ) {
				
				$newsp = '#<p>(.+?)</p>#sui';
				preg_match_all ( $newsp, $nas, $news );
			
				

				$linkp = '#<a class="lnk_cta" href="(.+?)">#sui';
				preg_match_all ( $linkp, $nas, $link );
				
				

				$news_body = $this->httpClient->getUrlText ( $link[1][0] );
				$dat = '#<font class="data">(.+?)</font>#sui';
				preg_match_all ( $dat, $news_body, $date );
			
				$head = '#<h2>(.+?)</h2>#sui';
				preg_match_all ( $head, $news_body, $header );
				$ful = '#<DIV><(.+?)<IMG#sui';
				preg_match_all ( $ful, $news_body, $full );
				
				
				

				$base[] = $newsElem = new ParserNews ();
				
				$newsElem->id = preg_replace ( "/[^0-9]/", "", $link[1][0] );
				$newsElem->date = $date[1][0];
				$newsElem->contentShort = preg_replace ( "/&nbsp;/", "", strip_tags ( $news[1][0] ) );
				$newsElem->urlShort = $baseUrl;
				$newsElem->urlFull = $link[1][0];
				$newsElem->header = $header[1][0];
				$newsElem->contentFull = preg_replace ( "/&nbsp;/", "", strip_tags ( $full[0][0] ) );
			}
		}
		
		return $this->saveNewsResult ( $base );
	}
}
