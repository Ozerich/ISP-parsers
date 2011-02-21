<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_wlogic_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.wlogic.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops/");
        preg_match_all('#h3>(.+?)</h3><br>(.+?)</table>#sui', $text, $items, PREG_SET_ORDER);
        foreach($items as $item)
        {
            $shop_name = $item[1];
            preg_match_all('#<td>(.+?)</td>#sui', $item[2], $info);

            $shop = new ParserPhysical();

            $shop->city = "Москва";
            $shop->address = $info[1][5].", ".$shop_name;
            $shop->timetable = $info[1][7];
            $shop->phone = $info[1][3];
            
            $base[] = $shop;
        }
		
		return $this->savePhysicalResult ($base); 
	}

    private function prepare_date($text)
    {
        $month_names = array("янв","фев","мар","апр","май","июн","июл","авг","сен","окт","ноя","дек");
        preg_match('#(.+?)\s(.+?),(.+?)$#sui', $text, $item);
        $month = $item[1];
        $year = $item[3];
        $day = $item[2];
        for($i = 1; $i <= 12; $i++)
            if($month_names[$i - 1] == $month)
            {
                $month = $i;
                break;
            }
        return $day.".".$month.".".$year;
    }
	
	public function loadNews ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<div class="journal-entry-text">(.+?)<div class="clearer"></div>#sui', $text, $items);

        foreach($items[1] as $item)
        {
            preg_match('#<a class="journal-entry-navigation-current" href="/(.+?)">(.+?)</a>.+?<span class="posted-on">.+?>(.+?)</span>.+?<div class="body">(.+?)</div>#sui', $item, $item);
            $news = new ParserNews();

            $news->urlShort = $this->shopBaseUrl;
            $news->urlFull = $this->shopBaseUrl.$item[1];
            $news->header = $this->txt($item[2]);
            $news->contentShort = $item[4];
            $news->date = $this->prepare_date($item[3]);
            $news->id = mb_substr($news->urlFull, mb_strrpos($news->urlFull, '/') + 1, mb_strrpos($news->urlFull, '.') - mb_strrpos($news->urlFull, '/') - 1);

            $text = $this->httpClient->getUrlText($news->urlFull);
            preg_match('#<div class="body">(.+?)</div>#sui', $text, $text);
            $news->contentFull = $text[1];
            
            $base[] = $news;
        }
		
		return $this->saveNewsResult($base);
	}
}
