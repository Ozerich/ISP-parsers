<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_mizuno_eu extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.mizuno.eu/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."ru-rus/resources/storelocator_service.aspx/GetStores/?_=1294856593391&latitude=62.0709680767349&longitude=93.779296875&catIds_string=2&restrictToSite=true");

        preg_match_all('#\{(.+?)\}\]\},#sui', $text, $texts);
        foreach($texts[1] as $text)
        {
            $shop = new ParserPhysical();

            preg_match('#"id":(\d+)#sui', $text, $id);
            $shop->id = $id[1];
            
            preg_match('#"address"\:"(.+?)",#si', $text, $address);
            $shop->address = $address[1];

            preg_match('#"telephone"\:"(.+?)",#si', $text, $phone);
            $shop->phone = $phone[1];

            $shop->address = trim(str_replace(array('\u000a','\u000d','г.','\\'), array('','','','',''), $shop->address));
            if(mb_strpos($shop->address, ", ") !== false)
            {
                $shop->city = mb_substr($shop->address, 0, mb_strpos($shop->address, ", "));
                $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ", ") + 2);
            }
            else
            {
                $shop->city = $shop->address;
                $shop->address = "";
            }
            if($shop->address == "Интернет-магазин")
                continue;

            if($shop->address == "")
                continue;
            $base[] = $shop;
        }
            
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."ru-rus/news";
        $text = $this->httpClient->getUrlText($url);
        preg_match('#<div class="overview-news">(.+?)</ul>#sui', $text, $text);
        preg_match_all('#<li>(.+?)</li>#sui', $text[1], $texts);
        foreach($texts[1] as $text)
        {
            $news = new ParserNews();
            
            preg_match('#<h3>(.+?)</h3>\s*<p style="text-align: justify">(.+?)<div class="read-more">\s*<a href="/(.+?)/">#sui', $text, $info);
            
            $news->header = $this->txt($info[1]);
            $news->contentShort = $info[2];
            $news->urlShort = $url;
            $news->urlFull = $this->shopBaseUrl.$info[3]."/";
            $news->id = mb_substr($info[3], mb_strrpos($info[3], "/") + 1);

            $text = $this->httpClient->getUrlText($news->urlFull);
            preg_match('#</h3>(.+?)</div>#sui', $text, $content);
            $news->contentFull = $content[1];
            

            $base[] = $news;
        }
		
		return $this->saveNewsResult($base);
	}
}
