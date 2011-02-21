<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_courtney_g_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.courtney-g.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shop_content.php?coID=2");
        preg_match_all('#<strong>(.+?)</strong>(.+?)</p>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city)
        {
            $city_name = trim(str_replace(array(' и Московская область','г.'),array('',''),$this->txt($city[1])));
            $text = $city[2];

            preg_match_all('#(?:<font face="verdana,geneva".*?>)*(.+?) -(.+?)(?:</font>|$|<br /><br />)#sui', $text, $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->city = $city_name;

                $name1 = $shop_value[1];
                $name2 = $shop_value[2];

                $name21 = mb_substr($name2, 0, mb_strrpos($name2, ','));
                $name22 = mb_substr($name2, mb_strrpos($name2, ',') + 1);
                
                $shop->address = $this->address(trim($name21.", ".$name1.", ".$name22));

                preg_match('#г. (.+?),#sui', $shop->address, $city_value);
                if($city_value)
                {
                    $shop->city = $city_value[1];
                    $shop->address = str_replace($city_value[0], '',$shop->address);
                }
                
                $base[] = $shop;
            }
        }     

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);

        preg_match_all('#<p><span style="color: \#333333;">(.+?)</span></p>(?:\s*<p><img src="templates/vamshop/img/pic\.jpg" border="0" /> <span style="text-decoration\: underline;"><a href="\.\./(shop_content.php\?coID=(\d+))">Подробнее</a></span></p>)*#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $this->shopBaseUrl;
            $news_item->contentShort = $news_value[1];
            $news_item->header = $this->txt($news_value[1]);

            if(isset($news_value[2]))
            {
                //$news_item->id = $news_value[3];
                $news_item->urlFull = $this->shopBaseUrl.$news_value[2];
                $text = $this->httpClient->getUrlText($news_item->urlFull);
                preg_match('#<td align="left" valign="top">(.+?)</td>#sui', $text, $content);
                $news_item->contentFull = $content[1];
            }
    
            $base[] = $news_item;
        }
		
		return $this->saveNewsResult($base);
	}
}
