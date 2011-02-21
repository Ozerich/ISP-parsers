<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_shoes_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.shoes.ru/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }
    private function get_param($text, $param, $cut_html = true)
    {
        preg_match('#<tr bgcolor="\#E2D8C2">\s*<td><strong>'.$param.':*</strong></td>\s*<td>(.+?)</td>\s*</tr>#sui', $text, $ans);
        if($ans)
            return ($cut_html) ? $this->txt($ans[1]) : $ans[1];
        else return "";
    }

    private function parseItem($url)
    {
        $item = new ParserItem();

        preg_match('#id=(\d+)#sui', $url, $id);
        $item->id = $id[1];
        $item->url = $url;
        
        $text = $this->httpClient->getUrlText($item->url);
        preg_match('#<td width="50%" valign="top">\s*<table width="100%" height="100%" cellspacing="1" cellpadding="2" border="0" bgcolor="\#808080">(.+?)</table>#sui', $text, $table);

        preg_match_all('#<tr bgcolor="\#E2D8C2">\s*<td><strong>(.+?)</strong></td>\s*<td>(.+?)</td>\s*</tr>#sui', $table[1], $params, PREG_SET_ORDER);
        $item->descr = "";
        foreach($params as $param)
        {
            if($param[1] == "Кол-во:" || $param[1] == "размеры:")continue;
            if(mb_substr($param[1], mb_strlen($param[1]) - 1, 1) != ':')$param[1].=':';
            $item->descr .= $this->txt($param[1])." ".$this->txt($param[2])."\n";
        }


        $item->brand = $this->get_param($text, "производитель");
        $item->articul = $this->get_param($text, "артикул");
        $item->colors[] = $this->get_param($text, "цвет");
        $item->material = $this->get_param($text, "Материал");
        $item->name = $this->get_param($text, "Модель");
        $item->made_in = $this->get_param($text, "Страна производитель:");
        $price = $this->get_param($text, "Цена");

        $sizes = $this->get_param($text, "размеры", false);
        preg_match_all('#<option value="(.+?)">#sui', $sizes, $sizes);
        $item->sizes = $sizes[1];

        preg_match('#(.+?)\.#sui', $price, $price);
        if($price)$item->price = $price[1];

        preg_match('#<td align="center" bgcolor="ffffff">\s*<img name=".+?" src="(.+?)"#sui', $text, $image);
        if(mb_strpos($image[1], "none_small") === false)
            $item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);

        return $item;
    }
	
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<a href="(index.php\?akus=1&m_id=(\d+)).+?" class="katalog">(.+?)\(#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value[2];
            $collection->url = $this->shopBaseUrl.$collection_value[1];
            $collection->name = $collection_value[3];

            $text = $this->httpClient->getUrlText($this->shopBaseUrl."index.php?akus=9&m=".$collection->id."&p=&z=&r=&sr=&t=&c=&sort=99999");

            preg_match_all('#<td align="center"  bgcolor="ffffff" height="100" valign="middle"><a href="(index.php\?akus=2&id=\d+)#sui', $text, $items);
            foreach($items[1] as $item_value)
                $collection->items[] = $this->parseItem($this->shopBaseUrl.$item_value);
        
            $base[] = $collection;
        }

		
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."index.php?akus=18");

        preg_match_all('#<table width="100%" cellspacing="1" bgcolor="\#808080" cellpadding="2" border="0">(.+?)</table>#sui', $text, $texts);

        $text = $texts[1][0];
        preg_match_all('#<tr bgcolor="E5E4E3">(.+?)</tr>#sui', $text, $trs);
        foreach($trs[1] as $tr)
        {
            preg_match('#<td>(.*?)</td>\s*<td>(.*?)</td>\s*<td>(.*?)</td>#sui', $tr, $info);

            $shop = new ParserPhysical();

            $shop->city = "Москва";
            $shop->address = str_replace('МО, ', '', $this->address($info[2]));
            $shop->phone = $this->txt($info[3]);

            if($shop->address == "" || mb_strpos($shop->address, "Режим") !== false)continue;

            preg_match('#г. (.+?),#sui', $shop->address, $city);
            if($city)
            {
                $shop->city = $city[1];
                $shop->address = str_replace($city[0], '', $shop->address);
            }

            if(mb_strpos($shop->address, "временно не работает") !== false)
                $shop->b_closed = 1;
            
            $shop->address = str_replace('- временно не работает','',$shop->address);
            $shop->phone = str_replace('- временно не работает','',$shop->phone);

            if(mb_substr($shop->address, mb_strlen($shop->address) - 1, 1) == '.')
                $shop->address = mb_substr($shop->address, 0, -1);
            
            $base[] = $shop;
        }

        $text = $texts[1][1];
        preg_match_all('#<tr bgcolor="E5E4E3">(.+?)</tr>#sui', $text, $trs);
        foreach($trs[1] as $tr)
        {
            preg_match('#<td>(.*?)</td>\s*<td>(.*?)</td>\s*<td>(.*?)</td>#sui', $tr, $info);

            $shop = new ParserPhysical();

            $shop->city = $this->txt($info[1]);
            $shop->address = str_replace('м Заельцовская ', '', $this->address($info[2]));
            if($shop->address == "")continue;
            $shop->phone = $this->txt($info[3]);
            
            $base[] = $shop;
        }


		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match_all('#<td class="zag"><a href="(index.php\?akus=4&id=(\d+)).+?"><font color="\#ff0000"><strong>\[(.+?)\] - (.+?)</font></a></td>#sui', $text, $news, PREG_SET_ORDER);


        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->id = $news_value[2];
            $news_item->urlShort = $this->shopBaseUrl;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1];
            $news_item->date = $news_value[3];
            $news_item->header = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[4];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#<table cellspacing="0" cellpadding="2" border="0" align="left">(.+?)</td>#sui', $text, $content);
            $news_item->contentFull = $content[1];
        
            $base[] = $news_item;
        }
        
		return $this->saveNewsResult($base);
	}
}
