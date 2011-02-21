<?php

/*********************************************************************/

require_once PARSERS_BASE_DIR . '/parsers/parserBase.php';
require_once PARSERS_BASE_DIR . '/parsers/httpClient.php';

/*********************************************************************/

/* В этот класс записываются все свои функции, которые в будуещем понадобятся для 
 * скачивания других сайтов 
 * */
abstract class ItemsSiteParser_Sema extends ItemsSiteParser
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
	}
	
	public function setCachePath ($path)
	{
		$this->httpClient->setCachePath($path);
	}
	
	protected function getData($start,$tag,&$page,$start_pos)
	{
		$end_shift	=strlen($tag)+2;
		$start_shift=$end_shift-1;
		
		$t			= strlen($page);
		if ($start_pos>$t)
			return '';
		$start		=strpos($page,$start,$start_pos);
		$end_2  	=strpos($page,"</$tag",$start)+$end_shift;
		$start_2	=strpos($page,"<$tag",$start+strlen($start));
		$k=1;
		while ($k>0)
		{
			if (($start_2<$end_2) && ($start_2>0))
			{
				$k++;
				$start_2  =strpos($page,"<$tag",$start_2+$start_shift);
			}
			else
			{
				$k--;
				$end=$end_2;
				$end_2  =strpos($page,"</$tag",$end_2)+$end_shift;
			}
		}
		return substr($page,$start,$end-$start);
	}
	
	protected function item_image_form($imgUrl)
	{
		$this->httpClient->getUrlBinary ($imgUrl);
		$type=$this->httpClient->getLastCtype ();
		if (substr($type,0,5)!= 'image') 
			$this->parseError("Content-type header $type,but not image at url '$imgUrl'!");
		$rash=strrpos($imgUrl,'.');
		$img_id=strrpos($imgUrl,'/')+1;
		$image = new ParserImage();
		$image->url = $imgUrl;
		$image->id  = substr($imgUrl,$img_id,$rash-$img_id);
		$image->path = $this->httpClient->getLastCacheFile();
		$image->type = substr($imgUrl,$rash+1);;
		return $image;
	}
	
	function get_text_data($html)
	{
		return html_entity_decode(trim(preg_replace("/(<[^>]*>)/",' ',$html)),ENT_QUOTES,'UTF-8');
	}
}

/*********************************************************************/
