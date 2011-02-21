<?php

/*********************************************************************/

require_once PARSERS_BASE_DIR . '/parsers/parserBase.php';
require_once PARSERS_BASE_DIR . '/parsers/httpClient.php';
require_once PARSERS_BASE_DIR . '/parsers/addons/phpQuery.php';

/*********************************************************************/

abstract class ItemsSiteParser_Yokotoka extends ItemsSiteParser
{
	/**
	 * @var $httpClient HttpClient
	 */
	protected $httpClient;

	public function __construct($savePath)
	{
		parent::__construct($savePath);

		$this->httpClient = new HttpClient();
		$this->httpClient->setConfig (array(
			'timeout'	=> 30, /* Время ожидания ответа от сервера */
			'useragent'	=> "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.6.30 Version/10.62",
			'adapter'	=> 'Zend_Http_Client_Adapter_Curl', // Будем обращаться к сайтам через CURL
			'curloptions' => array
			(
				CURLOPT_TIMEOUT			=> 30,
				CURLOPT_FRESH_CONNECT	=> true,
			),
		));

		$this->httpClient->setCookieJar(); /* Сделаем так, чтобы при переходе
			от страницы к странице запоминались Cookie. Это может быть удобно,
			если необходима авторизация на сайте. */
	}

	public function setCachePath ($path)
	{
		$this->httpClient->setCachePath($path);
	}

    /*
     * Дописывает uri к baseShopUrl
     * @param string $uri - относительный путь на сайте
     * */
    protected function getUriText($uri = '')
    {
        return $this->httpClient->getUrlText($this->uri2url($uri));
    }


    /*
     * Клеит path к baseurl
     *
     * */
    protected function uri2url($uri = '')
    {
        if (mb_strstr($uri, 'http://')){
            return $uri;
        } else {
            $ret = rtrim($this->shopBaseUrl, '/').'/'.ltrim($uri, '/');
            // print "URI2URL: ". $ret;
            return rtrim($this->shopBaseUrl, '/').'/'.ltrim($uri, '/');
        }
        //return $this->shopBaseUrl.$uri;
    }

 	function mb_ucfirst ($str)
    {
        return mb_strtoupper (mb_substr ($str, 0, 1)) . mb_substr ($str, 1);
    }
    
	function mb_ucfirst_walk(&$val, $key) 
	{
    	$val = $this->mb_ucfirst($val);
	}
	
	function array_mb_ucfirst($arr)
	{
	    array_walk($arr, array ($this, 'mb_ucfirst_walk'));
    	return $arr;
	}
	
	function translit($str) 
	{
    	$tr = array(
        	"А"=>"A","Б"=>"B","В"=>"V","Г"=>"G",
        	"Д"=>"D","Е"=>"E","Ж"=>"J","З"=>"Z","И"=>"I",
        	"Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N",
        	"О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T",
        	"У"=>"U","Ф"=>"F","Х"=>"H","Ц"=>"TS","Ч"=>"CH",
        	"Ш"=>"SH","Щ"=>"SCH","Ъ"=>"","Ы"=>"YI","Ь"=>"",
        	"Э"=>"E","Ю"=>"YU","Я"=>"YA","а"=>"a","б"=>"b",
        	"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
        	"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
        	"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
        	"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
        	"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
        	"ы"=>"yi","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
    	);
    	return strtr($str,$tr);
	}
}

/*********************************************************************/
