<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_mango_com extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://shop.mango.com/";
	
	public function loadItems () 
	{
		$base = array ();

		return $this->saveItemsResult ($base);
	}
	
	public function loadPhysicalPoints () 
	{
        return null;
    }
	
	public function loadNews ()
	{
        return null; 
	}
}
