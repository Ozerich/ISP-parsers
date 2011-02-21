<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_bornclub_com extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.born-club.com/";
	
	public function loadItems () 
	{
		$base = array ();
	
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $url = $this->shopBaseUrl."detskie-magazini/";

        $text = $this->httpClient->getUrlText($url);

        
        preg_match_all('#<P(?:.*?)>(?:<STRONG>)*<U>(.+?)</U>\s*(?:</STRONG>)*</P>(.+?)</LI></UL>#sui', $text, $cities, PREG_SET_ORDER);
        foreach($cities as $city_item)
        {
            $shops = array();
        
            $city = $this->txt($city_item[1]);
            $text = $city_item[2];

            preg_match_all('#<LI>(.+?)(?:</LI>|<LI>|$)#sui', $text, $shops1, PREG_SET_ORDER);
            if($shops1)
                foreach($shops1 as $shop_value)
					if(strpos($shop_value[1], "<DIV") === false)
						$shops[] = $shop_value;
            preg_match_all('#<DIV align=justify>(.+?)</DIV>#sui', $text, $shops2,PREG_SET_ORDER);
            if($shops2)
                foreach($shops2 as $shop_value)
                    $shops[] = $shop_value;
                
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->city = str_replace("(фото)", "", $city);
                $shop->address = $this->address($shop_value[1]);	
				if($this->address_have_prefix($shop->address))
				{							
					if(mb_strpos($shop->address, '"')!==false)
					{
						$prefix = mb_substr($shop->address, 0, mb_strpos($shop->address, '"'));
						$shop->address = trim(mb_substr($shop->address, mb_strlen($prefix)));

						if(mb_strpos($shop->address, '",'))
							$name = mb_substr($shop->address, 1, mb_strpos($shop->address, '",') - 1);
						else if(mb_strpos($shop->address, '" '))
							$name = mb_substr($shop->address, 1, mb_strpos($shop->address, '" ') - 2);
						$shop->address = trim(mb_substr($shop->address, mb_strlen($name) + 4));
						if($shop->address[0] == ',')
							$shop->address = trim(mb_substr($shop->address, 1));

					}
					else
					{

						$prefix = mb_substr($shop->address, 0, mb_strpos($shop->address, ' '));
						$shop->address = trim(mb_substr($shop->address, mb_strlen($prefix)));

						$name = mb_substr($shop->address, 1, mb_strpos($shop->address, ',') - 1);
						$shop->address = trim(mb_substr($shop->address, mb_strlen($name) + 2));
						
					}

					$shop->address .= ", ".$prefix." ".'"'.$name.'"'; 
					

					
				}				
				if(mb_strpos($shop->address, 'открытие') !== false)
					continue;
				$shop->address = str_replace("(фото)", "",$shop->address);
				if(mb_strpos($shop->address, "+") !== false)
					$shop->phone = mb_substr($shop->address, mb_strpos($shop->address, "+"));
				$shop->phone = str_replace(', ТЦ "осмарт"', "", $shop->phone);
				$shop->address = str_replace($shop->phone, "", $shop->address);
				$shop->address = str_replace(array("т.","тел."), array("",""), $shop->address);
				
                $base[] = $shop;
            }
        }
            
		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl);
		preg_match_all('#<div class="news">\s*<h1>(.+?)</h1>(.+?)
<a href="(http://www.born-club.com/rus/news/(.+?)/)"><img src="http://www.born-club.com/rus/img/img_arrow.gif"#sui', $text, $news, PREG_SET_ORDER);
                

		
		foreach($news as $news_value)
		{
			preg_match('#<STRONG>(.+?)</STRONG>#sui', $news_value[0], $header);
			$news_item = new ParserNews();
			
			$news_item->id = $news_value[4];
			$news_item->urlShort = $this->shopBaseUrl;
			$news_item->urlFull = $news_value[3];
			if($header)
				$news_item->header = $header[1];
			$news_item->date = $news_value[1];
			$news_item->contentShort = $news_value[2];
			
			$text = $this->httpClient->getUrlText($news_item->urlFull);
			
			preg_match('#<\!-- MAIN CONTENT -->(.+?)<\!-- //MAIN CONTENT -->#sui', $text, $text);
			if($text)
				$news_item->contentFull = $text[1];
			
			$base[] = $news_item;
		}
		return $this->saveNewsResult ($base); 
	}
}
