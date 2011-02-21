<?php

/*********************************************************************/

require_once PARSERS_BASE_DIR . '/parsers/parserBase.php';
require_once PARSERS_BASE_DIR . '/parsers/httpClient.php';
require_once 'Zend/Dom/Query.php';
/*********************************************************************/

/**
 * Класс для работы с url
 * @author  Alex Tatulchenkov aka webtota <webtota@gmail.com>
 */
class Zen_Url{
    /**
     * Эскейпит урл сегменты урл используя rawurlencode, работает только с абсолютными
     * путями вида http://site.com/path?params
     *
     * @return string
     */
    static public function escapeUrl($url)
    {
        $url = str_replace('\\', '/', $url);
        $parsed = parse_url($url);
        $chunks = explode('/', $parsed['path']);
        foreach ($chunks as $chunk) {
            $esc[] = rawurlencode($chunk);
        }
        $escUrl = implode('/', $esc);
        $parsed['path'] = $escUrl;
        return self::httpBuildUrl($parsed);

    }
    /**
     * Строит урл на основе массива, в формате возвращаемом функцией parse_url
     *
     * @param array $parsed
     * @return string
     */
    static public function httpBuildUrl($parsed)
    {
        if (!is_array($parsed)) return false;
        if (isset($parsed['scheme'])) {
            $sep = (strtolower($parsed['scheme']) === 'mailto' ? ':' : '://');
            $url = $parsed['scheme'] . $sep;
        } else {
            $url = '';
        }
        if (isset($parsed['pass'])) {
            $url .= "$parsed[user]:$parsed[pass]@";
        } elseif (isset($parsed['user'])) {
            $url.= "$parsed[usewr]@";
        }
        if (@is_array($parsed['query'])) {
            $parsed['query'] = http_build_query($parsed['query']);
        }
        if (isset($parsed['host'])) $url .= $parsed['host'];
        if (isset($parsed['port'])) $url .= ":" . $parsed['port'];
        if (isset($parsed['path'])) $url .= $parsed['path'];
        if (isset($parsed['query'])) $url .= "?" . $parsed['query'];
        if (isset($parsed['fragment'])) $url .= "#" . $parsed['fragment'];
        return $url;
    }


}

/*********************************************************************/

/* В этот класс записываются все свои функции, которые в будуещем понадобятся для
 * скачивания других сайтов
 * */
abstract class ItemsSiteParser_Dzentota extends ItemsSiteParser
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

    public function trimTags($string)
    {
        $string = preg_replace("~\r\n|\r|\n~","", $string);
        return preg_replace("~>\s+<~", "><", $string);
    }





        public function _d($var)
        {
            echo "<pre>";
            print_r($var);
            echo "</pre>";
            die();
        }

        protected function _getNodeInnerHTML($elem) {
            return simplexml_import_dom($elem)->asXML();
        }

        protected function _fixEncoding($string)
        {
            return mb_convert_encoding( $string, 'HTML-ENTITIES', 'utf-8');
        }

}

/*********************************************************************/
