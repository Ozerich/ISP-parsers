<?php

/*********************************************************************/

function parseItems ($parserExecuteInfo, $postProcess = null)
{
	$parser = ItemsSiteParser::getInstanceByExecuteInfo ($parserExecuteInfo);
	
	try
	{	
		$siteItemsPath    = $parser->loadItems();
		if ($siteItemsPath === false)
			die ("Can't download items!\n");

		print ("Site items downloaded to: '$siteItemsPath'\n");
		
		$base = unserialize (file_get_contents ($siteItemsPath));
		if ( ! $postProcess)
		{
			print_r ($base);
			return;
		}
	
		foreach ($base as & $col)
	 	{
	 			if ($postProcess == 'count')
		 			$col->items = count ($col->items);
		 		elseif ($postProcess == 'splice')
					array_splice($col->items, 2);
				else
					die ("UNKNOWN POST PROCESS: '$postProcess'\n");
		}
		
		print_r ($base);
	}
	catch (Exception $e)
	{
		print ("\n\n\nFATAL EXCEPTION: " . $e . "\n\n\n");
		exit (1);
	}
}

/*********************************************************************/

function parseNews ($parserExecuteInfo)
{
	$parser = ItemsSiteParser::getInstanceByExecuteInfo ($parserExecuteInfo);
	
	try
	{	
		$newsPath = $parser->loadNews();
		if ($newsPath === false)
			die ("Can't download news!\n");

		print ("Site news downloaded to: '$newsPath'\n");

		$base = unserialize (file_get_contents ($newsPath));
		print_r ($base);
	}
	catch (Exception $e)
	{
		print ("\n\n\nFATAL EXCEPTION: " . $e . "\n\n\n");
		exit (1);
	}
}

/*********************************************************************/

function parsePhysical ($parserExecuteInfo)
{
	$parser = ItemsSiteParser::getInstanceByExecuteInfo ($parserExecuteInfo);
	
	try
	{	
		$sitePhysicalPath = $parser->loadPhysicalPoints();
		if ($sitePhysicalPath === false)
			die ("Can't download physical points!\n");

		print ("Site items downloaded to: '$sitePhysicalPath'\n");

		$base = unserialize (file_get_contents ($sitePhysicalPath));
		print_r ($base);
	}
	catch (Exception $e)
	{
		print ("\n\n\nFATAL EXCEPTION: " . $e . "\n\n\n");
		exit (1);
	}
}

/*********************************************************************/

function dom2html ($domDoc, $xsltTemplatePath)
{
	$xmlTransform = new DOMDocument('1.0', 'utf-8');
	if ( ! $xmlTransform->load ($xsltTemplatePath))
		return false;
		
	$xsltProc = new XSLTProcessor();
	$xsltProc->importStylesheet($xmlTransform);
	$content = $xsltProc->transformToXml($domDoc);
	
	return $content;
}

/*********************************************************************/

function appendItemsToDom ($xRoot, $domResult, $baseColItems)
{
	$xCollections = $domResult->createElement ('collections');
	$xRoot->appendChild ($xCollections);
	
	$colIdx = -1;
	$itemIdx = -1;
	foreach ($baseColItems as $collection)
	{
		$colIdx++;
		$xCollection = $domResult->createElement ('collection');
		$xCollections->appendChild ($xCollection);
		$xCollection->setAttribute ('__id', $colIdx);
		
		foreach ($collection as $k => $v)
		{
			if ($k == 'items')
				continue;
				
			$xCollection->setAttribute ($k, $v);
		}
		
		foreach ($collection->items as $item)
		{
			$itemIdx++;
			$xItem = $domResult->createElement ('item');
			$xCollection->appendChild ($xItem);
			$xItem->setAttribute ('__id', $itemIdx);
			
			foreach ($item as $k => $v)
			{
				if ( ! is_array ($v))
				{
					$v = @iconv ('utf-8', 'utf-8' . "//TRANSLIT//IGNORE", $v);
					try
					{
						$xItem->setAttribute ($k, $v);
					}
					catch (Exception $e)
					{
						print ("Can't set attribute pair '$k' = '$v'\n");
						print ("EXCEPTION TEXT: " . $e . "\n");
						die ("\n");
					}
					continue;
				}
			
				$xKey = $domResult->createElement ($k);
				$xItem->appendChild ($xKey); 
			
				if ($k == 'images')
				{
					foreach ($v as $image)
					{
						$xImage = $domResult->createElement ('image');
						$xKey->appendChild ($xImage);

						foreach ($image as $k2 => $v2)
							$xImage->setAttribute ($k2, $v2);
					}
					continue;
				}
				
				foreach ($v as $innerValue)
				{
					$innerValue = @iconv ('utf-8', 'utf-8' . "//TRANSLIT//IGNORE", $innerValue);
					$xNode = $domResult->createElement ('node');
					$xKey->appendChild ($xNode);
					$xNode->setAttribute ('value', $innerValue);
				}
			}
		}
	}
}

