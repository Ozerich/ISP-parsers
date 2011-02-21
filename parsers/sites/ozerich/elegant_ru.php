<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_elegant_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.elegant.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match("#new TPopMenu\('магазины'(.+)#sui", $text, $text);
        preg_match_all("#new TPopMenu\('(.+?)','','a','(.+?)',''\);item_21_menu\.Add#sui", $text[1], $cities, PREG_SET_ORDER);

        foreach($cities as $city)
        {
            $city_name = $this->txt($city[1]);
            $text = $this->httpClient->getUrlText($city[2]);
            preg_match('#<div id="content">(.+?)</div>#sui', $text, $text);

            $addresses = array();
            
            preg_match_all('#<TABLE cellSpacing=5 cellPadding=5 border=0>(.+?)</TABLE>#sui', $text[1], $shops);
            foreach($shops[1] as $shop_text)
                if($this->txt($shop_text) != '')
                    $addresses[] = $this->txt(preg_replace('#<br.*?/*>#sui', ',', $shop_text));

    
            preg_match_all('#(.+?)(?:<hr style="width: 100%; color: rgb\(255, 255, 255\); height: 2px;">|<td><br></td></tr></tbody></table>)#sui', $text[1], $shops);
            foreach($shops[1] as $shop_text)
                if($this->txt($shop_text) != '')
                    $addresses[] = $this->txt(preg_replace('#<br.*?/*>#sui', ',', $shop_text));

            preg_match_all('#<TR>\s*<TD style="COLOR: rgb\(255,255,255\)">(.+?)</FONT></TD></TR></TBODY></TABLE>#sui', $text[1], $shops);
            foreach($shops[1] as $shop_text)
                    $addresses[] = $this->txt(preg_replace('#<br.*?/*>#sui', ',', $shop_text));

            preg_match_all('#<p><font style="color: rgb\(255, 255, 255\);" size="2">(?:<strong>)*&nbsp;&nbsp;&nbsp;\s*&nbsp;<font size="2">(.+?)(?:<p><font style="color: rgb\(255, 255, 255\);" size="2">&nbsp;</font></p>|$)#sui', $text[1], $shops);
            foreach($shops[1] as $shop_text)
                if($this->txt($shop_text) != '')
                    $addresses[] = $this->txt(preg_replace('#<br.*?/*>#sui', ',', $shop_text));

            if(!$addresses)
            {
                preg_match_all('#<FONT size=2>(.+?)</FONT>\s*(?:<HR style="WIDTH: 100%; HEIGHT: 2px">|$)#sui', $text[1], $shops);
                foreach($shops[1] as $shop_text)
                    $addresses[] = $this->txt(preg_replace('#<br.*?/*>#sui', ',', $shop_text));
            }

            if(!$addresses)
                $addresses[] = $this->txt(str_replace('</P>',',',$text[1]));
    
            foreach($addresses as $address)
            {
                $shop = new ParserPhysical();

                $shop->city = $city_name;

                if(mb_strpos($address,'СКОРО ОТКРЫТИЕ!') !== false)continue;

                $address = $this->address($address);
                $address = str_replace('СХЕМА ПРОЕЗДА >,>,>','',$address);

                preg_match('#\+\d \(\d+\)[\d\s-]+#sui', $address, $phone);
                if($phone)
                {
                    $shop->phone = $phone[0];
                    $address = str_replace($phone[0],'',$address);
                }
                $address = str_replace('тел.:','',$address);


                preg_match('#режим работы:*(.+?)(?:,|$)#sui', $address, $timetable);
                if($timetable)
                {
                    $shop->timetable = $timetable[1];
                    $address = str_replace($timetable[0], '', $address);
                }

                $address = str_replace(array(', вых.','ЦТиР "МИР"','стр.1/18','ТК "Ашан" Сокольники'),array(' вых.','ЦТиР "МИР",','','ТК "Ашан" Сокольники,'), $address);


                if($shop->city == 'Московская область' || $shop->city == 'Волгоград' || $shop->city == 'Тольятти' || $shop->city == 'Тула' || $shop->city == 'Уфа')
                {
                    $shop->timetable = trim(mb_substr($address, mb_strrpos($address,',') + 1));
                    $address = mb_substr($address, 0, mb_strrpos($address, ','));
                }


                preg_match('#г\.(.+?)(?:,|\s)#sui', $address, $city);
                if($city)
                {
                    $shop->city = trim($city[1]);
                    $address = str_replace($city[0],'',$address);
                }



                $address = $this->address($address);
                $address = $this->fix_address($address);
        
                
                $shop->address = $address;

                if($shop->city == 'Московская область')
                    $shop->city = 'Москва';

                $base[] = $shop;
            }
        }
            

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."akcii";
        $text = $this->httpClient->getUrlText($url);
        $text = $this->delete_comments($text);

        preg_match_all('#<h3.*?>(.+?)</h3>(.+?)(?:<br><br><hr style="width: 100%; height: 2px;"><br><br>|$)#sui', $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->header = $this->txt($news_value[1]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;

            preg_match('#<a href="/(.+?)">Подробнее...</a>#sui', $news_item->contentShort, $full_url);
            if($full_url)
            {
                $news_item->urlFull = $this->shopBaseUrl.$full_url[1];
                $news_item->contentShort = str_replace($full_url[0],'',$news_item->contentShort);
                $text = $this->httpClient->getUrlText($news_item->urlFull);
                preg_match('#<div id="content">(.+?)</div>#sui', $text, $content);
                $news_item->contentFull = $content[1];
            }

            $base[] = $news_item;
        }

        
            
		return $this->saveNewsResult($base);
	}
}
