<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_kiddy_russia_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.kiddy-russia.ru/";
	
	public function loadItems () 
	{
        return null;
    }
	
	public function loadPhysicalPoints () 
	{
        return null;
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."index1.php?info=contact");

        preg_match_all('#<strong>Москва,(.+?)</strong>.+?РАСПИСАНИЕ РАБОТЫ&nbsp;:(.+?)(?:Контактный телефон:|Телефон для справок:)(.+?)(?:</span>|</skype:span><br />)#sui', $text, $shops, PREG_SET_ORDER);

        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $shop->address = $this->txt($shop_value[1]);
            $shop->timetable = $this->txt($shop_value[2]); 
            $shop->phone = $this->txt($shop_value[3]);
            $shop->city = 'Москва';

            $base[] = $shop;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."index1.php?option=news";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<p><strong><a href="(\?option=news&page=1&id=(\d+))">(.+?)</a></strong><br><strong>(.+?)</strong><br>(.+?)</p>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl."index1.php".$news_value[1];
            $news_item->id = $news_value[2];
            $news_item->date = $this->txt($news_value[4]);
            $news_item->header = $this->txt($news_value[3]);
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<td><span class="text">(.+?)</td>#sui', $text, $content);
            $news_item->contentFull = $content[1];
    
            $base[] = $news_item;
        }


        $url = $this->shopBaseUrl."index1.php?info=present";
        $text = $this->httpClient->getUrlText($url);
        preg_match('#<p class="MsoNormal"><font color="\#ff0000" size="3"><font color="\#000000" size="\+0">(.+?)<p class="MsoNormal">#sui', $text, $text);

        $news_item = new ParserNews();

        $news_item->urlShort = $news_item->urlFull = $url;
        $news_item->contentShort = $news_item->contentFull = $text[1];

        $base[] = $news_item;
        

		return $this->saveNewsResult($base);
	}
}
