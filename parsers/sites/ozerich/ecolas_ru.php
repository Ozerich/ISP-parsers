<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ecolas_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ecolas.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?page=RuOurShops&Sid=SID");
        preg_match_all('#<TR><TD>(?:<P class=wysiwyg>)*<A href="(.+?)">(.+?)</A>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $this->txt($city[2]);
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);

            preg_match_all('#<TR(?:\s*vAlign=center)*><TD(?:\s*vAlign=center align=left)*><FONT\s*color=\#009933>(.+?)</FONT></TD><TD(?:\s*vAlign=center\s* align=left)*>(.+?)</TD>#sui', $text, $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->address = str_replace(array('Сервисный центр:','o"SHADE,'),array('',''),$this->txt($shop_value[1]));
                $shop->phone = $this->txt($shop_value[2]);
                $shop->city = $city_name;

                preg_match('#г\.(.+?),#sui', $shop->address, $city);
                if($city)
                {
                    $shop->city = $this->txt($city[1]);
                    $shop->address = str_replace($city[0],'',$shop->address);
                }

                $shop->address = $this->address($shop->address);
                
                $base[] = $shop;
            }
            
        }
        print_r($base);

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{

		$base = array();

        $url = $this->shopBaseUrl."?page=RuNews&Sid=SID";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#noindex>\s*<b style="color:red">(.+?)		(.*?)</b><br>(.+?)</noindex>#sui', $text, $news, PREG_SET_ORDER);


        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->urlShort = $url;

            $news_item->contentShort = $news_value[3];
            preg_match('#<div align=right><a href=(.+?)>Подробнее\.\.\.</a>#sui', $news_item->contentShort, $url_full);
            if($url_full)
            {
                $news_item->urlFull = $url_full[1];
                $news_item->contentShort = str_replace($url_full[0],'',$news_item->contentShort);
                $news_item->contentFull = $news_item->contentShort;
            }

            $news_item->contentShort = $this->txt($news_item->contentShort);
    
            
            $news_item->header = $this->txt($news_value[2]);

            $news_item->date = str_replace('-','.',$news_item->date);
            $date = explode('.',$news_item->date);
            $news_item->date = $date[2].'.'.$date[1].'.'.$date[0];
        
            $base[] = $news_item;
        }

        
		return $this->saveNewsResult($base);
	}
}
