<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/drakon.php';

class ISP_<имя сайта> extends ItemsSiteParser_Drakon
{ 
	protected $shopBaseUrl = 'http://<host>/'; // URL главной страницы сайта
	
	public function loadItems () 
	{
		$base = array ();
		// Код парсинга товаров. Результаты складываются в $base.
			
		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		// Код парсинга товаров. Результаты складываются в $base.
		
		return $this->savePhysicalResult ($base); /* Есть на сайте нет торговых точек, заменить
			этот код на return null; */
	}
	
	public function loadNews ()
	{
		$base = array ();
		// Код парсинга новостей/акций. Результаты складываются в $base.
		
		return $this->saveNewsResult ($base); /* Есть на сайте нет новостей, заменить
			этот код на return null; */
	}
}
