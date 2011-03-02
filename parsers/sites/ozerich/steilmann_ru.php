<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_steilmann_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.steilmann.ru/";
	
	public function loadItems () 
	{
        return null;
	}
	
	public function loadPhysicalPoints () 
	{
        return null;
	}
	
	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."Novosti/";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<div class="part_box fornews">\s*<h2><a href="(http://steilmann.ru/Novosti/(.+?)/)">(.+?)</a></h2>\s*<span class="datenews">(.*?)</span>\s*<p>(.+?)</p>\s*</div>#sui', $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = str_replace('%E2%80','%E2%80%93',$this->urlencode_partial($news_value[1]));
            $news_item->header = $this->txt($news_value[3]);
            $news_item->date = $news_value[4];

            if($news_item->date != "")
            {
                $news_item->date = mb_substr($news_item->date, mb_strpos($news_item->date, ",") + 1);
                $news_item->date = str_replace("г.","",$news_item->date);
                $news_item->date = $this->date_to_str($this->txt($news_item->date));
            }
            
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div class="content">(.+?)</div>#sui', $text, $content);

            $news_item->contentFull = $content[1];
        
            $base[] = $news_item;
        }
        
      
		return $this->saveNewsResult($base);
	}
}
