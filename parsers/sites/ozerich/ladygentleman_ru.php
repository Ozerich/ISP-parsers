<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ladygentleman_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ladygentleman.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl.'light/shops/');
        preg_match('#<h1>Сеть магазинов</h1>(.+?)<img#sui', $text, $text);
        preg_match_all('#<a href="/(light/shops/.+?)".+?>(.+?)</a>#sui', $text[1], $cities_, PREG_SET_ORDER);
        $cities = array(array("1"=>"light/shops/moscow/city.php","2"=>"Москва"),
                        array("1"=>"light/shops/moscow/disc.php","2"=>"Москва(дисконт)"));
        foreach($cities_ as $ind=>$city)
            if($ind)
                $cities[] = $city;
        foreach($cities as $city)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
            preg_match('#</h1>(.+?)<img#sui', $text, $text);
            preg_match_all('#<a href="(/*.+?)".*?>(.*?)</a>#sui', $text[1], $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                if($this->txt($shop_value[2]) == "")continue;
                $url = $shop_value[1];
                if(mb_substr($url,0, 4) == "http")
                    $url = $shop_value[1];
                else if($url[0] == '/')$url = $this->shopBaseUrl.mb_substr($shop_value[1],1);
                else $url = $this->shopBaseUrl.mb_substr($city[1], 0, mb_strrpos($city[1], '/')+1).$shop_value[1];
                $text = $this->httpClient->getUrlText($url);

                preg_match('#</h1>(.+?)(?:Проезд|Контактный телефон)#sui', $text, $address);
                if(!$address)preg_match('#</h1>(.+?)(?:<br />|</p>|<img)#sui', $text, $address);
                preg_match('#Телефон:*(.+?)(?:<br />|<p>|</p>)#sui', $text, $phone);
                preg_match('#Ждем Вас(.+?)(?:<br />|</p>)#sui', $text, $timetable);

                if($phone && $this->txt($phone[1]) == 'ы:')preg_match('#Телефоны:(.+?)<p> </p>#sui', $text, $phone);

                if($address && $this->txt($address[1]) == "")
                    preg_match("#</h1>\s*<img.+?>\s*<br />\s*<br />(.+?)<br#sui", $text, $address);


                $shop = new ParserPhysical();

                $shop->city = $this->txt($city[2]);
                if($address)$shop->address = $this->txt($address[1]);
                if($phone)$shop->phone = $this->txt($phone[1]);
                if($timetable)$shop->timetable = $this->txt($timetable[1]);

                $shop->address = trim(str_replace(array('Адрес:','МО, ','191040,','ХМАО-Югра'),array('','','',''),$shop->address));
                preg_match('#г\.(.+?),#sui', $shop->address, $city_name);
                if($city_name)
                {
                    $shop->city = $this->txt($city_name[1]);
                    $shop->address = str_replace($city_name[0],'',$shop->address);
                }

                $shop->city = str_replace('Сургут Тюменской области','Сургут', $shop->city);
                if(mb_strpos($shop->city,'(дисконт)') !== false)
                {
                    $shop->b_stock = 1;
                    $shop->city = str_replace('(дисконт)','',$shop->city);
                }

                $shop->address .= ', '.$this->txt($shop_value[2]);
                $shop->address = trim(str_replace(array('(г. Реутов)','ТЦ "Мега",'),array('',''),$shop->address));


                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<span class="news-date-time">(.+?)</span>\s*<a href="/(index.php\?ELEMENT_ID=(\d+))">(.+?)</a><br />(.+?)<div style="clear:both">#sui',
            $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->id = $news_value[3];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[5];
            $news_item->urlShort = $this->shopBaseUrl;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[2];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#</h3>(.+?)<div style="clear:both"></div>#sui', $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

		return $this->saveNewsResult ($base); 
	}
}
