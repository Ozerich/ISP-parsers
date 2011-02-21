<?php

require_once ('Zend/Http/Client.php');

class HttpClient extends Zend_Http_Client
{
	private $httpRetries = 300;	    // Количество попыток для скачивания страницы.
	private $failWait    = 2;       // Время ожидания в секундах между неудачными попытками.
	private $cachePath   = null;    // Директория, в которую будут кэшироваться скачанные данные.
	private $cacheExpire = 8640000;   // Время, в течение которого будет использоваться кэш в секундах.
	private $bAutoConv   = true;	// Автопреобразование кодировки в $dstCharset
	private $dstCharset  = 'utf-8'; // Кодировка, в которую будут преобразовываться все страницы.
	private $lastCache   = null;    // Путь к последнему сохранённому кэш-файлу.
	private $lastCtype   = null;    // Content-type последнего скачанной файла. 
	private $defaultSrcCharset = 'windows-1251'; /* Считаем, что если не указана кодировка
												    документа, то она будет такой. */
	private $bIgnoreBadCodes = false; /* Если установить в true, то даже если сервер
										отдаёт страницу с кодом != 200, она будет возвращена
										функцией loadPage. */
	
	private $lastReqTime = null; /* Время когда был сделан последний HTTP-запрос */
	private $reqPause    = 0;    /* Задержка между HTTP-запросами */  
	
	private $lastCacheDir = null;
	private $usedCacheDirs = array ();
	
	/*****************************************************************/
	
	public function setIgnoreBadCodes ()
	{
	    $this->bIgnoreBadCodes = true;
	}
	
	/*****************************************************************/
	
	public function disableAutoConv ()
	{
		$this->bAutoConv = false;
	}
	
	/*****************************************************************/
	
	/*
	 * Устанавливает задержку между HTTP-запросами в секундах.
	 */
	public function setRequestsPause ($seconds)
	{
		$this->reqPause = $seconds;
	}
	
	/*****************************************************************/
	
	public function setFailWait ($seconds)
	{
		$this->failWait = $seconds;	
	}
	
	/*****************************************************************/
	
	public function setHttpRetries ($count)
	{
		$this->httpRetries = $count;	
	}
	
	/*****************************************************************/
	
	public function setCachePath ($path)
	{
		$this->cachePath = $path;
	}
	
	/*****************************************************************/
	
	public function getLastCacheFile ()
	{
		return $this->lastCache;
	}
	
	/*****************************************************************/
	
	public function getLastCtype ()
	{
		return $this->lastCtype;
	}
	
	/*****************************************************************/
	
	/**
	 * Получает текстовые данные по урлу $url.
	 *  
	 * @param string $url - УРЛ, откуда будет скачиваться информация.
	 * @param array $postData - ассоциативный массив POST-данных, если
	 * 		нужно сделать POST-запрос.
	 * @param bool $bUseCache - кэшировать результат, чтобы не 
	 * 		скачивать одно и то же много раз.
	 */
	public function getUrlText ($url, $postData = null, $bUseCache = true)
	{
		return $this->loadPage ($url, $postData, $bUseCache, true);
	}
	
	/*****************************************************************/
	
	/**
	 * Получает двоичные данные по урлу $url (подойдёт, например, для скачивания
	 * картинок).  
	 * 
	 * @param string $url - УРЛ, откуда будет скачиваться информация.
	 * @param array $postData - ассоциативный массив POST-данных, если
	 * 		нужно сделать POST-запрос.
	 * @param bool $bUseCache - кэшировать результат, чтобы не 
	 * 		скачивать одно и то же много раз.
	 */
	public function getUrlBinary ($url, $postData = null, $bUseCache = true)
	{
		return $this->loadPage ($url, $postData, $bUseCache, false);
	}
	
	/*****************************************************************/
	
	private function log ($message)
	{
		print ('[' . date ('H:i:s') . "] $message\n");
	}
	
	/*****************************************************************/
	
	private function waitPause ()
	{
		$curTime = microtime(true);
		if ($this->lastReqTime and $this->reqPause and $curTime - $this->lastReqTime < $this->reqPause)
		{
			$waitSeconds = $this->reqPause - ($curTime - $this->lastReqTime);
			$this->log ("Waiting: $waitSeconds seconds");
			usleep (floor(1000000 * $waitSeconds));
		}
	}
	
	/*****************************************************************/
	
	private function loadPage ($url, $postData = null, $bUseCache = true, $bText = false)
	{
		if ($bUseCache)
		{
			$cachePath = $this->getCachePath($url, $postData, $bText);
			$cachedData = $this->getDataFromCache($cachePath);
			if ($cachedData !== false)
			{
				$this->log ("Cached '$url' (path: $cachePath).");
				return $cachedData;
			}
		}
		
		$this->waitPause();
		
		$this->log ("Downloading '$url'...");
		$this->resetParameters (false);
		$this->setUri ($url);
		
		if ($postData)
		{
			$this->setParameterPost ($postData);
			$requestType = 'POST';
		}
		else
			$requestType = 'GET';
		
		$response = null;
		for ($try = 0; $try < $this->httpRetries; $try++)
		{
			try 
			{
				$response = @ $this->request($requestType);
				break;
			}
			catch (Exception $e)
			{
				if ($this->failWait)
					sleep ($this->failWait);
			}
		}
		
		if ( ! $response)
			throw $e;

		$responseCode = $response->getStatus ();
		if ($responseCode != 200)
		{
			$this->log ("HTTP code '$responseCode' != 200 at url: '$url'!");
			if ( ! $this->bIgnoreBadCodes)
			    return null;
		}
			
		$ctype = $this->getResponseContentType($response);
		// if ($ctype === false)
		//	throw new ParserException("Can't get content-type header for url: '$url'!");
			
		if ($bText)
		{
			$retData = $this->getResponseText($response, $ctype);
			if ($retData === false or $retData === null)
				throw new ParserException("Can't get response text for url: '$url'!");
		}
		else
			$retData = @ $response->getBody();
			
		$this->lastCtype = $ctype;
		
		if ($bUseCache and $cachePath and ($responseCode == 200 or $this->bIgnoreBadCodes))
			$this->putDataToCache($cachePath, $retData, $ctype);
		
		$this->lastReqTime = microtime(true);
		return $retData;
	}
	
