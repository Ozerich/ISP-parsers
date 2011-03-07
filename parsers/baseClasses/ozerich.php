<?php

/*********************************************************************/

require_once PARSERS_BASE_DIR . '/parsers/parserBase.php';
require_once PARSERS_BASE_DIR . '/parsers/httpClient.php';

/* В этот класс записываются все свои функции, которые в будуещем понадобятся для 
 * скачивания других сайтов 
 * */
 
abstract class ItemsSiteParser_Ozerich extends ItemsSiteParser
{ 
	/**
	 * @var $httpClient HttpClient
	 */
	
	public 	$week_days = array("Понедельник","Вторник","Среда","Четверг","Пятница","Суббота","Воскресенье");
    private $month_names = array(array("Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"),
                                array("января","февраля","марта","апреля","мая","июня","июля","августа","сентября","октября","ноября","декабря"));

    private $prefixs = array("ТРК","ТД","магазин","ТРЦ","ЦММ","ТК","ТЦ", "Салон", "СТЦ","Молл","МТДЦ", "ТОЦ","CТЦ", "МЦ", "Студия", "Торговый центр", "РТЦ","РК","ТМ", "Большой ТЦ", "Малый ТЦ","Гипермаркет","Pepe Jeans", "Бутик", "Эксклюзивный отдел", "АТК", "БЦ", "Галерея моды", "СК", "ЦТиР", "Универмаг","Молл", "Мегацентр","сеть магазинов", "Центр","Салон-магазин","ЦУМ","магазины",'МЕГАСИТИ','МТДЦ');
	
	protected $httpClient;
	
	public function __construct($savePath)
	{
		parent::__construct($savePath);
		
		$this->httpClient = new HttpClient ();
		$this->httpClient->setConfig 
		(array(
			'timeout'	=> 1800, /* Время ожидания ответа от сервера */
			'useragent'	=> "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.6.30 Version/10.62",
			'adapter'	=> 'Zend_Http_Client_Adapter_Curl', // Будем обращаться к сайтам через CURL 
			'curloptions' => array 
			(
				CURLOPT_TIMEOUT			=> 1800,
				CURLOPT_FRESH_CONNECT	=> true,
			),
		));
		
		$this->httpClient->setCookieJar(); /* Сделаем так, чтобы при переходе 
			от страницы к странице запоминались Cookie. Это может быть удобно,
			если необходима авторизация на сайте. */
	}
	
	public function txt($text)
	{

		$text = str_replace(array("<BR>",'<br />','<br>','<BR />','&nbsp;', "&laquo;", "&raquo", "&quot;", "&ndash;","&mdash;","«","»","&amp;","&gt","&minus;","&#0150;","&#150;","&bull;","&rsaquo;","&#233;",'\\"','/"',"&#40;","&#41","&#37;","&#171;","&#187;","&ldquo;","&rdquo;",'&#225;','&#218;','&#345;','&#353;','&amp;','&#215;'), array("\n","\n","\n","\n",' ','"','"','"','-',"-",'"','"','&',">","-","-","-","*",">","é",'"','"','(',')','%','"','"','"','"','á','Ú','ř','š','&','×'), $text);
		$text = strip_tags($text);
        $text = htmlspecialchars_decode($text);
		$text = trim($text);
		return $text;
	}
	
	public function discount($old, $new)
	{
		$old = str_replace(array(chr(194).chr(160)," "), array("",""), $old);
		$new = str_replace(array(chr(194).chr(160)," "), array("",""), $new);
		$razn = $old - $new;
		$discount = $razn / $old * 100;
		if(mb_strpos($discount, ".") !== false)
			return substr($discount, 0, strpos($discount, "."));
		else return $discount;
	}
	
	public function date_to_str($date)
	{
        if(mb_strpos($date, " ") !== false)
        {
        while(strpos($date, "  ") !== false)
			$date = str_replace("  ", " ", $date);
		$items = explode(" ", $date);
		if(count($items) >= 2)
		{
			$day = $items[0];
            $month = -1;
            foreach($this->month_names as $month_names_elem)
            {
                $found = false;
                foreach($month_names_elem as $ind=>$name)
                    if(trim(mb_strtoupper($items[1])) == mb_strtoupper($name))
                    {
                        $month = $ind + 1;
                        $found = true;
                        break;
                    }
                if($found)break;
            }
            if($month == -1)
                $month = $items[1];
			$today = getdate(time());
			$today_month = $today['mon'];
			$today_year = $today['year'];
			
			$year = ($today_month < $month) ? $today_year - 1 : $today_year;
			

		}
		if(count($items) >= 3)
			$year = $items[2];
		return $day.".".$month.".".$year;
        }
	}
	
	public function get_month_number($month_name)
	{
		$month_names = $this->month_names[0];
		for($i = 0; $i < count($month_names); ++$i)
			if($month_names[$i] == $month_name)
				return $i+1;
		return 0;
	}
	
