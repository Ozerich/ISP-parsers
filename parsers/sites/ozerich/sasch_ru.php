<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_sasch_ru extends ItemsSiteParser_Ozerich
{ 
	protected $shopBaseUrl = "http://www.sasch.ru/";
	
	public function loadItems () 
	{
		$base = array();
		return $this->saveItemsResult ($base);
	//	return null;
	}
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."shop");
		
		preg_match('#<TH colSpan=2>Москва</TH></TR>(.+?)<TR id=firstTown>#sui', $text, $shop_text);
		preg_match_all('#<TR>(.+?)</TR>#sui', $shop_text[1], $shops, PREG_SET_ORDER);
		foreach($shops as $shop)
		{
			if(strpos($shop[1], "class=stitle1")!==false)
				continue;
			$shop_item = new ParserPhysical();
			
			$shop_item->city = "Москва";
			$shop_item->address = $this->address($shop[1]);
			
			if($this->address_have_prefix($shop_item->address))
			{

				preg_match('#((.+)")(\.|,|\s)\s*(.+)#sui', $shop_item->address, $info);
				if($info)
					$shop_item->address = $info[4] .",". $info[1];
				
				if(mb_substr($shop_item->address, 0, mb_strlen("2 эт.")) == "2 эт.")
					$shop_item->address = mb_substr($shop_item->address, mb_strlen("2 эт.,")).",2 эт";
			}
			
			$base[] = $shop_item;
		}
		
		preg_match('#<TR id=firstTown>(.+?)<TR>\s*<TD class=stitle2 colSpan=2>#sui', $text, $shop_text);
		preg_match_all('#<TR>\s*<TH>(.+?)</TH>\s*<TD>(.+?)</TD>\s*</TR>#sui', $shop_text[1], $shops, PREG_SET_ORDER);
		foreach($shops as $shop)
		{
			$shop_item = new ParserPhysical();
			
			$shop_item->city = $shop[1];
			$shop_item->address = $shop[2];
			
			$shop_item->address = $this->address($shop_item->address);
			$shop_item->address = trim(str_replace(array("»","«","\n"), array('"','"',""),$shop_item->address));
			
			if($this->address_have_prefix($shop_item->address))
			{

				preg_match('#((.+)")(\.|,|\s)\s*(.+)#sui', $shop_item->address, $info);
				if($info)
					$shop_item->address = $info[4] .",". $info[1];
				
				if(mb_substr($shop_item->address, 0, mb_strlen("2 эт.")) == "2 эт.")
					$shop_item->address = mb_substr($shop_item->address, mb_strlen("2 эт.,")).",2 эт";
			}
		
			if(strpos($shop_item->address, 'ТРЦ "Авокадо"') !== false)
				$shop_item->address = substr($shop_item->address, mb_strlen('ТРЦ "Авокадо"')).",".'ТРЦ "Авокадо"';
			if($shop_item->address[0] == ',')
				$shop_item->address = trim(substr($shop_item->address, 2));
			$base[] = $shop_item;
		}
		
		

		return $this->savePhysicalResult ($base); 
	}
	
	public function loadNews ()
	{
		$base = array ();
		
		$text = $this->httpClient->getUrlText($this->shopBaseUrl."new");
		
		preg_match_all('#<h4 class="title"><span class="date">(.+?)</span>\s-\s<a href="/(new/(\d+))">(.+?)</a>\s*</h4>\s*<p class="note">(.+?)</p>\s*</div>#sui', $text, $news, PREG_SET_ORDER);
		
		foreach($news as $news_item)
		{
			$item = new ParserNews();
			
			$item->id = $news_item[3];
			$item->date = $news_item[1];
			if(strpos($item->date, " ")!==false)
				$item->date = substr($item->date, 0, strpos($item->date, " "));
			$item->header = $news_item[4];
			$item->contentShort = $news_item[5];
			$item->urlShort = $this->shopBaseUrl."new";
			$item->urlFull = $this->shopBaseUrl.$news_item[2];
			
			$text = $this->httpClient->getUrlText($item->urlFull);
			preg_match('#<p class="note">(.+?)</p>#sui', $text, $content);
			if($content)
				$item->contentFull = $content[1];
			
			$base[] = $item;
		}
		
		return $this->saveNewsResult ($base); 
	}
}