/*********************************************************************/

function appendPhysicalToDom ($xRoot, $domResult, $basePhys)
{
	$xPhysPoints  = $domResult->createElement ('physPoints');
	$xRoot->appendChild ($xPhysPoints);
	
	foreach ($basePhys as $physIndex => $phys)
	{
		if ($phys instanceof ParserPhysical)
		{
			$xPhys = $domResult->createElement ('phys');
			$xPhysPoints->appendChild ($xPhys);
			foreach ($phys as $k => $v)
			{
				$v = @iconv ('utf-8', 'utf-8' . "//TRANSLIT//IGNORE", $v);
				try
				{
					$xPhys->setAttribute ($k, $v);
				}
				catch (Exception $e)
				{
					print ("Exception: " . $e . "\n\n");
				}
			}
			continue;
		}
		
		foreach ($phys as $innerIndex => $physInfo)
		{
			$xPhys = $domResult->createElement ('phys');
			$xPhys->setAttribute ('physIndex', $physIndex);
			$xPhysPoints->appendChild ($xPhys);
			
			foreach ($physInfo as $k => $v)
			{
				$v = @iconv ('utf-8', 'utf-8' . "//TRANSLIT//IGNORE", $v);
				try
				{
					$xPhys->setAttribute ($k, $v);
				}
				catch (Exception $e)
				{
					print ("Exception: " . $e . "\n\n");
				}
			}
		}
	}
}

/*********************************************************************/

function appendNewsToDom ($xRoot, $domResult, $baseNews)
{
	$xNews = $domResult->createElement ('news');
	$xRoot->appendChild ($xNews);
	$blockIdx = -1;
	foreach ($baseNews as $block)
	{
		$xBlock = $domResult->createElement ('block');
		$xNews->appendChild ($xBlock);
		$xBlock->setAttribute ('__id', ++$blockIdx);
		
		foreach ($block as $k => $v)
		{
			if ($k != 'contentShort' and $k != 'contentFull')
			{
				$xBlock->setAttribute ($k, $v);
				continue;
			}
			
			$xNode = $domResult->createElement ($k);
			$xBlock->appendChild ($xNode);
			
			$cdata = $domResult->createCDATASection ($v);
			$xNode->appendChild ($cdata);
		}
	}
}

/*********************************************************************/

function createParserDomByInfo ($baseColItems = null, $basePhys = null, 
	$baseNews = null, $title = null)
{
	$domResult = new DOMDocument('1.0', 'utf-8');
	$xRoot = $domResult->createElement ('page');
	$domResult->appendChild ($xRoot);
	if ($title)
		$xRoot->setAttribute ('title', $title);

	if ($baseColItems)
		appendItemsToDom ($xRoot, $domResult, $baseColItems);
		
	if ($basePhys)
		appendPhysicalToDom ($xRoot, $domResult, $basePhys);
		
	if ($baseNews)
		appendNewsToDom ($xRoot, $domResult, $baseNews);
		
	return $domResult;
}

/*********************************************************************/

