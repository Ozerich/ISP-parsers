<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ketroy_com extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ozis.by/";

	public function loadItems () 
	{
	    return null;
	}
	
	public function loadPhysicalPoints () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<div class="russia">(.+?)</div>\s*<span>(.+?)</span>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            preg_match_all('#<p>(.+?)</p>#sui', $city[2], $shops);
            foreach($shops[1] as $text)
            {
                $shop = new ParserPhysical();

                $shop->city = $this->txt($city[1]);
                $shop->address = $text;

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
