<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_consul_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = "http://www.consul.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);
    }

    private function parse_value($name, $text)
    {
        preg_match('#<td class=punktir valign=bottom><span class=zagolparam>'.$name.'\s*:</span></td>.+?<span>(.+?)</span>#sui',
            $text, $result);
        return ($result) ? $result[1] : "";
    }

    private function parse_item($url)
    {
        $item = new ParserItem();

        $item->url = $url;
        $item->id = mb_substr($url, mb_strrpos($item->url,'-') + 1, -5);

        $text = $this->httpClient->getUrlText($item->url);

        preg_match('#<td colspan=2><img src="/(.+?)"#sui', $text, $image);
        $image = $this->loadImage($this->shopBaseUrl.$image[1]);
        if($image)$item->images[] = $image;

        $item->brand = str_replace('Ювелирные украшения','',$this->parse_value('Производитель',$text));
        $item->articul = $this->parse_value('Артикул',$text);
        $item->weight = $this->parse_value('Вес изделия',$text);
        $item->descr = $this->parse_value('Описание',$text);
        $color = $this->parse_value('Цвет циферблата',$text);
        if($color != "")$item->colors[] = $color;

        preg_match('#<td class=navmenunoactive height="20">(.+?)</td>#sui', $text, $path);
        $path = $this->txt($path[1]);
        $path = mb_substr($path, mb_strpos($path, '/') + 1);
        $item->categ = explode('/', $path);

        preg_match('#<td style="padding-left: 20px;" valign=top>\s*<table border="0" cellpadding="0" cellspacing="0" width="100%">(.+?)</table>#sui',
            $text, $descr);
        if($item->descr == "")
           $item->descr = $this->txt(str_replace('</tr>',"\n",$descr[1]));
        if(mb_strpos($item->descr, "Калибр") !== false)
           $item->descr = mb_substr($item->descr, mb_strpos($item->descr, "Калибр"));

        preg_match('#<td class=zagol_razdel>(.+?)</td>#sui', $text, $name);
        if($name)$item->name = $this->txt($name[1]);

        

        return $item;
    }

	public function loadItems () 
	{
        $this->shopBaseUrl = "http://www.consul-catalog.ru/";
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<select name="cat" style="width: 100%" class=findinput>(.+?)</select>#sui', $text, $text);

        preg_match_all("#<option value=\"(\d+)\">(.+?)\n#sui", $text[1], $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->name = $this->txt($collection_value[2]);
            if($collection->name != "Ювелирные украшения")
                $collection->name = "Часы ".$collection->name;
            $collection->id = $collection_value[1];
            if($collection->id == 0)continue;
            $collection->url = $this->shopBaseUrl."catalog/".$collection->id.".html";



            $text = $this->httpClient->getUrlText($collection->url);
            preg_match_all('#<td><a href="/(catalog/\d+.html)" class="catalogmenu"#sui', $text, $pages);
            foreach($pages[1] as $url)
            {
                $url = mb_substr($url, 0, -5)."-snASC-mall.html";
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$url);

                preg_match_all('#<td><a href="/(catalog/[\d-]+?\.html)" class=podrobnee#sui', $text, $items);
                foreach($items[1] as $url)
                {
                    $item = $this->parse_item($this->shopBaseUrl.$url);
                    if($item)$collection->items[] = $item;
                }
            }
            $base[] = $collection;
        }



		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        $this->shopBaseUrl = "http://www.consul.ru/";
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."shops.asp");
        preg_match_all('#href=(shops.asp\?Id_Member=\d+)>(.+?)</a>#sui', $text, $shops, PREG_SET_ORDER);

        foreach($shops as $shop_value)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$shop_value[1]);

            $shop = new ParserPhysical();

            $shop->address = $this->txt($shop_value[2]);

            preg_match('#<b class="caption">(.+?)</b>#sui', $text, $caption);
            $caption = $this->txt($caption[1]);
            $shop->city = mb_substr($caption, 0, mb_strpos($caption, ','));
            $caption = mb_substr($caption, mb_strpos($caption, ',') + 1);

            $caption = str_replace($shop->address, '', $caption);
            $shop->phone = $this->address($caption);

            $shop->address = $this->fix_address($shop->address);

            preg_match('#г\.(.+?),#sui', $shop->address, $city);
            if($city)
            {
                $shop->city = $this->txt($city[1]);
                $shop->address = str_replace($city[0], '', $shop->address);
            }

            preg_match("#<b>Часы работы: </b>(.+?)<b>#sui", $text, $timetable);
            if($timetable)
                $shop->timetable = $this->txt($timetable[1]);

            $base[] = $shop;
        }

   		return $this->savePhysicalResult ($base);
	}
	
	public function loadNews ()
	{
        $this->shopBaseUrl = "http://www.consul.ru/";
		$base = array();

        $url = $this->shopBaseUrl."news.asp";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all('#<div class="news">\s*<b>(.+?)</b>.+?<b>(.+?)</b>&nbsp;\s*<a href=\'(.+?)\'#sui',
            $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[3];
            $news_item->id = mb_substr($news_value[3], mb_strrpos($news_value[3], '=') + 1);

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div style="margin-left:20px;margin-right:20px;margin-top:20px;">(.+?)</div>#sui', $text, $content);
            $news_item->contentFull = $content[1];

            $base[] = $news_item;
        }

        $url = $this->shopBaseUrl."discount.asp";
        $text = $this->httpClient->getUrlText($url);
        preg_match_all('#<TD vAlign=top>\s*<P>(.+?)<STRONG>(.+?)</STRONG>\s*</P>\s*<P><A href="(.+?)">#sui', $text, $news, PREG_SET_ORDER);

        $ids = 1;
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt($news_value[1]);
            $news_item->header = $this->txt($news_value[2]);
            $news_item->contentShort = $news_value[2];
            $news_item->urlShort = $url;
            $news_item->urlFull = $this->txt($news_value[3]);

            preg_match('#Id_news=(\d+)#sui', $news_item->urlFull, $id);
            $news_item->id = $id ? $id[1] : $ids++;

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<div style="margin-left:20px;margin-right:20px;margin-top:20px;">(.+?)</div>#sui', $text, $content);
            if($content)
                $news_item->contentFull = $content[1];
            

            $base[] = $news_item;
        }
        

        return $this->saveNewsResult($base);
	}
}
