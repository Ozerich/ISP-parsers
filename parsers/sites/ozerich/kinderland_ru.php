<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_kinderland_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = "http://www.kinderland.ru/";

    public function __construct($savePath)
    {
        parent::__construct($savePath);
        $this->httpClient->setRequestsPause (0.5);

    }


	public function loadItems ()
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?did=3");
        preg_match_all('#<td class="leftmenu".+?<a href="(\?did=(.+?))">(.+?)</a></td>#sui', $text, $collections,
            PREG_SET_ORDER);

        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->id = $collection_value[2];
            $collection_item->url = $this->shopBaseUrl.$collection_value[1];
            $collection_item->name = $this->txt($collection_value[3]);

            $text = $this->httpClient->getUrlText($collection_item->url);
            preg_match_all("#<p>\s*<div class=newshd.+?><a href='(\?did=.+?)'>(.+?)</a></div>\s*</p>#sui", $text, $categories,
                PREG_SET_ORDER);
            if(!$categories)
                $categories = array(array('1'=>$collection_value[1], '2'=>''));

            foreach($categories as $category_value)
            {
                $category_name = $category_value[2];
                $category_url = $this->shopBaseUrl.$category_value[1];
                $text = $this->httpClient->getUrlText($category_url);

                $offset = 0;
                while($offset < 1000)
                {
                    $text = $this->httpClient->getUrlText($category_url."&i=".$offset);

                    preg_match_all("#<div class=newshdr.+?><a href='(\?did=(.+?))'>(.+?)</a></div>.+?<td class='text'.+?>(.+?)</td>#sui", $text, $items, PREG_SET_ORDER);
                    if(!$items)break;

                    foreach($items as $item_value)
                    {
                        $item = new ParserItem();

                        $item->url = $this->shopBaseUrl.$item_value[1];
                        $item->id = $item_value[2];
                        $item->name = $this->txt($item_value[3]);

                        $descr_text = $this->txt($item_value[4]);
                        preg_match('#Рекомендованная розничная цена:(.+?),#sui', $descr_text, $price);
                        if($price)
                            $item->price = $this->txt(str_replace('.','',$price[1]));

                        preg_match('#Артикул:(.+?)(?:\.|$)#sui', $item->name, $articul);
                        if($articul)
                        {
                            $item->articul = $this->txt($articul[1]);
                            $item->name = str_replace($articul[0], '', $item->name);
                        }

                        preg_match('#Артикул:(.+?)\.#sui', $descr_text, $articul);
                        if($articul)
                            $item->articul = $this->txt($articul[1]);

                        if($category_name != '')
                            $item->categ = $category_name;

                        $text = $this->httpClient->getUrlText($item->url);

                        preg_match("#</div><img src='(.+?)'#sui", $text, $image);
                        $image = $this->loadImage($this->shopBaseUrl.$image[1]);
                        if($image)
                            $item->images[] = $image;

                        preg_match('#Вес, кг.: (.+?)<#sui', $text, $weight);
                        if($weight)
                            $item->weight = $this->txt($weight[1])." кг";

                        preg_match('#<BR><BR><BR>(.+?)<BR><BR><A#sui', $text, $descr);
                        if($descr)
                        {
                            $descr = $descr[1];
                            while(mb_substr($descr, 0, 4) == "<BR>")
                                $descr = mb_substr($descr, 4);

                            $item->descr = $this->txt($descr);
                        }

                        

                        preg_match('#Страна производства: (.+?)\.#sui', $text, $made_in);
                        if($made_in)
                            $item->made_in = $this->txt($made_in[1]);

                        preg_match('#Производитель:(.+?)<#sui', $text, $brand);
                        if($brand)
                            $item->brand = $this->txt($brand[1]);

                        preg_match('#Размеры: (.+?)\.#sui', $text, $sizes);
                        if($sizes)
                            $item->sizes[] = $this->txt($sizes[1]);
                        

                        $collection_item->items[] = $item;
                    }



                    $offset += 10;
                }
                
            }

            if(!$collection_item->items)continue;

            $base[] = $collection_item;
        }

		return $this->saveItemsResult ($base);
	}

	public function loadPhysicalPoints ()
	{
        $this->add_address_prefix('База Орса');
		$base = array ();

        $cities = array(
            array('city'=>'Москва','url'=>'?did=7_33'),
            array('city'=>'Москва','url'=>'?did=7_1005'),
            array('city'=>'Санкт-Петербург','url'=>'?did=7_1046')
        );

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."?did=7_990");
        preg_match_all("#<div class=newshdr.+?><a href='(.+?)'>(.+?)</a></div>#sui", $text, $cities_, PREG_SET_ORDER);
        foreach($cities_ as $city_value)
        {
            $city_item = array();
            $city_item['url'] = $city_value[1];
            $city_item['city'] = $this->txt($city_value[2]);
            if(mb_strpos($city_item['city'], '(') !== false)
                $city_item['city'] = mb_substr($city_item['city'],0,mb_strpos($city_item['city'],'('));
            $cities[] = $city_item;
        }

        foreach($cities as $city)
        {
            $city_name = $city['city'];
            $text = $this->httpClient->getUrlText($this->shopBaseUrl.$city['url']);

            preg_match_all('#<FONT color=(?:blue|\#0000ff)>(.+?)</FONT>(.+?)(?:<BR><BR>|</P>)#sui', $text, $shops, PREG_SET_ORDER);
            foreach($shops as $shop_value)
            {
                $shop = new ParserPhysical();

                $shop->city = $city_name;
                $addr = $this->address($shop_value[2]);

                if(mb_strpos($addr,'Представленные товары:') !== false)
                    $addr = mb_substr($addr, 0, mb_strpos($addr,'Представленные товары:'));
                if(mb_strpos($addr, 'Время работы:') !== false)
                {
                    $shop->timetable = mb_substr($addr, mb_strpos($addr,'Время работы:') + mb_strlen('Время работы:'));
                    $addr = mb_substr($addr, 0, mb_strpos($addr,'Время работы:'));
                }

                preg_match('#\sтел\.*:*(.+?)$#sui', $addr, $phone);
                if($phone && $phone[1] != '')
                {
                    $shop->phone = $this->txt($phone[1]);
                    $addr = str_replace($phone[0], '', $addr);
                }

                preg_match('#\(\d+\)[\d-\s]+$#sui', $addr, $phone);
                if($phone)
                {
                    $shop->phone = $phone[0];
                    $addr = str_replace($phone[0], '', $addr);
                }

                preg_match('#г\.(.+?),#sui', $addr, $city_text);
                if($city_text)
                {
                    $shop->city = $this->txt($city_text[1]);
                    $addr = str_replace($city_text[0], '', $addr);
                }

                if(mb_strpos($shop->city, '(') !== false)
                    $shop->city = mb_substr($shop->city, 0, mb_strpos($shop->city, '('));

                if(mb_strpos($addr, ',') !== false)
                {
                    while(true)
                    {
                        $first = mb_substr($addr, 0, mb_strpos($addr, ','));
                        if(mb_strpos($first, 'обл') !== false || mb_strpos($first, 'р-н') !== false)
                            $addr = mb_substr($addr, mb_strpos($addr, ',') + 1);
                        else
                            break;
                    }
                }

                if(mb_substr($addr, 0, 2) == 'ИП')
                    $addr = mb_substr($addr, mb_strpos($addr, '. ') + 2);

                $addr = str_replace('Митино,','',$addr);

                if($this->address_have_prefix($addr))
                {
                    $name = mb_substr($addr, 0, mb_strpos($addr, '"') + 1);
                    $addr = mb_substr($addr, mb_strpos($addr, '"') + 1);
                    $name .= mb_substr($addr, 0, mb_strpos($addr, '"') + 1);
                    $addr = mb_substr($addr, mb_strpos($addr, '"') + 1).", ".$name;
                }


                if(!$addr)continue;
                $shop->address = $this->address($addr);

                $base[] = $shop;
            }
        }

		return $this->savePhysicalResult ($base);
	}

	public function loadNews ()
	{
		$base = array();

        $url = $this->shopBaseUrl."?did=2";
        $text = $this->httpClient->getUrlText($url);

        preg_match_all("#<div class=newshdr.+?><a href='(\?did=(.+?))'>(.+?)</a></div>.+?<td class='text' valign='top' width=100%>(.+?)//(.+?)<a#sui", $text, $news, PREG_SET_ORDER);

        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->urlShort = $url;
            $news_item->urlFull = $this->shopBaseUrl.$news_value[1];
            $news_item->id = $news_value[2];
            $news_item->header = $this->txt($news_value[3]);
            $news_item->date = $this->txt($news_value[4]);
            $news_item->contentShort = $news_value[5];

            $text = $this->httpClient->getUrlText($news_item->urlFull);
            preg_match('#</span></div>(.+?)<center>#sui', $text, $content);
            $news_item->contentFull = mb_substr($content[1], 3);
            
            $base[] = $news_item;
        }

		return $this->saveNewsResult($base);
	}
}
