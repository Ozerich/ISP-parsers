<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_deffinesse_net extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.deffinesse.net/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#<span style="color: \#ffffff">(.+?)</span>#sui', $text, $texts);
        $i = 0;
        while($i < count($texts[1]))
        {
            $text = $texts[1][$i];
            if(mb_strpos($text, ".м.") === false && mb_strpos($text, "г .") === false)
            {
                $i++;
                continue;
            }
            $shop = new ParserPhysical();

            if(mb_strpos($text, "г .") !== false)
            {
                preg_match('#г . (.+?),#sui', $text, $city);
                $shop->city = $city[1];
                $text = str_replace($city[0], '', $text);
            }
            else
                $shop->city = "Москва";

            if(mb_substr_count($text, "ТЦ") == 2)
            {
                $text1 = mb_substr($text, 0, mb_strrpos($text, "ТЦ"));
                $texts[1][] = "г . ".$shop->city.",".mb_substr($text, mb_strrpos($text, "ТЦ"));
                $text = $text1;
            }


            $shop->address = $this->address($text);
            
            $shop->address = str_replace(array('ст.','см.'),array('',''),$shop->address);
            $shop->address = str_replace(' Чкаловская "', '',$this->address($shop->address));

            preg_match('#время работы : (.+?)$#sui', $shop->address, $timetable);
            if($timetable)
            {
                $shop->timetable = $timetable[1];
                $shop->address = str_replace($timetable[0], '', $shop->address);
            }

            preg_match('#(?:тел \. :|тел\.|тел \. |\(тел\. \))([\d\(\)\s-]+)#sui', $shop->address, $phone);
            if($phone)
            {
                $shop->phone = $phone[1];
                $shop->address =str_replace($phone[0], '',$shop->address);
            }

            $shop->address = str_replace(' . ','.',$this->fix_address($shop->address));
            
            $base[] = $shop;
            $i++;
        }

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."news";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<td style="color:\#fff;padding-bottom:10px;text-align:left;">(.+?)</td>#sui', $text, $texts);
        foreach($texts[1] as $text)
        {

            preg_match('#(.+?)&nbsp;<strong>(.+?)</strong><br/>(.+)#sui', $text, $info);

            $news = new ParserNews();

            $news->date = $info[1];
            $news->header = $this->txt($info[2]);
            $news->urlShort = $url;
            $news->contentShort = $info[3];

            $base[] = $news;
        }
            
		return $this->saveNewsResult($base);
	}
}
