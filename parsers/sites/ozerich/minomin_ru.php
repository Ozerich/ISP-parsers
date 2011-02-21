<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_minomin_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.minomin.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."contacts/index.html");
        preg_match('#<blockquote>(.+?)</blockquote>#sui', $text, $text);
        preg_match_all('#<a href="(.+?)">(.+?)</a>#sui', $text[1], $cities, PREG_SET_ORDER);

        foreach($cities as $city)
        {
            $city_name = $this->txt($city[2]);
            $text = $this->httpClient->getUrlText($city[1]);
            preg_match('#<blockquote>(.+?)</blockquote>#sui', $text, $text);
            preg_match_all('#<tr>(.+?)</tr>#sui', $text[1], $shops);
            for($i = 1; $i < count($shops[1]); $i++)
            {
                $text = $shops[1][$i];
                preg_match('#<td.*?>.*?<a href="(.+?)".*?>.*?/td>\s*<td.*?>.+?</td>\s*<td.*?>(.+?)</td>\s*<td.*?>(.+?)</td>#sui', $text, $info);
                if($info)
                {
                    $shop = new ParserPhysical();
                
                    $shop->phone = $this->txt($info[2]);
                    if($shop->phone == "Скоро открытие!!!")continue;
                        
                    $shop->timetable = $this->txt($info[3]);
                    $shop->city = $city_name;

                    $text = $this->httpClient->getUrlText($info[1]);
                    preg_match('#<pre>(.+?)</pre>#sui', $text, $address);
                    if(!$address)preg_match('#<p class="style4">(.+?)<p class="style1">#sui', $text, $address);
                    
                    $shop->address = $this->txt($address[1]);
                    if($shop->address == "")continue;

                    if(mb_strpos($shop->address, 'Тел.') !== false)$shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address, 'Тел.'));
                    if(mb_strpos($shop->address, 'Метро') !== false)$shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address, 'Метро'));
                    if(mb_strpos($shop->address, 'Как добраться?') !== false)$shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address, 'Как добраться?'));

                    preg_match('#г\. (.+?)(?:,|\s)#sui', $shop->address, $city);
                    if($city)
                    {
                        $shop->city = $this->txt($city[1]);
                        $shop->address = str_replace($city[0], '', $shop->address);
                    }

                    $shop->address = str_replace(array('МО, ','Московская обл.,'),array('',''),$shop->address);
                    $shop->address = $this->address($shop->address);
                    $shop->address = str_replace('Золотой Вавилон','Золотой_Вавилон', $shop->address);

                    if($this->address_have_prefix($shop->address))
                    {
                        $name = mb_substr($shop->address, 0, mb_strpos($shop->address, ' '));
                        $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ' ') + 1);
                        $name .= " ".mb_substr($shop->address, 0, mb_strpos($shop->address, ' '));
                        $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ' ') + 1).", ".$name;
                    }
                    
                    $shop->address = str_replace('Золотой_Вавилон','Золотой Вавилон', $shop->address);
                    
                    $base[] = $shop;
                }
            }
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news/index.html";
        $text = str_replace('<p align="left"><strong><font size="2" face="Verdana, Arial, Helvetica, sans-serif"><img src="images/news-7.jpg" width="370" height="161"></font></strong></p>','',$this->httpClient->getUrlText($url));

        preg_match('#(.+)<p align="left"><strong><br>#sui', $text, $text);

        preg_match_all('#<strong>(.+?)(?:</strong></font></p>|</font></strong></p>)(.+?)(?=<strong|$)#sui', $text[1], $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->header = $this->txt($news_value[1]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
    
            $base[] = $news_item;
        }
            
		return $this->saveNewsResult($base);
	}
}
