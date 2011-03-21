<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_laurenvidal_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.laurenvidal.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."sale.htm");
        preg_match('#<td id="id1">(.+?)</td>#sui', $text, $text);
        preg_match_all('#<strong>(.+?)</strong>(.+?)(?:<p>|$)#sui',
                        $text[1], $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $shop->address = $this->txt($shop_value[2]);
            $shop->city = 'Москва';
            preg_match('#тел\.:([\s\d\(\)\-\+]+)#sui', $shop->address, $phone);
            if($phone)
            {
                $shop->phone = $this->txt($phone[1]);
                $shop->address = mb_substr($shop->address, 0, mb_strpos($shop->address, 'тел.:'));
            }
            $shop->address = $this->address($shop->address);
            $shop->address .= ', '.$this->txt($shop_value[1]);

            $base[] = $shop;
        }

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."sale2.htm");
        preg_match_all('#<strong>(.+?)</strong><br>(.+?)<br>(.+?)<br>\s*<br>#sui', $text, $shops, PREG_SET_ORDER);
        foreach($shops as $shop_value)
        {
            $shop = new ParserPhysical();

            $shop->address = $this->txt($shop_value[2]);
            $shop->phone = $this->txt(str_replace(array('тел.:','м. Щукинская'),array('',''),$shop_value[3]));

            preg_match('#.+?,(.+?)$#sui', $shop_value[1], $city_name);
            if($city_name)
                $shop->city = $this->txt($city_name[1]);
            if(mb_strpos($shop_value[1],','))
                $shop_value[1] = mb_substr($shop_value[1], 0, mb_strpos($shop_value[1],','));

            $first = mb_substr($shop->address, 0, mb_strpos($shop->address, ','));
            if(mb_strpos($first, ' ') === false)
            {
                $shop->city = $this->txt($first);
                $shop->address = mb_substr($shop->address, mb_strpos($shop->address, ',') + 1);
            }
            if($shop->city == '')
                $shop->city = 'Москва';

            $shop->address .= ', '.str_replace('("Фрау-Мода")','',$shop_value[1]);

            $base[] = $shop;
        }


		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
        $url = $this->shopBaseUrl."news/indexnews.htm";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<font style="font-size:11px; font-weight:bold; font-family:Arial, Helvetica, sans-serif; color:\#FFFFFF"><br>(.+?)</font></strong>(.+?) <a href="(.+?)"#sui',
            $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->header = $this->txt($news_value[1]);
            $news_item->urlShort = $url;
            $news_item->id = (mb_strpos($news_value[3],'.') !== false) ? mb_substr($news_value[3], 0, mb_strpos($news_value[3],'.')): $news_value[3];
            $news_item->urlFull = $this->shopBaseUrl."news/".$news_value[3];
            $news_item->contentShort = $news_value[2];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<font color="\#FFFFFF" size="2" face="Arial, Helvetica, sans-serif">(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

		return $this->saveNewsResult ($base); 
	}
}
