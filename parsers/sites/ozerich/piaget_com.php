<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_piaget_com extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://ru.piaget.com/";


	public function loadItems () 
	{
        $base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl);
        preg_match('#<ul class="lnk">(.+?)</ul>#sui', $text, $text);
        preg_match_all('#<li><a.+?href="/(.+?)">(.+?)</a></li>#sui', $text[1], $collections, PREG_SET_ORDER);
        for($i = 0; $i < count($collections) - 1; $i++)
        {
            $collection = new ParserCollection();

            $collection->id = $collections[$i][1];
            $collection->url = $this->shopBaseUrl.$collections[$i][1];
            $collection->name = $this->txt($collections[$i][1]);

            $text = $this->httpClient->getUrlText($this->urlencode_partial($collection->url));
            preg_match('#<div class="block">(.+?)</div>#sui', $text, $text);
            preg_match_all('#<a href="/(.+?)">(.+?)</a>#sui', $text[1], $categories, PREG_SET_ORDER);
            foreach($categories as $category)
            {
                $text = $this->httpClient->getUrlText($this->urlencode_partial($this->shopBaseUrl.$category[1]));
                $category = $this->txt($category[2]);

                preg_match_all('#<h2>\s*<a href="/(.+?)">(.+?)</a>#sui', $text, $items, PREG_SET_ORDER);
                foreach($items as $item_value)
                {
                    $item = new ParserItem();

                    $item->url = $this->shopBaseUrl.$item_value[1];
                    $item->name = $this->txt($item_value[2]);
                    $item->categ = $category;

                    $text = $this->httpClient->getUrlText($this->urlencode_partial($this->shopBaseUrl.$item_value[1]));

                    preg_match('#<p class="">(.+?)</p>#sui', $text, $descr);
                    $item->descr = $this->txt($descr[1]);

                    preg_match('#<div id="productMedia"(.+?)</div>#sui', $text, $image_text);

                    preg_match_all('#img src="/(.+?)"#sui', $image_text[1], $images);
                    if(!$images[1])
                        preg_match_all('#<div id="image_print_div">\s*<img src="/(.+?)"#sui', $text, $images);
                    foreach($images[1] as $url)
                    {
                        $url = str_replace("thumb2/","",$url);
                        $image = $this->loadImage($this->shopBaseUrl.$url);
                        if($image)
                            $item->images[] = $image;
                    }


                    preg_match('#<h3 class="subtitle">(.+?)</h3>#sui', $text, $structure);
                    $item->structure = $this->txt($structure[1]);

                    preg_match('#<h2 id="reference">(.+?)<#sui', $text, $articul);
                    $articul[1] = $this->txt($articul[1]);
                    $item->articul = $item->id = mb_substr($articul[1], mb_strrpos($articul[1], ' ') + 1);

                    $item->structure = trim(str_replace(array('Ультратонкие часы,','Ультратонкие механические часы,'), array('',''),
                        $item->structure));

                    $collection->items[] = $item;
                }
            }

            $base[] = $collection;
        }

        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->urlencode_partial($this->shopBaseUrl."бутики"));
        preg_match_all('#<h2.*?><a href="/(.+?)">#sui', $text, $shops);
        foreach($shops[1] as $url)
        {
            $text = $this->httpClient->getUrlText($this->urlencode_partial($this->shopBaseUrl.$url));

            preg_match('#<div id="store_infos" class="block">(.+?)<br />#sui', $text, $address);
            preg_match('#Тел\.:(.+?)<br />#sui', $text, $phone);
            preg_match('#Часы работы<br />(.+?)<br />#sui', $text, $timetable);

            $shop = new ParserPhysical();

            $shop->address = $this->fix_address($address[1]);
            $shop->phone = $this->txt($phone[1]);
            $shop->timetable = $this->txt($timetable[1]);
            $shop->city = 'Москва';

            $base[] = $shop;
        }

        return $this->savePhysicalResult($base);
	}
	
	public function loadNews ()
	{
		return null;
	}
}
