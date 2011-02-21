<?php

/*********************************************************************/

require_once PARSERS_BASE_DIR . '/parsers/parserBase.php';
require_once PARSERS_BASE_DIR . '/parsers/httpClient.php';

/*********************************************************************/

/* В этот класс записываются все свои функции, которые в будуещем понадобятся для 
 * скачивания других сайтов 
 * */
abstract class ItemsSiteParser_Drakon extends ItemsSiteParser
{ 
	/**
	 * @var $httpClient HttpClient
	 */
	protected $httpClient;
	
	public function __construct($savePath)
	{
		parent::__construct($savePath);
		
		$this->httpClient = new HttpClient ();
		$this->httpClient->setConfig 
		(array(
			'timeout'	=> 30, /* Время ожидания ответа от сервера */
			'useragent'	=> "Opera/9.80 (Windows NT 5.1; U; ru) Presto/2.6.30 Version/10.62",
			'adapter'	=> 'Zend_Http_Client_Adapter_Curl', // Будем обращаться к сайтам через CURL 
			'curloptions' => array 
			(
				CURLOPT_TIMEOUT			=> 30,
				CURLOPT_FRESH_CONNECT	=> true,
				CURLOPT_SSL_VERIFYPEER	=> false,
				CURLOPT_SSL_VERIFYHOST	=> false,
			),
		));
		
		$this->httpClient->setCookieJar(); /* Сделаем так, чтобы при переходе 
			от страницы к странице запоминались Cookie. Это может быть удобно,
			если необходима авторизация на сайте. */
		
		/* Дадим серверам знать, на каком языке предпочтительнее выдавать сайт: */
		$this->httpClient->setHeaders('Accept-Language', 'ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3');
	}
	
	public function setCachePath ($path)
	{
		$this->httpClient->setCachePath($path);
	}
	
	/*
	 * Удаляет из строки $str HTML-теги, заменяет сущности типа &nbsp;, &lt; на
	 * соответствующие им символы, обрезает концевые пробелы.
	 */
	public function getText ($str)
    {
        return trim(html_entity_decode(strip_tags($str), ENT_COMPAT, "utf-8" )) ;
    }
}

/*********************************************************************/
