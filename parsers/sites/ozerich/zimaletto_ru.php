<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_zimaletto_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.zimaletto.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."ru/boutiques/");
        preg_match_all('#<ul class="list">(.+?)</ul>#sui', $text, $text);

        $texts = array();
        for($i = 2; $i < count($text[1]); $i++)
            $texts[] = $text[1][$i];

        $text = $this->txt($text[1][0]);
        $items = preg_split('#(ТР*Ц)#sui', $text);

        for($i = 1; $i < count($items); ++$i)
        {
            $text = "ТЦ ".$items[$i];
            $shop = new ParserPhysical();
            
            $shop->city = "Москва";
            $shop->address = $text;

            preg_match('#Режим работы:(.+)#sui', $shop->address, $timetable);
            if($timetable)
            {
                $shop->timetable = $timetable[1];
                $shop->address = str_replace($timetable[0], '', $shop->address);
            }

            preg_match('#\+([\s|\d|\(||)]+)#sui', $shop->address, $phone);
            if($phone)
            {
                $shop->phone = $phone[1];
                $shop->address = str_replace($phone[0], '', $shop->address);
            }

            $shop->address = str_replace(array('тел.:', 'Тел.:'), array('',''),$shop->address);
            
            if($this->address_have_prefix($shop->address))
            {
                $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ","));
                $shop->address = trim(mb_substr($shop->address, mb_strpos($shop->address, ",") + 2)).", ".$name;
            }
            
            $base[] = $shop;
        }
        
        foreach($texts as $city_text)
        {
            preg_match('#<li><b>(.+?)</b>(.+)#sui', $city_text, $info);
            $city = $info[1];
            preg_match_all('#<STRONG>(.+?)</STRONG>#sui', $info[2], $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();
                
                $shop->city = $city;
                $shop->address = $this->txt($shop_value[1]);

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ","));
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ",") + 2).", ".$name;
                }
                
                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."ru/about/news/";
        $text = $this->httpClient->getUrlText($url);
        preg_match('#<table border="0" cellspacing="0" cellpadding="0" style="padding-top: 28px;">(.+?)</table>#sui', $text, $text);
        preg_match_all('#<b>(.+?)<br><a href="(index.php\?id4=(\d+))" class="title">(.+?)</a></b>#sui', $text[1], $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $news_value[1];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl."ru/about/news/".$news_value[2];
            $news_item->id = $news_value[3];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<td valign="top" width="100%" height="100%"><h1>Новости </h1>(.+?)<ul class="arrow" id="noprint">#sui', $text, $content);
            if($content)$news_item->contentFull = $content[1];
            
            
            $base[] = $news_item;
        }
		
		return $this->saveNewsResult($base);
	}
}
