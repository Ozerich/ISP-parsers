<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_inwm_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.inwm.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $this->add_address_prefix('"МЕГА Тёплый Стан"');

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."Shop+finder-country=russia.htm");
        preg_match_all('#</div>\s*<a href="(.+?)" class="storeDetails">ДЕТАЛИ</a>\s*</div>#sui', $text, $shops);
        foreach($shops[1] as $url)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$url);
           // $text = $this->httpClient->getUrlText("http://www.inwm.ru/Shop+finder/Ekaterinburg-Xays.htm");

            $shop = new ParserPhysical();
            
            preg_match('#<h1>(.+?)</h1>#sui', $text, $city);
            $shop->city = $city[1];

            preg_match('#Часы работы:<br />(.+?)</p>#sui', $text, $timetable);
            if($timetable)$shop->timetable = $this->txt($timetable[1]);

            preg_match('#тел.([\(\)\d\s-]+)#sui', $text, $phone);
            if($phone)$shop->phone = $this->txt($phone[1]);

            preg_match('#<img src="/img/(?:inw_m|companys)\.gif" border="0">\s*<br />(.+?)</p#sui', $text, $address);

            $address = $address[1];
            if(mb_strpos($address, "тел") !== false)$address = mb_substr($address, 0, mb_strpos($address, "тел"));
            if(mb_strpos($address, "Часы работы:") !== false)$address = mb_substr($address, 0, mb_strpos($address, "Часы работы:"));
            if(mb_strpos($address, "Тел.") !== false)$address = mb_substr($address, 0, mb_strpos($address, "Тел."));
 
            $info_ = explode("<br />", $address);
            $info = array();
            foreach($info_ as $item)
            {
                $item = $this->txt($item);
                if($item != "" && $item != '(м-н "Companys")')
                    $info[] = $this->txt($item);
            }

            $address = $info[0];
            if(count($info) > 1)$address = $info[0].", ".$info[1];

            
            $shop->address = $this->address($address);
            $shop->address = $this->fix_address($shop->address);
            if(mb_substr($shop->address, 0, 3) == 'м-н')
                $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ',') + 1);

            $base[] = $shop;
        }


		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();
        $ids = array();

        $url = $this->shopBaseUrl."news/arhiv_news.htm";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td valign="top"><div class="news" align="justify">(.+?)<br />(.+?)\s*<a href="((\d+).htm)">Подробнее</a>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl."news/".$news_value[3];
            $news_item->id = $news_value[4];
            
            if(in_array($news_item->id, $ids))continue;
            $ids[] = $news_item->id;


            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="news" align="justify">.+?<br />(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }

        $url = $this->shopBaseUrl."akcia/arhiv_akcia.htm";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td valign="top"><div class="news" align="justify">(.+?)<br />(.+?)\s*<a href="((\d+).htm)">Подробнее</a>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl."news/".$news_value[3];
            $news_item->id = $news_value[4];

            if(in_array($news_item->id, $ids))continue;
            $ids[] = $news_item->id;

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="news" align="justify">.+?<br />(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }
        
            
		return $this->saveNewsResult($base);
	}
}
