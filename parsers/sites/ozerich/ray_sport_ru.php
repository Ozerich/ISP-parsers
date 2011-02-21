
<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_ray_sport_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.ray-sport.ru/";

    private function parseItemsPage($url, $category, $id, $item_name, $collection_id)
    {
        //if($url == "http://www.ray-sport.ru/rus/catalog/110/111/1283.html")return array();
       // $url = "http://www.ray-sport.ru/rus/catalog/224/1057.html";
       //$url = "http://www.ray-sport.ru/rus/catalog/117/144/286.html";
        $text = $this->httpClient->getUrlText($url);

        preg_match('#<div class="good_img">\s*<a href="/(.+?)"#si', $text, $image_url);
        if(!$image_url)return array();
        $image = new ParserImage();
        $image->url = $this->urlencode_partial($this->shopBaseUrl.$image_url[1]);
        if(mb_strpos($image->url, "Foto-ne") !== false)$image = null;
        else
        { 
        $this->httpClient->getUrlBinary($image->url);
        $image->path = $this->httpClient->getLastCacheFile();
        $image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, -4);
        $image->type = "jpg";
    }
        preg_match('#<h3 class="h3_good">Описание</h3>(.+?)\s+<br><br>\s+#si', $text, $descr_text);
        $descr_text = $descr_text[1];

        $count = 0;
        preg_match_all('#<span style="font-weight: bold;">(.+?)</span>#si', $descr_text, $items);
      //  print_r($items);
        foreach($items[1] as $item_)
            if(mb_strlen($this->txt($item_)) > 2 && mb_strpos($this->txt($item_), "Цена") === false)
                $count++;

            $new_price = $old_price = '';

            preg_match('#<SPAN style="FONT-WEIGHT: bold; FONT-SIZE: 10pt; TEXT-DECORATION: line-through">Цена:(.+?) р#si', $descr_text, $price);
            if(!$price)preg_match('#<SPAN style="FONT-SIZE: 10pt; TEXT-DECORATION: line-through">Цена:(.+?) р#si', $descr_text, $price);
            if(!$price)preg_match('#<SPAN style="TEXT-DECORATION: line-through">Цена:(.+?) р#si', $descr_text, $price);
            if($price)$old_price = $this->txt($price[1]);

            $price = null;

            preg_match('#<SPAN style="FONT-WEIGHT: bold; FONT-SIZE: 12pt">Цена:(.+?) р#si', $descr_text, $price);
            if(!$price)preg_match('#<SPAN style="FONT-SIZE: 12pt">(?:<STRONG>)*Цена:(.+?) р#si', $descr_text, $price);
            if($price)$new_price = $this->txt($price[1]);

            $price = null;

            if(!$new_price)
            {
                preg_match('#<span style="(?:font-size: 10pt; )*font-weight: bold;">Цена:(.+?) р#si', $descr_text, $price);
                if(!$price)preg_match('#<span style="font-weight: bold; font-size: 14pt;">Цена(.+?) р#si', $descr_text, $price);
                if(!$price)preg_match('#<STRONG>Цена:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<p style="font-family: Tahoma;"><span style="font-weight: bold; font-size: 10pt;">Цена:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<SPAN style="FONT-WEIGHT: bold; FONT-SIZE: 12pt">Ценa:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<SPAN style="FONT-WEIGHT: bold; FONT-SIZE: 10pt; FONT-FAMILY: Tahoma">Цена:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<span style="font-weight: bold; font-family: Tahoma; font-size: 10pt;">Це</span><span style="font-weight: bold; font-family: Tahoma; font-size: 10pt;"></span><span style="font-weight: bold; font-family: Tahoma; font-size: 10pt;">на:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<span style="font-size: 10pt; color: rgb\(0, 0, 0\); font-family: Arial;">Цена:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<span style="font-weight: bold;"><br>Цена:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<SPAN style="FONT-SIZE: 10pt">Цена: </SPAN>(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<SPAN style="COLOR: \#000000">Цена:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<span style="font-size: 10pt; color: black; font-family: \'Tahoma\',\'sans-serif\'; font-weight: bold;">Цена:(.+?)р#si', $descr_text, $price);
                if(!$price)preg_match('#<span style="font-weight: bold; font-size: 14pt;">Цена</span></span><span style="font-family: Tahoma;"><span style="font-weight: bold; font-size: 14pt;" lang="en-US">(.+?)</span>#si', $descr_text, $price);


                if($price)$new_price = $this->txt($price[1]);
            }


            $old_price = str_replace(' ','',$old_price);
            $new_price = str_replace(' ','',$new_price);

            if($old_price && $new_price)$price_count = 2;
            elseif($old_price || $new_price)$price_count = 1;
            else $price_count = 0;

        if((mb_substr_count($descr_text, "Цен") == $price_count) || mb_strpos($descr_text, "Цен") === false)
        {
            $item = new ParserItem();

            $item->url = $url;
            $item->id = $id;
            $item->categ = $category;
            preg_match('#<h1 class="title">(.+?)</h1>#sui', $text, $name);
            $item->name = $name[1];


            if($old_price == '')$item->price = $new_price;
            else
            {
                $item->price = $old_price;
                $item->discount = $this->discount($old_price, $new_price);
            }

            
        
            preg_match('#Материал:(.+?)<#sui', $descr_text, $material);
            if($material)$item->material = $this->txt($material[1]);

            preg_match('#Размер(?:ы)*:(.+?)<b#sui', $descr_text, $sizes);
            if($sizes)
            {
                $sizes = $this->txt($sizes[1]);
                if(mb_strpos($sizes, "см") === false && mb_strpos($sizes, "высота") === false)
                {
                    if(mb_strpos($sizes, ','))
                        $item->sizes = explode(',',$sizes);
                    else
                        $item->sizes[] = $sizes;
                }
            }
            preg_match('#Цвет:(.+?)<#sui', $descr_text, $color);
            if($color)
            {
                $color = str_replace('.','',$this->txt($color[1]));
                if(mb_strpos($color, ','))
                    $item->colors = explode(',',$color);
                else
                    $item->colors[] = $color;
            }
            
            
            $item->descr = $this->txt($descr_text);

            if($image)$item->images[] = $image;

            $item->descr = str_replace('st1\:*{behavior:url(#ieooui) }', ' ',$item->descr);

            $end_pos = mb_strpos($item->descr, "\n");
            if(mb_strpos($item->descr, ",") !== false && mb_strpos($item->descr,",") < $end_pos)$end_pos = mb_strpos($item->descr, ",");
            if(mb_strpos($item->descr, ".") !== false && mb_strpos($item->descr,".") < $end_pos)$end_pos = mb_strpos($item->descr, ".");
            if(mb_strpos($item->descr, ":") !== false && mb_strpos($item->descr,":") < $end_pos)$end_pos = mb_strpos($item->descr, ":");
            if($collection_id != 160 && $collection_id != 191 && $collection_id != 224)//$item->name = mb_substr($item->descr, 0, $end_pos);
            $item->name = $item_name;
            return array($item);
        }
        else
        {
            preg_match_all('#<span style="font-weight: bold;">(.+?)</span>(.+?)Цена:(.+?)р#si', $descr_text, $items, PREG_SET_ORDER);
            if(!$items)preg_match_all('#<SPAN style="FONT-SIZE: 10pt">(.+?)<BR>(.+?)Цена:(.+?)р#si', $descr_text, $items,PREG_SET_ORDER);
          //  print_r($url);
           // print_r($items);
           $count = 1;
           $result = array();
           foreach($items as $item_value)
           {
               $descr_text = $item_value[2];
               $item = new ParserItem;

               $item->id = $id."_".$count++;
               $item->name = $this->txt($item_value[1]);
               $item->descr = $this->txt($item_value[2]);
               $item->price = str_replace(array(' ',',00','.00'),array('','',''),$this->txt($item_value[3]));
               $item->categ = $category;
               if($image)$item->images[] = $image;
               $item->url = $url;

            preg_match('#Материал:(.+?)(?:<br>|Наполнение)#sui', $descr_text, $material);
            if($material)$item->material = $this->txt($material[1]);

            preg_match('#Размер(?:ы)*:(.+?)<b#sui', $descr_text, $sizes);
            if($sizes)
            {
                $sizes = $this->txt($sizes[1]);
                if(mb_strpos($sizes, "см") === false && mb_strpos($sizes, "высота") === false)
                {
                    if(mb_strpos($sizes, ','))
                        $item->sizes = explode(',',$sizes);
                    else
                        $item->sizes[] = $sizes;
                }
            }
            preg_match('#Цвет:(.+?)<#sui', $descr_text, $color);
            if($color)
            {
                $color = str_replace('.','',$this->txt($color[1]));
                if(mb_strpos($color, ','))
                    $item->colors = explode(',',$color);
                else
                    $item->colors[] = $color;
            }

            if($collection_id == 121)
           {
                  
                    $item->name = mb_substr($item->descr, 0, mb_strpos($item->descr, "\n"));
                    if(mb_strpos($item->name, "Манекен") === false)
                    {
                        $temp = $item->descr;
                        $temp = mb_substr($temp, mb_strpos($temp, "\n") + 1);
                        $item->name = mb_substr($temp, 0,mb_strpos($temp, "\n"));
                    }
           }
           else
                if($collection_id != 191 && $collection_id != 121)
                    $item->name = $item_name;
               $result[] = $item;
           }
           return $result;
            
        }

    }
	
	public function loadItems () 
	{
        $base = array();

        $text = $this->httpClient->getUrlBinary($this->shopBaseUrl."rus/catalog/");
        
        preg_match('#<ul id="catalog_menu">(.+)#si', $text, $text);
        preg_match_all('#<li class="level1_n">\s*<a href="/(rus/catalog/(\d+))">(.+?)</a>#si', $text[1], $collections, PREG_SET_ORDER);

        foreach($collections as $collection_item)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_item[2];
            $collection->url = $this->shopBaseUrl.$collection_item[1];
            $collection->name = $collection_item[3];

            $text = $this->httpClient->getUrlText($collection->url);
            preg_match('#<table width="100%" cellspacing="10" id="cat_tab"><col width="33%" />(.+?)</table>#si', $text, $text);
            if($text)
                preg_match_all('#<div>\s*<a href="/(.+?)" class="cat_item_title">(.+?)</a>\s*</div>#si', $text[1], $categories,PREG_SET_ORDER);
            else
                $categories = array(array("1"=>$collection_item[1],"2"=>""));
            foreach($categories as $category)
            {
                $category_name = $category[2];
                $text = $this->httpClient->getUrlText($this->shopBaseUrl.$category[1]);
                preg_match('#<table width="100%" cellspacing="10" id="cat_tab"><col width="33%" />(.+?)</table>\s*<script>#si', $text, $text);
                if($text)
                    preg_match_all('#<div>\s*<a href="/(.+?)" class="cat_item_title">(.+?)</a>\s*</div>#si', $text[1], $sub_categories,PREG_SET_ORDER);
                else
                    $sub_categories = array(array("1"=>$category[1],"2"=>""));
                foreach($sub_categories as $sub_category)
                {
                    $sub_category_name = $sub_category[2];
                    $text = $this->httpClient->getUrlText($this->shopBaseUrl.$sub_category[1]);
                    $category = array();
                    if($category_name != '')$category[] = $category_name;
                    if($sub_category_name != '')$category[] = $sub_category_name;

                    preg_match_all('#<span class="cat_title">\s*<a href="/(.+?)">(.+?)</a>\s*</span>.+?<span class="content_txt13">(.+?)</span>#si', $text, $items, PREG_SET_ORDER);

                    foreach($items as $item_value)
                    {
                        if($item_value[2] == "ВНИМАНИЕ!")continue;
                        $url = $this->shopBaseUrl.$item_value[1];
                        $id =  mb_substr($url, mb_strrpos($url, '/') + 1, mb_strrpos($url,'.')-mb_strrpos($url,'/') - 1);
                        $page_items = $this->parseItemsPage($url,$category,$id, $this->txt($item_value[3]), $collection->id);
                        foreach($page_items as $item)
                            $collection->items[] = $item;
                    }
                    
                }
            }
            
            $base[] = $collection;
        }
        
        return $this->saveItemsResult($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."rus/buy/");
        preg_match_all('#<td style="border: 1px solid black;">(.+?)</td>#sui', $text, $items);

        $phones = array();
        
        foreach($items[1] as $shop_text)
        {
            preg_match('#<p>(.+?)</p>.+?РЕЖИМ РАБОТЫ МАГАЗИНА:<br>(.+?)</p>.+?Телефон:(.+?)</p>#sui', $shop_text, $info);

            $shop = new ParserPhysical();

            $shop->address = $this->txt($info[1]);
            $shop->timetable = $this->txt($info[2]);
            $shop->phone = $this->txt($info[3]);

            $shop->address = str_replace('г.', '',$shop->address);
            $shop->city = mb_substr($shop->address, 0, mb_strpos($shop->address, ','));
            $shop->city = str_replace('Московской обл.', '', $shop->city);
            $shop->address = $this->address(mb_substr($shop->address, mb_strpos($shop->address, ',') + 1));

            $phone =str_replace('-','',$shop->phone);
            if(in_array($phone, $phones))continue;
            $phones[] = $phone;
            
            $base[] = $shop;
        }

        preg_match('#<table style="margin-left: 2px; width: 100%;" border="1">(.+?)</tbody></table></div>#sui', $text, $text);
        preg_match_all('#<tr>(.+?)</tr>#sui', $text[1], $trs);
        foreach($trs[1] as $text)
        {
            if(mb_strpos($text, 'colspan="3"') !== false)continue;
            preg_match_all('#<td.*?>(.+?)</td>#sui', $text, $tds);

            $shop = new ParserPhysical();

            $shop->address = (count($tds[1]) == 2) ? $this->txt($tds[1][0]) : $this->txt($tds[1][1]);
            if($shop->address == "Адреса" || $shop->address == '-')continue;
            $shop->phone = (count($tds[1]) == 2) ? $this->txt($tds[1][1]) : $this->txt($tds[1][2]);

            $shop->address = str_replace('г.', '',$shop->address);
            $shop->city = mb_substr($shop->address, 0, mb_strpos($shop->address, ','));
            if($shop->city == "")continue;
            $shop->address = $this->address(mb_substr($shop->address, mb_strpos($shop->address, ',') + 1));
            $shop->address = str_replace('()','',$shop->address);
            if(mb_substr($shop->address, mb_strlen($shop->address) - 1) == '(')
                $shop->address = mb_substr($shop->address, 0, -1);

            $phone =str_replace('-','',$shop->phone);
            if(in_array($phone, $phones))continue;
            
            $base[] = $shop;
        }
		
		return $this->savePhysicalResult ($base); 
	}

	public function loadNews ()
	{
		$base = array();

        
		return $this->saveNewsResult($base);
	}
}
