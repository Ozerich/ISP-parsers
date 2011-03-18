<?php

/* Подключаем личный класс для операций по скачивания страниц: */
require_once PARSERS_BASE_DIR . '/parsers/baseClasses/ozerich.php';

class ISP_serge_fashion_by extends ItemsSiteParser_Ozerich
{
	protected $shopBaseUrl = 'http://serge-fashion.by/'; // Адрес главной страницы сайта
    var $_base=array();

	function get_items($cont,$cat,$link){
         preg_match('!<div class="paging">(.*?)</div>!si',$cont,$pag);
         if (isset($pag[1])){
           preg_match_all('!<a href="(.*?)">(.*?)</a>!si',$pag[1],$nn);
           $kol=intval($nn[2][count($nn)-2]);
         }
         else $kol=1;

         for ($i=1;$i<$kol+1;$i++){
            $url=$link."~page__n19=".$i;
            $cont=$this->httpClient->getUrlText ($url);
            preg_match_all('!<div class="item(.*?)</script>!si',$cont,$itm);
            for ($y=0;$y<count($itm[0]);$y++){
            	preg_match('!href="(.*?)"(.*?)title="(.*?)"!si',$itm[0][$y],$lin);
            	$art=$lin[3];
            	$itemlink=$lin[1];

                $itmcont=$this->httpClient->getUrlText ($itemlink);
                preg_match('!<div class="big">(.*?)<div class="description">!si',$itmcont,$big);
                preg_match('!<div class="description">(.*?)<br class="clr" />!si',$itmcont,$descr);
                preg_match('!rateItemInit(.*?);!si',$descr[1],$id);
                $r=explode(",",$id[1]);
                unset($id);
                $id=$r[2];

                preg_match('!<div>Цвет: <strong>(.*?)</strong>!si',$descr[1],$info);
                preg_match('!<div>Состав полотна: <strong>(.*?)</strong>!si',$descr[1],$info2);
                preg_match('!<div>Размер: <strong>(.*?)</strong>!si',$descr[1],$info3);

                if (isset($info[1])) {
                    $coo=$info[1];
                    $color=explode(",",$coo);
                }
                else $color='';
                if (isset($info2[1]))$sost=$info2[1]; else $sost='';
                if (isset($info3[1])){
                	$si=$info3[1];
                    $size=explode(",",$si);
                }
                else $size='';
                preg_match_all('!<img src="(.*?)"!si',$big[1],$im);

                $itemInfo = new ParserItem ();
		        $itemInfo->id       = $id;
		        $itemInfo->url   	= $itemlink;
		        $itemInfo->name     = $art;
		        $itemInfo->articul  = $art;
                $itemInfo->categ 	= $cat;
                $itemInfo->structure= $sost;
                $itemInfo->sizes    = $size;
                $itemInfo->colors   = $color;

                $pict=array();
                if (isset($im[1][0])){
                	$kkk=0;
                	if (count($im[1])>2) $kkk=2;
                	else $kkk=count($im[1]);
                	for ($z=0;$z<$kkk;$z++){
                		$imgUrl=$im[1][$z];
                		$this->httpClient->getUrlBinary ($imgUrl);
		                if ($this->httpClient->getLastCtype () != 'image/jpeg') continue;

		                $image = new ParserImage();
		                $image->url  = $imgUrl;
		                $image->path = $this->httpClient->getLastCacheFile();
		                $image->type = 'jpeg';

                        $itemInfo->images[]=$image;
                	}
                }
                $this->_base[]=$itemInfo;
            }
         }
    }
    function addtocol($nam,$url,$item){
        $collection = new ParserCollection();
		$collection->id   = $nam;
		$collection->url  = $url;
		$collection->name = $nam;
		$collection->items = $item;
		return $collection;
	}