	public function address($text)
	{
		$text = $this->txt($text);
        $text = str_replace("км", "___KM___", $text);
        $text = str_replace("\n", " ", $text);
        
        
        $text = str_replace(';',',',$text);
        
        $metro_exist = (mb_strpos($text, " м.") !== false || mb_strpos($text, " м ") !== false || mb_strpos($text, ",м ") !== false
                || mb_strpos($text, ";м ") !== false  || mb_strpos($text, "метро") !== false) || mb_strpos($text, ",м.") !== false
                || mb_strpos($text, " м.") !== false || mb_strpos($text, ";м.") !== false || mb_substr($text,0,2) == "м."
                || mb_strpos($text, "ст.м.") !== false;
		
		while($metro_exist)
		{
			preg_match_all('#((?:(?:ст\.м|м|метро)(?:\.|\s)[^,\.]+)(?:,|\.|$))#sui', $text, $metro, PREG_SET_ORDER);

            for($i = 0; $i < count($metro); $i++)
            {
                if(mb_strpos($metro[$i][1], "(") !== false) continue;
                if($metro)
                {
                    $beg = mb_strpos($text, $metro[$i][1]);
                    $end = $beg + mb_strlen($metro[$i][1]);
                    $text = mb_substr($text, 0, $beg).mb_substr($text, $end);
                }
                break;
            }
            $metro_exist = (mb_strpos($text, " м.") !== false || mb_strpos($text, " м ") !== false || mb_strpos($text, ",м ") !== false
                || mb_strpos($text, ";м ") !== false  || mb_strpos($text, "метро") !== false) || mb_strpos($text, ",м.") !== false
                || mb_strpos($text, " м.") !== false || mb_strpos($text, ";м.") !== false || mb_substr($text,0,2) == "м."
                || mb_strpos($text, "ст.м.") !== false;        }


		$text = trim($text);
		if($text && $text[mb_strlen($text)-1] == ',')
			$text = mb_substr($text, 0, -1);

        $text = str_replace(array("___KM___", ',,',';,',', ,',' ,'), array("км", ',',',',',',','), $text);


        $last_char = mb_substr($text, mb_strlen($text) - 1, 1);
        if($last_char == ',' || $last_char == ';' || $last_char == '.')$text = mb_substr($text, 0, -1);

        $first_char = mb_substr($text, 0, 1);
        while($first_char == ',' && mb_strlen($first_char) > 0)
        {
            $text = trim(mb_substr($text, 1));
            $first_char = mb_substr($text, 0, 1); 
        }

        $text = str_replace("___KM__","км", $text);
		
		return trim($text);
	}
	
	public function address_have_prefix($text)
	{

		$text = $this->txt($text);

		foreach($this->prefixs as $prefix)
        if(mb_strtoupper(mb_substr($text, 0, mb_strlen($prefix))) == mb_strtoupper($prefix))
        {
            if(mb_strlen($text) == mb_strlen($prefix))
                return $prefix;
            $char = mb_strtoupper(mb_substr($text, mb_strlen($prefix), 1));
            if(($char >= 'A' && $char <= 'Z') || ($char >= 'А' && $char <= 'Я'))
                continue;
            return $prefix;
        }
        if(mb_strpos($text, ",") && mb_strpos(mb_strtoupper(mb_substr($text, 0, mb_strpos($text, ","))), "ЭТАЖ") !== false && mb_strpos(mb_substr($text, 0, mb_strpos($text, ",")), "(") === false)
            return true;
		return null;
	}

    public function add_address_prefix($prefix)
    {
        $this->prefixs[] = $prefix;
    }
	
	
	public function setCachePath ($path)
	{
		$this->httpClient->setCachePath($path);
	}


    public function getFileName($url)
    {
         return mb_substr($url, mb_strrpos($url, '/') + 1, mb_strrpos($url, '.') - mb_strrpos($url, '/') - 1);

    }

    public function loadImage($url, $use_encode = true)
    {
        $image = new ParserImage();

        $image->url = $use_encode ? $this->urlencode_partial($url) : $url;
        $text = $this->httpClient->getUrlBinary($image->url);
        if(!$text)return false;
        
        $image->path = $this->httpClient->getLastCacheFile();
        switch ($this->httpClient->getLastCtype()) {
            case 'image/jpeg':
                $image->type = 'jpeg';
                break;
            case 'image/gif';
                $image->type = 'gif';
                break;
            case 'image/png';
                $image->type = 'png';
                break;
            default:
                $image->type = 'jpeg';
                break;
        }
        $image->id = mb_substr($image->url, mb_strrpos($image->url, '/') + 1, mb_strrpos($image->url, '.') - mb_strrpos($image->url, '/') - 1);
        return $image;
    }


    public function fix_address($address)
    {
        
        $address = str_replace(';',',',$address);
        $max_count = mb_substr_count($address, ",");
        $prefix = $this->address_have_prefix($address);
        $count = 0;

        while($prefix && $count <= $max_count)
        {
            $pos = mb_strpos($address, ",");
            if($pos === false)break;
            $name = mb_substr($address, 0, $pos);

            $address = trim(mb_substr($address, $pos + 1)).", ".$name;
            $prefix = $this->address_have_prefix($address);

            $count++;
        }
        if($address[0] == ',')$address = mb_substr($address, 1);
        $address = $this->address($address);

        if(mb_strpos($address, ',') !== false)
        {
            $first = mb_strtolower(mb_substr($address, 0, mb_strpos($address, ',')));
            while(mb_strpos($first, 'республика') !== false || mb_strpos($first, 'обл.') !== false || mb_strpos($first,"область") !== false)
            {
                $address = trim(mb_substr($address, mb_strpos($address, ',') + 1));  
                if(mb_strpos($address, ',') === false)
                    break;
                $first = mb_strtolower(mb_substr($address, 0, mb_strpos($address, ',')));
            }
        }


        return $this->address($address);
    }

    public function delete_comments($text)
    {
        $text = preg_replace('#<!--(.+?)-->#sui', '',$text);
        return $text;
    }
}

/*********************************************************************/
