<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_oharamania_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.oharamania.ru/";
	
	public function loadItems () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog/");
        preg_match_all('#<td width=".+?" height="\d+" class="text" align="center"><a href="/(catalog/(.+?)/)"><img\s*src=".+?"\s*width="\d+" height="\d+" alt="(.+?)"#sui',
            $text, $collections, PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->id = $collection_value[2];
            $collection->name = $collection_value[3];



            $url = $collection->url;
            while(true)
            {

                $text = $this->httpClient->getUrlText($url);

                $item = new ParserItem();

                $item->url = $url;

                preg_match('#<td width=265><img src=\./(.+?)\salt#sui', $text, $image);
                if($image)
                    $item->images[] = $this->loadImage($collection->url.$image[1]);

                preg_match('#/(.+?)\.jpg#sui', $image[1], $id);
                if($id)
                    $item->id = $id[1];

                preg_match('#<td align=center class=textred2  width=100%><b>(.+?)<b>#sui', $text, $name);
                if($name)
                    $item->name = $this->txt($name[1]);

                preg_match('#Арт. (.+?)<br#sui', $text, $articul);
                if($articul)
                    $item->articul = $this->txt($articul[1]);

                preg_match('#Размеры: (.+?)<br>#sui', $text, $sizes);
                if($sizes)
                    $item->sizes[] = $this->txt($sizes[1]);

                preg_match('#<td align=center class=textred3 width=100%><b> Возможные цвета: <b></td></tr>(.+?)</table>#sui', $text, $color_text);
                if($color_text)
                {
                    preg_match_all('#<td align=center class=textred3 width=100%>(.+?)</td>#sui', $color_text[1], $colors);
                    foreach($colors[1] as $color)
                        if($this->txt($color) != '')
                            $item->colors[] = $this->txt($color);
                }

                preg_match('#<td width="265">&nbsp;<table border="0" width="100%" cellspacing="0" cellpadding="0">(.+?)</table>#sui', $text, $descr);
                if($descr)
                    $item->descr = $this->txt(str_replace('</td></tr>',"\n",$descr[1]));


                $collection->items[] = $item;

                preg_match('#<p align="right">\s*<a class=rlink href="/(.+?)">#sui',$text, $url);

                if(!$url)
                    break;
                else
                    $url = $this->shopBaseUrl.$url[1];

            }
            


            $base[] = $collection;
        }
        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
	{
		return null;
	}
	
	public function loadNews ()
	{
		return null;
	}
}
