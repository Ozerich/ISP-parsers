<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_sonyababy_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.sonyababy.ru/";
	
	public function loadItems () 
	{
		return null;
	}
	
	public function loadPhysicalPoints () 
	{
		return null;
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		

		preg_match_all('#<div class="views-field-created">\s*<span class="field-content">(.+?)</span>\s*</div>\s*<div class="views-field-title">\s*<span class="field-content"><a href="(.+?)">(.+?)</a></span>\s*</div>\s*<div class="views-field-teaser">\s*<div class="field-content"><p><span style=".+?">(.+?)</span></p></div>\s*</div>#sui',$text,$news,PREG_SET_ORDER);
		
		
		foreach($news as $news_item)
		{
			$item = new ParserNews();
			
			$item->date = $news_item[1];
			$item->urlFull = $this->shopBaseUrl.$news_item[2];
			$item->id =substr($item->urlFull, strrpos($item->urlFull, "/") + 1);
			$item->header = $news_item[3];
			$item->urlShort = $this->shopBaseUrl;
			$item->contentShort = $news_item[4];
			
			$text = $this->httpClient->getUrlText($item->urlFull);
			
			preg_match('#<div class="content clear-block">\s*<p><span style=".+?">(.+?)</span></p>#sui', $text, $content);
			$item->contentFull = $content[1];
			
			$base[] = $item;
		}
		
		return $this->saveNewsResult ($base); 
	}
}
