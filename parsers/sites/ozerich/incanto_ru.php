<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_incanto_ru extends ItemsSiteParser_Ozerich
{	
	protected $shopBaseUrl = 'http://incanto.ru/'; // Адрес главной страницы сайта
		
	public function loadNews() { 
		$base = array ();
		$text = $this->httpClient->getUrlText ($this->shopBaseUrl.'ru/XML/ru/newsmodule.xml');
        $text = mb_convert_encoding($text,'CP1251','UTF-8');

		preg_match_all('#<newsitem labelimage="img/news/thumbs/(\d+).jpg".+?<newsdate><\!\[CDATA\[(.+?)\]\]> </newsdate>\s*<label><\!\[CDATA\[(.+?)\]\]></label>\s*<text><\!\[CDATA\[(.+?)\]#si',
            $text, $news, PREG_SET_ORDER);
        foreach($news as $news_value)
        {
            $news_item = new ParserNews();

            $news_item->date = $this->txt(str_replace('-','.',$news_value[2]));
            $news_item->header = $this->txt($news_value[3]);
            $news_item->contentShort = $news_value[3];
            $news_item->contentFull = $news_value[4];
            $news_item->urlShort = $this->shopBaseUrl.'ru/#/news/newsmodule/0?lang=ru';
            $news_item->urlFull = $this->shopBaseUrl.'ru/#/news/newsmodule/'.$news_value[1].'?lang=ru';
            $news_item->id = $news_value[1];

            $base[] = $news_item;
        }

		return $this->saveNewsResult ($base);
	}

	
	
	public function loadItems () 
	{
		$base = array ();

        $collection_name = "Весна-Лето 2011";
        $collections = array(
            array("name"=>"Бельё", "id"=>"u2011s1"),
            array("name"=>"Одежда", "id"=>"c2011s1"),
            array("name"=>"Купальники", "id"=>"s2011s1"));
        foreach($collections as $collection_value)
        {
            $collection = new ParserCollection();

            $collection->id = $collection_value['id'];
            $collection->name = $collection_name." - ".$collection_value['name'];
            $collection->url = $this->shopBaseUrl."ru/#/collections/".$collection_value['id']."/0?lang=ru";

            $xml_file = $this->shopBaseUrl."ru/XML/ru/collections/".$collection->id.".xml";

            $text = $this->httpClient->getUrlText($xml_file);
            $text = mb_convert_encoding($text, "CP1251", "UTF-8");

            preg_match_all('#<photo>(.+?)</photo>#sui', $text, $items);
            foreach($items[1] as $text)
            {
                preg_match('#<filename>(.+?)</filename>.+?<itemname1 type="(.*?)">(.*?)</itemname1>\s*<itemname2 type="(.*?)">(.*?)</itemname2>#sui',
                    $text, $info);
                $item = new ParserItem();

                if($info[2] != '')
                    $item->name .= $info[2]." ".$info[3];
                if($info[4] != '')
                    $item->name .= ', '.$info[4]." ".$info[5];
                if($item->name == "")
                    continue;

                $image = $this->loadImage($this->shopBaseUrl.'ru/img/'.$info[1]);
                if($image)
                    $item->images[] = $image;

                $collection->items[] = $item;
            }

            $base[] = $collection;
        }


		return $this->saveItemsResult ($base);
	}

	public function loadPhysicalPoints () 
	{
		$base = array ();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."ru/XML/ru/shopmodule.xml");
        $data = simplexml_load_string($text);
        $data = $data->combomenu;
        foreach($data->incanto_locate as $item)
        {

            $country = mb_convert_encoding($item->country, 'CP1251', 'UTF-8');
            if($country != 'Российская Федерация')
                continue;
            $city = mb_convert_encoding($item->city, 'CP1251', 'UTF-8');
            $address = mb_convert_encoding($item->address, 'CP1251', 'UTF-8');
            $code = mb_convert_encoding($item->code, 'CP1251', 'UTF-8');
            $phone = mb_convert_encoding($item->tel, 'CP1251', 'UTF-8');
            $timetable = mb_convert_encoding($item->hours, 'CP1251', 'UTF-8');

            $shop = new ParserPhysical();

            if(mb_substr($address,0,2) == "ТЦ" && $address[2] != ' ')
                $address = mb_substr($address, 0, 2)." ".mb_substr($address, 3);
            if(mb_substr($address,0,3) == "ТРЦ" && $address[3] != ' ')
                $address = mb_substr($address, 0, 3)." ".mb_substr($address, 4);

            $shop->city = $city;
            $shop->address = $this->fix_address($address);
            if($code != "")
                $shop->phone = "(".$code.")";
            $shop->phone .= $phone;
            $shop->timetable = $timetable;



            $base[] = $shop;
        }



		return $this->savePhysicalResult ($base);
	}
}

?>