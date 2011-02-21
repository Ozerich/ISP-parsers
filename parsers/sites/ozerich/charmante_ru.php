<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_charmante_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.charmante.ru/";

    private function parse_item($id)
    {
                    $item = new ParserItem();



                    $item->id = $id;
                    
                    $item->url = $this->shopBaseUrl."detail.php?ID=".$id;
            
                    $text = $this->httpClient->getUrlText($item->url);
preg_match('#<td valign="top" style="padding-left:30px">\s*<b>(.+?)</b>#sui', $text, $name);
                    $item->name = $this->txt($name[1]);

                    

                    preg_match('#<b>Артикул:</b>(.+?)<br/>#sui', $text, $articul);
                    if($articul)$item->articul = $this->txt($articul[1]);

                    preg_match('#<b>Размерный ряд:</b>(.+?)<br/>#sui', $text, $sizes);
                    if($sizes)
                    {
                        $t = $this->txt($sizes[1]);
                        $sizes = explode(",",$t);
                        foreach($sizes as $size)
                            $item->sizes[] = $this->txt($size);
                    }

                    preg_match('#<b>Цвета:</b>(.+?)<br/>#sui', $text, $colors);
                    if($colors)
                    {
                        $t = $this->txt($colors[1]);
                        $colors = explode(",",$t);
                        foreach($colors as $color)
                        {
                            $color = preg_replace('#\d+#', '', $this->txt($color));
                            preg_match('#\((.+?)\)#sui', $color, $color_);
                            if($color_)$color=$color_[1];
                            $item->colors[] = $color;
                        }
                        
                    }

                    preg_match('#<b>Состав:</b>(.+?)<br/>#sui', $text, $structure);
                    if($structure)$item->structure = str_replace('см. на упаковке','',$this->txt($structure[1]));

                    preg_match('#<span>(.+?)</span>#sui', $text, $descr);
                    if($descr)$item->descr = $this->txt($descr[1]);

                    preg_match('#<table width="100%" border="0" cellspacing="0" cellpadding="0">(.+?)</table>#sui', $text, $image_text);
                    preg_match('#<img src="/(.+?)"#sui', $image_text[1], $image);
                    $image = $this->loadImage($this->shopBaseUrl.$image[1]);
                    $item->images[] = $image;
                    $images_hash = array($image->id);

                    preg_match_all("#document.getElementById\('mainImg'\).src='/(.+?)'#sui", $text, $images);
                    foreach($images[1] as $image)
                    {
                        $image = $this->loadImage($this->shopBaseUrl.$image);
                        if(in_array($image->id, $images_hash))continue;
                        $images_hash[] = $image->id;
                        $item->images[] = $image;
                    }

                    return $item;
    }


        private function parse_child_item($id)
    {
                    $item = new ParserItem();

                    $item->id = $id;
                    $item->url = $this->shopBaseUrl."child/detail.php?ID=".$id;
                    
                    $text = $this->httpClient->getUrlText($item->url);

                    preg_match('#<strong>(.+?)</strong>#sui', $text, $name);
                    if(mb_strpos($name[1], "<br>")!== false)
                        $name[1] = mb_substr($name[1], 0, mb_strpos($name[1], "<br>"));
                    preg_match("#<b>(.+?)</b>#sui", $name[1], $articul);
                    if($articul)
                    {
                        $item->articul = $articul[1];
                        $name[1] = str_replace($articul[0], '',$name[1]);
                    }
                    $item->name = $this->txt($name[1]);

                    preg_match('#Артикул:(.+?)<br />#sui', $text, $articul);
                    if($articul)$item->articul = $this->txt($articul[1]);

                    preg_match('#Размер:(.+?)<br />#sui', $text, $sizes);
                    if($sizes)
                    {
                        $t = $this->txt($sizes[1]);
                        $sizes = explode(",",$t);
                        foreach($sizes as $size)
                            $item->sizes[] = $this->txt($size);
                    }

                    preg_match('#Цвет:(.+?)<br />#sui', $text, $colors);
                    if($colors)
                    {
                        $t = $this->txt($colors[1]);
                        $colors = explode(",",$t);
                        foreach($colors as $color)
                        {
                            $color = preg_replace('#\d+#', '', $this->txt($color));
                            preg_match('#\((.+?)\)#sui', $color, $color_);
                            if($color_)$color=$color_[1];
                            $item->colors[] = $color;
                        }
                        
                    }

                    preg_match('#Состав:(.+?)<br(?: /)*>#sui', $text, $structure);
                    if($structure)$item->structure = str_replace('см. на упаковке','',$this->txt($structure[1]));

                    preg_match('#<div align="justify">(.+?)</div>#sui', $text, $descr);
                    if(!$descr)preg_match('#<p>(.+?)</p>#sui', $text, $descr);
                    if($descr)$item->descr = $this->txt($descr[1]);


                    preg_match('#<div class="detail_card">(.+?)<span#sui', $text, $image_text);
                    preg_match('#<img src="/(.+?)"#sui', $image_text[1], $image);
                    $image = $this->loadImage($this->shopBaseUrl.$image[1]);
                    $item->images[] = $image;
                    $images_hash = array($image->id);

                    preg_match_all("#document.getElementById\('mainImg'\).src='/(.+?)'#sui", $text, $images);
                    foreach($images[1] as $image)
                    {
                        $image = $this->loadImage($this->shopBaseUrl.$image);
                        if(in_array($image->id, $images_hash))continue;
                        $images_hash[] = $image->id;
                        $item->images[] = $image;
                    }

                    $item->name = trim(str_replace($item->articul, '',$item->name));
             //   print_r($item);exit();
                    return $item;
    }


    private function parse_items($url)
    {
        $result = array();
        $text = $this->httpClient->getUrlText($url);

        preg_match_all("#<option value='(\d+)'>#sui", $text, $items);
        foreach($items[1] as $id)
            $result[] = $this->parse_item($id);

        return $result;
    }


    private function parse_child_items($url)
    {
        $result = array();
        $text = $this->httpClient->getUrlText($url);

        preg_match_all("#<option value='(\d+)'>#sui", $text, $items);
        foreach($items[1] as $id)
            $result[] = $this->parse_child_item($id);

        return $result;
    }

    
	public function loadItems () 
	{
       // $this->parse_child_item(7022);
        $base = array();

        $rasdels = array(array("id"=>1,"name"=>"Купальники"),
                            array("id"=>2,"name"=>"Колготки"),
                            array("id"=>3,"name"=>"Носки"),
                            array("id"=>4,"name"=>"Белье"));
        $collections = array();
        
        foreach($rasdels as $rasdel_value)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog.php?GROUP_ID=".$rasdel_value['id']);
            if($rasdel_value['id'] == 1)
            {
                preg_match_all('#<td><nobr> <a\s*href="(\?GROUP_ID=1&COLLECTION_ID=\d+)"#sui', $text, $categories);
                foreach($categories[1] as $url)
                {
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog.php".$url);
                    preg_match('#<div class="menu2" style="margin-bottom: 0px;">(.+?)(?=<a)(.+?)</div>#sui', $text, $text);
                    $collections[$this->txt($text[1])][] = $this->shopBaseUrl."catalog.php".$url;
                    preg_match_all('#href="(.+?)">(.+?)</a>#sui', $text[2], $categories, PREG_SET_ORDER);
                    foreach($categories as $category)
                        $collections[$this->txt($category[2])][] = $this->shopBaseUrl."catalog.php".$category[1];
                }
            }
            else if($rasdel_value['id'] == 2 || $rasdel_value['id']==4)
            {
                $text = $this->httpClient->getUrlText($this->shopBaseUrl."stockings.php?GROUP_ID=".$rasdel_value['id']);
                preg_match_all('#<td align="center" style="padding-top:20px">\s*<a href="(.+?)"#sui', $text, $urls);
                foreach($urls[1] as $url)
                    $collections[$rasdel_value['name']][] = $this->shopBaseUrl."catalog.php".$url;
            }
            else if($rasdel_value['id'] == 3)
            {
                preg_match_all('#<td><nobr> <a\s*href="(.+?)"#sui', $text, $urls);
                $collections['Носки мужские'][] = $this->shopBaseUrl."catalog.php".$urls[1][0];
                $collections['Носки женские'][] = $this->shopBaseUrl."catalog.php".$urls[1][1];

            }

        }

        $rasdels = array(array("id"=>11,"name"=>"Купальные костюмы"),
                            array("id"=>12,"name"=>"Хлопковое белье для девочек"),
                            array("id"=>5681,"name"=>"Хлопковое белье для мальчиков"),
                            array("id"=>10,"name"=>"Детские колготки"),
                            array("id"=>14,"name"=>"Детские носки"));
        $child_collections = array();
        foreach($rasdels as $rasdel_value)
        {
            $text = $this->httpClient->getUrlText($this->shopBaseUrl."child/catalog.php?GROUP_ID=".$rasdel_value['id']);
            if($rasdel_value['id'] == 11)
            {
                preg_match_all('#<li style="white-space:nowrap"><a href="(.+?)"#sui', $text, $urls);
                foreach($urls[1] as $url)
                {
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl."catalog.php".$url);

                    preg_match('#<div class="menu2" style="margin-bottom: 0px;">(.+?)(?=<a)(.+?)</div>#sui', $text, $text);
                    $child_collections[$this->txt($text[1])][] = $this->shopBaseUrl."catalog.php".$url;
                    preg_match_all('#href="(.+?)">(.+?)</a>#sui', $text[2], $categories, PREG_SET_ORDER);
                    foreach($categories as $category)
                        $child_collections[$this->txt($category[2])][] = $this->shopBaseUrl."catalog.php".$category[1];
                    
                }
            }
            else 
            {
                preg_match_all('#<li style="white-space:nowrap"><a href="(.+?)"#sui', $text, $categories);
                foreach($categories[1] as $url)
                    $child_collections[$this->txt($rasdel_value['name'])][]=$this->shopBaseUrl."catalog.php".$url;
            }
        }

        
        foreach($collections as $collecion_name=>$urls)
        {
            $collection = new ParserCollection();
            $collection->name = $collecion_name;

            foreach($urls as $url)
            {
               $items = $this->parse_items($url);
               foreach($items as $item)
                    $collection->items[] = $item;
            }
        
            $base[] = $collection;
        }

        foreach($child_collections as $collecion_name=>$urls)
        {
            $collection = new ParserCollection();
            $collection->name = $collecion_name;

            foreach($urls as $url)
            {
                $items = $this->parse_child_items($url);
                foreach($items as $item)
                    $collection->items[] = $item;
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
