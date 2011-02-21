<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_vanlaack_de extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.vanlaack.de/";
    
    public function __construct($savePath) 
    { 
        parent::__construct($savePath); 
       // $this->httpClient->setRequestsPause (0.5);

    }
   
	public function loadItems () 
	{
		$base = array();

        $text = $this->httpClient->getUrlText($this->shopBaseUrl."ru/web/collection/");
        preg_match_all('#<li><a href="(http://www.vanlaack.de/ru/web/collection/(.+?)/)".+?>(.+?)</a>#sui', $text, $collections, PREG_SET_ORDER);
        foreach($collections as $collection_value)
        {
            $collection_item = new ParserCollection();

            $collection_item->id = $collection_value[2];
            $collection_item->url = $collection_value[1];
            $collection_item->naem = $collection_value[3];

            $text = $this->httpClient->getUrlText($collection_item->url);
            preg_match('#<ul id="carousel_ul">(.+?)</ul>#sui', $text, $text);
            preg_match_all('#id="bottomImage_(\d+)"#sui', $text[1], $ids);
            foreach($ids[1] as $id)
            {
                $item = new ParserItem();

                $item->id = $id;
                $item->url = $collection_item->url;
                    
                $text = $this->httpClient->getUrlText('http://www.vanlaack.de/out/my_theme/src/js/ajax_updater.php?action=getShirtDetails&id='.$id.'&lang=ru');

                preg_match("#else{\s*top.document.getElementById\('shirtDetailsImage'\).src='/(.+?)'#sui", $text, $image);
                $item->images[] = $this->loadImage($this->shopBaseUrl.$image[1]);
                
                preg_match("#innerHTML = '(.+?)'#sui", $text, $text);
                $text = $text[1];

                preg_match('#<b>(.+?)</b>#sui', $text, $name);
                if($name)$item->name = $this->txt($name[1]);

                preg_match('#артикулы: (.+?) #sui', $text, $articul);
                if($articul)$item->articul = $this->txt($articul[1]);

                preg_match('#цвет: (.+?)<br />#sui', $text, $color);
                if($color)$item->colors[]  = $this->txt($color[1]);
                

                $collection_item->items[] = $item;
            }

            $base[] = $collection_item;
        }

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