function saveParseResultToHTML ($parserExecuteInfo, $templatePath, $saveDir = null,
	$results = array ())
{
	$parser = ItemsSiteParser::getInstanceByExecuteInfo ($parserExecuteInfo);
	
	try
	{	
		$siteItemsPath    = isset ($results['items']) ? $results['items'] : $parser->loadItems();
		if ($siteItemsPath === false)
			die ("Can't download items!\n");
		print ("Site items parsed to: '$siteItemsPath'\n");
		$baseColItems = $siteItemsPath ? unserialize (file_get_contents ($siteItemsPath)) : array();
		
		$sitePhysicalPath = isset ($results['phys']) ? $results['phys'] : $parser->loadPhysicalPoints();
		if ($sitePhysicalPath === false)
			die ("Can't download physical points!\n");
		print ("Site physical parsed to: '$sitePhysicalPath'\n");
		$basePhys = $sitePhysicalPath ? unserialize (file_get_contents ($sitePhysicalPath)) : array ();
		
		$siteNewsPath     = isset ($results['news']) ? $results['news'] : $parser->loadNews();
		if ($siteNewsPath === false)
			die ("Can't downloads news!\n");
		print ("News parsed to: '$siteNewsPath'\n");
		$baseNews = $siteNewsPath ? unserialize (file_get_contents ($siteNewsPath)) : array ();
	}
	catch (Exception $e)
	{
		print ("\n\n\nFATAL EXCEPTION: " . $e . "\n\n\n");
		exit (1);
	}
	
	$domResult = createParserDomByInfo ($baseColItems, $basePhys, 
		$baseNews, $parserExecuteInfo->getFilePath());
	
	$content = dom2html ($domResult, $templatePath);
	if ($content === false)
		die ("Can't transform dom with template '$templatePath'\n");
	
	if ( ! $saveDir)
		$saveDir = '.';
		
	$savePath = $saveDir . '/' . $parserExecuteInfo->getClassName() . '.html';
	if (file_put_contents($savePath, $content) === false)
		die ("Can't save data to '$savePath'!\n");
		
	$savePath = $saveDir . '/' . $parserExecuteInfo->getClassName() . '.xml';
	if ($domResult->save($savePath) === false)
		die ("Can't save data to '$savePath'!\n");
}

/*********************************************************************/

function checkForNewParsers ($allParsers)
{
	$new = array ();
	$cur = array ();
	
	foreach ($allParsers as $r)
		$cur[] = $r->getFilePath();

	$baseDir = PARSERS_BASE_DIR . '/parsers/sites';
	$dirs = scandir ($baseDir);
	if ($dirs === false)
		die ("Can't read dir $baseDir\n");
		
	foreach ($dirs as $dir)
	{
		$dirPath = $baseDir . '/' . $dir;
		if ( ! ($dir != '.' and $dir != '..' and is_dir ($dirPath)))
			continue;
			
		$files = scandir ($dirPath);
		if ($files === false)
			die ("Can't read dir '$dirPath'!\n");

		foreach ($files as $fName)
		{
			if (substr ($fName, -4) != '.php')
				continue;
				
			$relPath = $dir . '/' . $fName;
			$fullPath = $baseDir . '/' . $relPath;
			if (in_array ($relPath, $cur))
				continue;
			
			$classesBefore = get_declared_classes();
			require_once $fullPath;
			$classesAfter  = get_declared_classes();
				
			$classNames = array_diff ($classesAfter, $classesBefore);
			$foundNewClass = null;
			foreach ($classNames as $cn)
			{
				if (substr ($cn, 0, 4) != 'ISP_')
					continue;
					
				if ($foundNewClass)
					die ("Found 2 ISP_* classes while including '$fullPath'\n");
				$foundNewClass = $cn; 
			}
				
			$new[$foundNewClass] = $relPath;
		}
	}		
	
	if (empty ($new))
		return;
		
	print ("Found new parsers:\n");
	foreach ($new as $class => $relPath)
	{
		print ("\t0000\t=> new ParserExecuteInfo ('$relPath',\t\t'$class'),\n");
	}
		
	/*
	print ("Found new parsers: " . print_r ($new, true) . "\n");
	*/
	exit (0);
}

/*********************************************************************/
