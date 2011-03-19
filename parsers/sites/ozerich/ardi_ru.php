<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ardi_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ardi.ru/";
	
	public function loadItems () 
	{
		$base = array ();

		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."index.php?act=retail");
        preg_match('#ФИРМЕННЫЕ МАГАЗИНЫ.+?<td align=left><b>Телефон</td>(.+?)$#sui', $text, $text);
        preg_match_all('#<tr.+?><td></td><td align=left>(.*?)</td><td align=left>(.*?)</td><td></td><td align=left>(.*?)</td><td align=left nowrap>(.*?)</td><td align=right>(.*?)</td>#sui', $text[1], $shops, PREG_SET_ORDER);

        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $shop->city = $this->txt($shop_value[1]);
            $shop->address = $this->txt($shop_value[2].', '.$shop_value[3]);
            $shop->phone = $this->txt($shop_value[4]);

            $base[] = $shop;
        }

        return $this->savePhysicalResult($base);
    }
	
	public function loadNews ()
	{
        return null; 
	}
}
