<?php

// require_once PARSERS_BASE_DIR . '/parsers/addons/URL2.php';
require_once PARSERS_BASE_DIR . '/parsers/dataFormat.php';

/**********************************************************************/

/* Этот класс будет использоваться для организации исключений. */
class ParserException extends Exception {}

/**********************************************************************/

abstract class ItemsSiteParser /* описывает интерфейс по парсингу сайта */
{
	protected $shopBaseUrl;
	protected $savePath;
	private $dstShopId = null;
	
	/**
	 * Конструктор устанавливает директорию, в которой будут сохраняться данные. 
	 * @param $savePath - строка, путь к директории сохранения файлов.
	 */
	public function __construct ($savePath)
	{
		$this->savePath = $savePath;
	}
	
	/**
	 * Возвращает количество скачанных товарных позиций в случае успешной загрузки и false
	 * в случае ошибки.
	 * а картинки в директории "$savePath/images/"
	 */
	abstract public function loadItems ();
	
	/**
	 * Возвращает количество скачанных торговых точек в случае успешной загрузки и false
	 * в случае ошибки. Может возвращать null если на сайте нет торговых точек.
	 * */
	abstract public function loadPhysicalPoints ();
	
	
	/**
	 * Возвращает количество скачанных новостей в случае успешной загрузки и false
	 * в случае ошибки. Может возвращать null если на сайте нет акций/новостей.  
	 * */
	abstract public function loadNews();
	
	private final function getBaseUrlHost ()
	{ 
		$parsed = parse_url ($this->shopBaseUrl);
		if ($parsed === false or ! isset ($parsed['host']))
			throw new ParserException ("Can't parse base url: '{$this->shopBaseUrl}'");
		
		return strtolower ($parsed['host']);
	}
	
	private final function getSiteSavePathBase ()
	{
		$ret = $this->savePath . '/' . $this->getBaseUrlHost ();
		
		if ($this->dstShopId)
			$ret .= '_dstShopId' . $this->dstShopId; 
		
		return $ret;
	}
	
	public final function getShopsFilePath() 
	{
        return $this->getSiteSavePathBase() . '_physical.dat';
    }
    
    public final function getCollectionsFilePath() 
    {
        return $this->getSiteSavePathBase() . '_items.dat';
    }
	
    public final function getNewsFilePath ()
    {
    	return $this->getSiteSavePathBase() . '_news.dat';
    }
    
	public final function saveItemsResult ($items)
	{
		$savePath = $this->getCollectionsFilePath();
		
		$result = @ file_put_contents($savePath, serialize ($items));
		if ($result === false)
			throw new ParserException ("Can't save items result to: '$savePath'");
			
		return $savePath;
	}

	public final function savePhysicalResult ($physicalPoints)
	{
		$savePath = $this->getShopsFilePath();
		$result = @ file_put_contents($savePath, serialize ($physicalPoints));
		if ($result === false)
			throw new ParserException ("Can't save physical points result to: '$savePath'");
			
		return $savePath;
	}
	
	public final function saveNewsResult ($news)
	{
		$savePath = $this->getNewsFilePath();
		$result = @ file_put_contents($savePath, serialize ($news));
		if ($result === false)
			throw new ParserException ("Can't save news result to: '$savePath'");
			
		return $savePath;
	}
	
	/* Вызывается в случае, если страницу невозможно отпарсить. */
	protected final function parseError ($message)
	{
		throw new ParserException("[PARSE ERROR]: $message");		
	}
	
	/* Вызывается, например, если на сайте есть битая ссылка. */
	protected final function parseWarning ($message)
	{
		print ("WARNING: $message\n");
	}
	
	
	
	/*****************************************************************/
	/*                       ПОЛЕЗНЫЕ ФУНКЦИИ:                       */
	/*****************************************************************/
	
	/**
 	* Вычисляет абсолютный URL по атрибуту href HTML-ссылки и 
 	* URL'у текущей страницы сайта.
 	*  
 	* @param string $baseAddr  - адрес страницы сайта, на которой находится ссылка
 	* @param string $href      - атрибут href ссылки 
 	*/
	/* function createUrlFromHref ($baseAddr, $href)
	{
		$baseUrlObject = new Net_URL2($baseAddr);
		return $baseUrlObject->resolve($href)->getURL();
	} */

	function sym2HexUrl ($s)
	{
		$hex = strtoupper(bin2hex ($s));
		$hex = '%' . substr($hex, 0, 2) . '%' . substr($hex, 2, 2); 
		return $hex;
	}
	
	/*
 	* Необходимо применять эту функцию к УРЛу, когда он содержит
 	* пробелы и др. символы.
 	* 
 	* */
	function urlencode_partial ($str, $noEncode = array ('-', '_', '.', '/', '?', '=', '&'))
	{
		return self::urlencode_partial_static ($str, $noEncode);
	}
	
	public static  function urlencode_partial_static ($str, $noEncode = array ('-', '_', '.', '/', '?', '=', '&'))
	{
		$ret = '';
	
		if (preg_match ("#^(https?://[^/]+)(.*)#", $str, $regs))
		{
			$ret = $regs[1];
			$str = $regs[2];
		}
	
		$len = mb_strlen ($str);
		for ($i = 0; $i < $len; $i++)
		{
			$ch = mb_substr ($str, $i, 1);
			if (
					($ch >= 'a' and $ch <= 'z') 
				or  ($ch >= 'A' and $ch <= 'Z') 
				or  (in_array ($ch, $noEncode))
				or  ($ch >= '0' and $ch <= '9')
			)
			{
				$ret .= $ch;
			}
			elseif (strlen ($ch) == 1) // Обычный символ
				$ret .= sprintf ("%%%02X", ord($ch[0]));			
			else // UNICODE-символ
				$ret .= sprintf ("%%%02X%%%02X", ord($ch[0]), ord($ch[1]));
		}
	
		return $ret;
	}
	
	public static final function getInstanceByExecuteInfo ($parserExecuteInfo)
	{
		require_once PARSERS_BASE_DIR . '/parsers/sites/' .$parserExecuteInfo->getFilePath();
		$className = $parserExecuteInfo->getClassName();
		$parser = new $className (SITES_PARSERS_DATA_PATH . '/results');
	
		if (in_array ('setCachePath', get_class_methods($className)))
			$parser->setCachePath (SITES_PARSERS_DATA_PATH . '/cache');
		
		if ($dstShopId = $parserExecuteInfo->getDstShopId())
			$parser->setDstShopId ($dstShopId);
			
		return $parser;
	}
	
	public final function getCacheFiles ()
	{
		return null;
	}
	
	public final function getCacheSize ()
	{
		return null;
	}
	
	public final function cleanupCache ()
	{
		if ( ! isset ($this->httpClient))
			return true;
		
		$this->httpClient->cleanupUsedCacheDirs();
	}
	
	public final function setDstShopId ($shopId)
	{
		$this->dstShopId = $shopId;
	}
	
	public final function getDstShopId()
	{
		if ( ! $this->dstShopId)
			$this->parseError ("dstShopId not set!");
		
		return $this->dstShopId;
	}
}

/**********************************************************************/

class ParserExecuteInfo
{
	private $fpath;
	private $cname;
	private $dstShopId;
	
	public function __construct ($fpath, $cname, $dstShopId = null)
	{ 
		$this->fpath     = $fpath;
		$this->cname     = $cname;
		$this->dstShopId = $dstShopId; 
	}
	
	public function getFilePath () { return $this->fpath; }
	public function getClassName() { return $this->cname; }
	public function getDstShopId () { return $this->dstShopId; }
}

/**********************************************************************/
	
