<?php

require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_svetski_ru extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://www.svetski.ru/'; // Адрес главной страницы сайта 
	
	public function loadPhysicalPoints () 
	{
		$base = array ();
		
		$url = 'http://svetski.ru/content/shops_rus/';
		$cities = $this->httpClient->getUrlText ($url);
		$pregcitiesUrl = '#(size="4">([^<>]{3,})<.+?)(<p><font|<br />..<font|<div><font)#sui';
		preg_match_all ($pregcitiesUrl, $cities, $citiesUrl, PREG_SET_ORDER);
		$cityShopsPreg = '#Магазин(.*)#';

		foreach ($citiesUrl as $city)
		{
			$cityName = $city[2];

			preg_match_all($cityShopsPreg, $city[0], $physPoints, PREG_SET_ORDER);
			
			foreach ($physPoints as $point)
			{
				$phys = new ParserPhysical();
				preg_match('#((ул\.|Д|М|С|Т|Л).*?)(,?\s?тел\.|,?\s?тел\s|тел&nbsp;| 8 \(|</li)#', $point[1], $addr);//
				preg_match('#(тел\.|тел\s|тел&nbsp;|14/2)[^\d\(]*([^<>]+)#', $point[1], $phone);//
				$phys->city 	 = $cityName;

                $phys->address   = $this->txt($addr[1]);
				if (isset($phone[2])) $phys->phone     = $this->txt($phone[2]);


                if(mb_strpos($phys->address, 'ДИСКОНТ') !== false)
                {
                    $phys->b_stock = 1;
                    $phys->address = mb_substr($phys->address, mb_strlen('ДИСКОНТ') + 1);
                }

                if($this->address_have_prefix($phys->address) && mb_strpos($phys->address, '" ') !== false)
                    $phys->address = str_replace('" ', '", ', $phys->address);

                $phys->address = $this->fix_address($phys->address);

                preg_match('#г\.(.+?)\s#sui', $phys->address, $city_name);
                if($city_name)
                {
                    $phys->city = $this->txt($city_name[1]);
                    $phys->address = str_replace($city_name[0], '', $phys->address);
                }

                $base[] = $phys;
			}
		}
		return $this->savePhysicalResult ($base);
	}
	
	
	public function loadNews ()
	{
		$base = array ();
		
		$baseUrl = 'http://svetski.ru/news/part/?id=1';
		$news = $this->httpClient->getUrlText ($baseUrl);
		
		$pregNews = '#<a href=\'/news/news/\?id=(\d+)\'>(.+?)</a>#sui';
		$pregNews = '# <div class=\'news_title\'><b><a href=\'/news/news/\?id=(\d+)\'>(.+?)</a>.+?<div class=\'news_date\'>(.+?)</div>.+?(<table.+?</table>)#sui';
		preg_match_all ($pregNews, $news, $newsResult, PREG_SET_ORDER);
		$pregNewsFull = '#<div class=\'news_date\'>.*?<div>(.+?)<div><br>#sui';

		foreach ($newsResult as $block)
		{
			$url = 'http://svetski.ru/news/news/?id=' . $block[1];
			
			$base[] = $newsElem = new ParserNews();
			$newsElem->id           = $block[1];
			$newsElem->header      = $block[2];
			$newsElem->contentShort = $block[4];
			$newsElem->urlShort     = $baseUrl;
			$newsElem->urlFull      = $url;
			$newsElem->date         = $block[3];
			
			$newsFull = $this->httpClient->getUrlText ($url);
			preg_match($pregNewsFull, $newsFull, $newsResultFull);
			$newsElem->contentFull = $newsResultFull[1];
		}

		return $this->saveNewsResult ($base);	
	}
	
	
	public function loadItems () 
	{
		$base = array ();
		
		
		$sitemap = $this->httpClient->getUrlText ('http://svetski.ru/gallery/');

		$pregGoods = '#/gallery_images/\d+/([^\s"/]+?)_big\.jpg#sui';
		$pregCollectionName = '#<div class="title".*?>(.*?)</div>#sui';
		preg_match ($pregCollectionName, $sitemap, $CollectionName);

        preg_match_all('#<tr valign="top"><td valign="top" style="padding-left:52px; color:\#333333"><b>(.+?)</b></td></tr>(.+?)</table>#sui',
            $sitemap, $categories, PREG_SET_ORDER);
        foreach($categories as $category)
        {
            $category_name = mb_substr($this->txt($category[1]), 0, -1);
            preg_match_all('#<a href="(.+?)">(.+?)</a>#sui', $category[2], $collectionUrls, PREG_SET_ORDER);
            foreach ($collectionUrls as $colUrlInfo)
            {

                $items = array();
                $page = 1;

                do
                {
                    $url = "http://svetski.ru" . $colUrlInfo[1] . "&page=" . $page;
                    $content = $this->httpClient->getUrlText($url);

                    $category = array($category_name, $colUrlInfo[2]);
                    $page++;
                    preg_match_all($pregGoods, $content, $regsGoods, PREG_SET_ORDER);
                    if (empty ($regsGoods)) break;
                    foreach ($regsGoods as $r)
                    {
                        $itemInfo = new ParserItem ();
                        $itemInfo->url = "http://svetski.ru" . $r[0];
                        $itemInfo->id = $r[1];
                        $itemInfo->categ = $category;

                        $this->httpClient->getUrlBinary("http://svetski.ru" . $r[0]);
                        if ($this->httpClient->getLastCtype() != 'image/jpeg')
                            $this->parseError("Content-type header not image/jpeg at url '$imgUrl'!");
                        $image = new ParserImage();
                        $image->url = "http://svetski.ru" . $r[0];
                        $image->path = $this->httpClient->getLastCacheFile();
                        $image->type = 'jpeg';
                        $itemInfo->images[] = $image;

                        $items[] = $itemInfo;
                    }

                } while ($page < 10);
                if (empty ($items))
                    continue;
                $collName = $CollectionName[1];

                if (isset ($base[$collName])) {
                    foreach ($items as $item)
                        $base[$collName]->items[] = $item;
                }
                else
                {
                    $collection = new ParserCollection();
                    $collection->name = $collName;
                    $collection->items = $items;
                    $base[$collName] = $collection;
                }
            }

        }
		
		return $this->saveItemsResult ($base);
	}
	

}
