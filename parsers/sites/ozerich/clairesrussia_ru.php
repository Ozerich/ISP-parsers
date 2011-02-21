<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_clairesrussia_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.clairesrussia.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#div class="citybn">(.+?)</div>(.+?)(?=</div><div class="citybn">)#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = $city[1];
            $text = $city[2];
            preg_match_all('#<div class="shname"><a id=".+?">(.+?)</a></div><div class="shblock" id=".+?"><p>Часы работы:(.+?)Телефон:(.+?)</p><p>Адрес:(.+?)</p>#sui', $text, $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->city = $city_name;
                $shop->timetable = $this->txt($shop_value[2]);
                $shop->phone = $this->txt($shop_value[3]);
                $shop->address = $this->address($shop_value[4]).", ".$this->txt($shop_value[1]);

                if(mb_strpos($shop->address,"МО") !== false)
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, "МО") + 4);

                if(mb_strpos($shop->address, "район") !== false)
                    $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ",") + 2);

                preg_match('#г\.(.+?),#sui', $shop->address, $city);
                if($city)
                {
                    $shop->city = $this->txt($city[1]);
                    $shop->address = str_replace($city[0], '', $shop->address);
                }
        
                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
        return null;
	}
}
