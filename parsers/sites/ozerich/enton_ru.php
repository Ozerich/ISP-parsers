<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_enton_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.enton.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $this->add_address_prefix('Пермский областной Универмаг');

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."customer/buy.shtml");
        preg_match_all('#nclick="region\((\d+)\)#sui', $text, $regions);
        foreach($regions[1] as $region)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.'customer/'.$region.'region.html');
            preg_match_all('#<h2>(.+?)</h2>(.+?)(?=<h2>|$)#sui', $text, $cities, PREG_SET_ORDER);
            foreach($cities as $city)
            {
                $city_name = $this->txt($city[1]);
                $text = $city[2];

                preg_match_all('#<li>(.+?)</li>#sui', $text, $shops);
                if(!$shops[1])$shops[1][0]=$text;

                foreach($shops[1] as $text)
                {
                    $shop = new ParserPhysical();

                    $shop->city = $city_name;
                    $shop->address = $this->address($text);

                    $shop->address = $this->address(str_replace(array('(фирменный магазин)','(фирменный магазин'),array('',''),$shop->address));

                    $count = 0;
                    while($this->address_have_prefix($shop->address) || $shop->address[0] == '"')
                    {
                        if(mb_strpos($shop->address, '"') === false)break;                       
                        $name = mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                        $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 1);
                        $name .= mb_substr($shop->address, 0, mb_strpos($shop->address, '"') + 1);
                        $shop->address = mb_substr($shop->address, mb_strpos($shop->address, '"') + 1).", ".$name;
                        $shop->address = $this->address($shop->address);

                    }

                    $shop->address = $this->fix_address($shop->address);
                    $base[] = $shop;
                }
            }
        }
        
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl.'news/news.shtml';
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td>\s*<h2>(.+?)</h2>\s*(.+?)<p align="right">(.+?)</p>#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->header = $this->txt($news_value[1]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->date = $this->txt($news_value[3]);
    
            $base[] = $news_item;
        }

            
		return $this->saveNewsResult($base);
	}
}