    function get_link($url){
        $cont = $this->httpClient->getUrlText ($url);
        preg_match('!title="Трусы"  class="open">(.*?)<ul>(.*?)</ul>!si',$cont,$bl);
        preg_match_all('!<a href="(.*?)" title="(.*?)"!si',$bl[2],$li);
        $mas['link']=$li[1];
        $mas['name']=$li[2];
        return $mas;
    }
    // товары
	public function loadItems (){
        $base=array();
        $baseUrl='http://serge-fashion.by/rus/products/';
    	$cont = $this->httpClient->getUrlText ($baseUrl);
    	preg_match_all('!<div class="item">(.*?)<h3>(.*?)</h3>(.*?)</div>!si',$cont,$ca);
        $cat[0][0]='<div class="item">
							<h3> Новинки</h3>
							<a href="http://serge-fashion.by/rus/products/~group_id__n19=70" title="Новинки">
						</div>';
        $cat[0][1]=$ca[0][0];
        $cat[0][2]=$ca[0][1];

        $cat[2][0]='Новинки';
        $cat[2][1]=$ca[2][0];
        $cat[2][2]=$ca[2][1];

    	$collin[]='http://serge-fashion.by/rus/products/~group_id__n19=70';
    	$collin[]='http://serge-fashion.by/rus/products/~group_id__n19=4';
    	$collin[]='http://serge-fashion.by/rus/products/~group_id__n19=15';
    	for ($i=0;$i<count($cat[0]);$i++){
            preg_match_all('!<a href="(.*?)" title="(.*?)">!si',$cat[0][$i],$li);
            for ($y=0;$y<count($li[0]);$y++){
                unset($ca);
                unset($lin);
                $ca=$li[2][$y];
                $lin=$li[1][$y];
                $cont=$this->httpClient->getUrlText ($lin);
                $this->get_items($cont,$ca,$lin);

                if ($lin=='http://serge-fashion.by/rus/products/~group_id__n19=5~csort__n19=date~csortorder__n19=-1') {
                	$sub=$this->get_link($lin);
                    for ($z=0;$z<count($sub['link']);$z++){
                        unset($cc);
                        $cc[]=$ca;
                        $cc[]=$sub['name'][$z];
                    	$cont=$this->httpClient->getUrlText ($sub['link'][$z]);
                        $this->get_items($cont,$cc,$sub['link'][$z]);
                    }
                }
            }
            $colname=trim($cat[2][$i]);
            $bas=$this->_base;
            unset($this->_base);
            $base[$colname] = $this->addtocol($colname,$collin[$i],$bas);
        }
		return $this->saveItemsResult ($base);
	}
	// точки
	public function loadPhysicalPoints ()
	{
        return null;
	}
	// парс новостей
	public function loadNews ()
	{
        $base = array ();
        $url = $this->shopBaseUrl."rus/news/";
		$news = $this->httpClient->getUrlText ($url);

        preg_match_all('!<div class="item">(.*?)<br class="clr" />!si',$news,$itm);

        foreach($itm[1] as $text)
        {
            $news_item = new ParserNews();

            preg_match('#<div class="date"><span>(.+?)</span></div>#sui', $text, $date);
            $news_item->date = $date[1];

            preg_match('#<h4.+?>(.+?)</h4>#sui', $text, $header);
            $news_item->header = $this->txt($header[1]);

            preg_match_all('#<span.+?>(.+?)</span>#sui', $text, $content);
            $news_item->contentShort = $content[1][1];

            preg_match('#<a href="(http://serge-fashion.by/rus/news/.+?)"#sui', $text, $news_url);
            if($news_url)
            {
                $news_item->urlFull = $news_url[1];
                $text = $this->httpClient->getUrlText($this->urlencode_partial($news_item->urlFull));
                preg_match('#</h4>(.+?)<br class="clr" />#sui', $text, $content);
                if($content)
                    $news_item->contentFull = $content[1];
                $news_item->id = mb_substr($news_item->urlFull, mb_strrpos($news_item->urlFull, '=') + 1);
            }

            $news_item->urlShort = $url;

            $base[] = $news_item;
        }


        return $this->saveNewsResult ($base);
	}
}