	/*****************************************************************/
	
	private function putDataToCache ($cachePath, $data, $ctype = null)
	{
		$result = @ file_put_contents ($cachePath, $data);
		if ($result === false)
			throw new ParserException("Can't save cache file '$cachePath'!");
			
		$cachePathCtype = $cachePath . '.ctype';
		$result = @ file_put_contents ($cachePathCtype, $ctype ? $ctype : '');
		if ($result === false)
			throw new ParserException("Can't save ctype cache file '$cachePathCtype'!");
			
		$this->lastCache = $cachePath;
	}
	
	/*****************************************************************/
	
	private function getDataFromCache ($cachePath)
	{
		if ( ! $cachePath or ! file_exists($cachePath))
			return false;

		if (time() - filemtime($cachePath) > $this->cacheExpire)
			return false;

		$cachedData = @ file_get_contents($cachePath);
		if ($cachedData === false)
			throw new ParserException("Can't get data at cache file '$cachePath'!");

		$cachePathCtype = $cachePath . '.ctype';
		$ctype = @ file_get_contents($cachePathCtype);
		if ($ctype === false)
			throw new ParserException("Can't get ctype data at cache file '$cachePathCtype'!");
			
		$this->lastCtype = $ctype;
		$this->lastCache = $cachePath;
		return $cachedData;
	}
	
	/*****************************************************************/
	
	private function getResponseContentType ($response)
	{
		$ctype = $response->getHeader('Content-type');
		if ( ! $ctype)
			return false;
		
		if (is_array($ctype)) 
			$ctype = $ctype[0];
			
		$ctype = strtolower (trim ($ctype));
		// $ctype = trim(preg_replace("/;.*$/", "", $ctype));
		return $ctype;
	}
	
	/*****************************************************************/
	
	public function getResponseText ($response, $ctype = null)
	{
		if ($response === null or $response === false or ! is_object ($response))
			return false;
		
		if ( ! $ctype)
		{
			$ctype = $this->getResponseContentType ($response);
			if ($ctype === false)
				return false;
		}
		
		$textTypes = array 
		(
			'application/xml',
			'application/xhtml+xml',
			'application/xhtml',
			'text/html',
			'text/xhtml',
			'text/xml',
			'text/plain',
		);
		$bFoundTextType = false;
		foreach ($textTypes as $tt)
		{
			if (strpos ($ctype, $tt) === 0)
			{
				$bFoundTextType = true;
				break;
			}
		}
		if ( ! $bFoundTextType)
			return false;
		
		$body = @ $response->getBody();
		if ( ! $this->bAutoConv)
			return $body;
		
		$src_charset = null;
		
		if (preg_match("/charset=([^;]+)$/", $ctype, $regs))
			$src_charset = strtolower ($regs[1]);
			
		$preg_meta_charset = "#(<meta\s+(http-equiv=[\"']?Content-Type[\"']?\s+)?content=[\"']?[^>]*?charset=)([^\"'\s;]+)#i";
		if ($src_charset === null and preg_match($preg_meta_charset, $body, $regs))
			$src_charset = strtolower ($regs[3]);
		
		if (preg_match("/-1251/", $src_charset))
			$src_charset = 'windows-1251';
			
		if ( ! $src_charset)
			$src_charset = $this->defaultSrcCharset;

		$body = preg_replace ($preg_meta_charset, '$1' . $this->dstCharset, $body);
		$body = @iconv ($src_charset, $this->dstCharset . "//TRANSLIT//IGNORE", $body);
		return $body;
	}
	
	/*****************************************************************/
	
	private function calcCacheDir ($url)
	{
		$pathinfo = parse_url ($url);
		if ($pathinfo === false or ! isset ($pathinfo['host']))
			throw new ParserException ("Can't parse url: '$url'!");
		
		$this->lastCacheDir = strtolower ($pathinfo['host']);
		
		if ( ! in_array ($this->lastCacheDir, $this->usedCacheDirs))
			$this->usedCacheDirs[] = $this->lastCacheDir;
		
		return $this->calcFullCachePath ($this->lastCacheDir);
	}
	
	/*****************************************************************/
	
	private function calcFullCachePath ($cacheDir)
	{
		return $this->cachePath . '/' . $cacheDir;
	}
	
	/*****************************************************************/
	
	public function cleanupUsedCacheDirs ()
	{
		foreach ($this->usedCacheDirs as $dir)
		{
			$path = $this->calcFullCachePath ($dir);
			if ( ! rrmdir ($path))
				throw new ParserException ("Can't recursive clean dir '$path'!");
		}
		
		$this->usedCacheDirs = array ();
	}
	
	/*****************************************************************/
	
	public function getCachePath ($url, $postData = null, $bText = false)
	{
		if ( ! $this->cachePath)
			return false;
			
		$dirPath = $this->calcCacheDir ($url);
		if ( ! is_dir ($dirPath))
			if ( ! mkdir($dirPath, 0777, true))
				throw new ParserException ("Can't create directory '$dirPath'");

		$fname = md5 (serialize(array ($url, $postData)));
		$fname .= $bText ? '.txt' : '.dat';		
		
		return $dirPath . '/' . $fname;
	}
	
	/*****************************************************************/
}
