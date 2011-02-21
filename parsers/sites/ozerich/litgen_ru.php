<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_litgen_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.litgen.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shop.php");
        preg_match('#<table cellpadding="5" cellspacing="5" width="100%">(.+?)</table>#sui', $text, $text);
        preg_match_all('#<font color="\#AC2B17" size="4">(.+?)</font>(.+?)(?=<font color="\#AC2B17"|$)#sui', $text[1], $cities, PREG_SET_ORDER);

        foreach($cities as $city)
        {
            $city_name = $city[1];
            $text = $city[2];

            preg_match_all('#<td colspan="2">(.+?)</td>#sui', $text, $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                $shop->city = trim(str_replace('г.','',$city_name));
                $shop->address = $this->address($text);

                if(mb_strpos($shop->address, "e-mail:") !== false)
                    $shop->address = $this->address(mb_substr($shop->address, 0, mb_strpos($shop->address, "e-mail:")));

                preg_match('#т(?:ел)*\.(.+?)$#sui', $shop->address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[1];
                    $shop->address = $this->address(str_replace($phone[0], '',$shop->address));
                }

                $ch = mb_substr($shop->address, 0, 2);
                if($ch == "М." || $ch == "М ")
                {
                    $shop->address = trim(mb_substr($shop->address, 2));
                    $shop->address = trim(str_replace(array('стан", ','вокзал",','Деревня,','Победы,','восстания",','проспект',),array('','','','','',''),mb_substr($shop->address, mb_strpos($shop->address, " ") + 1)));
                }

                $shop->address = trim(str_replace(array('Митино,','Жулебино,','Солнцево,'), array('','',''), $shop->address));

                if($this->address_have_prefix($shop->address))
                {
                    $name = mb_substr($shop->address,0, mb_strpos($shop->address, '"')+1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 1);
                    $name .= mb_substr($shop->address,0, mb_strpos($shop->address, '"')+1);
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 2);
                    $shop->address.=", ".$name;
                }



                $base[] = $shop;
            }
        }
        

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."sobyt.php";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<p><font color="\#AC2B17" size="3">(.+?)</font></p>(.+?)(?=<p><font color="\#AC2B17")#sui', $text, $news, PREG_SET_ORDER);
        for($i = 0; $i < count($news) - 1; $i++)
        {
            $news_value = $news[$i];
            $news_item = new ParserNews();

            $news_item->header = $this->txt($news_value[1]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
    
            $base[] = $news_item;
        }
            
		return $this->saveNewsResult($base);
	}
}
