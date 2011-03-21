<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_people4people_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.people4people.ru/";

	public function loadItems () 
	{
		return null;
	}
	
	public function loadPhysicalPoints () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#<a href="/(shops/\d+/)">(.+?)</a><br>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city[1]);
            $city = $this->txt($city[2]);

            preg_match_all('#<div style="display: inline-block;">(.+?)</div>#sui', $text, $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                $shop->address = $this->txt(mb_substr($text, 0, mb_strpos($text, '<br/>')));
                $shop->phone = $this->txt(mb_substr($text, mb_strpos($text, '<br/>') + 5));
                $shop->city = $city;

                preg_match('#г\.(.+?),#sui', $shop->address, $city_name);
                if($city_name)
                {
                    $shop->city = $this->txt($city_name[1]);
                    $shop->address = str_replace($city_name[0],'',$shop->address);
                    $shop->address = $this->address($shop->address);
                }

                $base[] = $shop;
            }
        }

        return $this->savePhysicalResult($base);
    }
	
	public function loadNews ()
	{
        return null; 
	}
}
