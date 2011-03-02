<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" 
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
        xmlns="http://www.w3.org/1999/xhtml">
        
<xsl:output encoding="utf-8" method="xml" indent="yes"
	doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" 
    doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"  />
    
<!-- ****************************************************************************************** -->
<xsl:template name="cssStyle">
	body
	{
		font-family: Verdana;
		font-size: 9pt;
	}
	
	div.container
	{
		border: 1px solid black;
		margin: 5px;
		padding: 2px;
	}
	
	h1
	{
		padding: 0px;
		margin: 0px;
	}
	
	a { color: #2f2fb7; }
	
	table.StdTable
	{
		border: 1px solid #0a80d8;
		background-color: #D9D9D9;
		width: 100%;
		margin-bottom: 10px;
	}

	table.StdTable tr.even td { background-color: #ededed; }

	table.StdTable th
	{
		text-align: center;
		font-weight: bold;
		background-color: #1075bd;
		color: white;
	}

	table.StdTable td
	{
		text-align: left;
		background-color: #FFF9F2;
		color: black;
	}
	
	ul { padding: 0px; margin: 0px; padding-left: 15px; }
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template name="allScripts">
	function toggle(id)
	{
		var obj = document.getElementById (id);
		obj.style.display = (obj.style.display == 'none' || obj.style.display == '') ? 'block' : 'none';
		return false;
	}
	
	function toggleImages(id)
	{
		var obj = document.getElementById (id);
		obj.style.display = (obj.style.display == 'none' || obj.style.display == '') ? 'block' : 'none';
		var images = obj.getElementsByTagName ('img');
		
		if (images.length != 0)
		{
			for (var i = 0; i != images.length; i++)
			{
				images[i].src = images[i].getAttribute ('srcOnShow');
			}
		}
		return false;
	}
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template name="Head_Tag">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title><xsl:value-of select="/page/@title" /></title>
		
		<style type="text/css">
			<xsl:call-template name="cssStyle" />
		</style>
		
		<script type="text/javascript">
			<xsl:call-template name="allScripts" />
		</script>
	</head>
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template match="/">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<xsl:call-template name="Head_Tag" />
		<body>
			<xsl:call-template name="HtmlBodyDesign" />
		</body>
	</html>
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template match="/page/news">
	<div id="news" style="display: none;">
		<table cellpadding="3" cellspacing="1" class="StdTable">
			<tr>
				<th title="ID новости на сайте">id</th>
				<th title=" Дата размещения новости на сайте в формате ГГГГ-ММ-ДД,
								либо ДД.ММ.ГГГГ. Нули дописывать в день и месяц не
								обязательно. ">date</th>
				<th title="URL страницы с плоным текстом новости">urlShort</th>
				<th title="URL страницы с кратким текстом новости.">urlFull</th>
				<th title="Заголовок новости (берётся либо из анонса, либо из полного
								текста новости).">header</th>
				<th title="Краткий текст в HTML-формате (если в анонсе есть картинка,
							   её сюда вставлять не нужно)">contentShort</th>
				<th title="Полный текст в HTML-формате">contentFull</th>
			</tr>
			
			<xsl:for-each select="./block">
				<tr>
					<xsl:if test='position() mod 2 = 0'><xsl:attribute name="class">even</xsl:attribute></xsl:if>
					<td><xsl:value-of select="@id" /></td>
					<td><xsl:value-of select="@date" /></td>
					<td>
						<a href="{@urlShort}" target="_blank">
							<xsl:value-of select="@urlShort" />
						</a>
					</td>
					<td>
						<a href="{@urlFull}" target="_blank">
							<xsl:value-of select="@urlFull" />
						</a>
					</td>
					<td><xsl:value-of select="@header" /></td>
					<td><xsl:value-of select="./contentShort" /></td>
					<td>
						<a href="#" onclick="return toggle('newsFullText_{@__id}');">
							Показать 
						</a> 
					</td>
				</tr>
				<tr>
					<td colspan="7">
						<div id="newsFullText_{@__id}" style="display: none;">
						<xsl:value-of select="./contentFull" />
						</div>
					</td>
				</tr>
			</xsl:for-each>
		</table>
	</div>
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template match="/page/physPoints">
	<div id="physPoints" style="display: none;">
		<table cellpadding="3" cellspacing="1" class="StdTable">
			<tr>
				<th title="ID торговой точки на сайте">id</th>
				<th title="Город, в котором расположена торговая точка. Для московской и ленинградском 
					областей писать 'Москва' и 'Санкт-Петербург' соответственно.">city</th>
				<th title="Адрес торговой точки без города.">address</th>
				<th title="Все телефоны торговой точки.">phone</th>
				<th title="является-ли сток(дисконт)-центром (1 или 0). Значение устанавливается
						   только если эта информация есть на сайте.">b_stock</th>
				<th title="Режим работы.">timetable</th>
				<th title="Закрыта-ли торговая точка (1 или 0). Значение устанавливается
						   только если эта информация есть на сайте.">b_closed</th>
			</tr>
			
			<xsl:for-each select="./phys">
				<tr>
					<xsl:if test='position() mod 2 = 0'><xsl:attribute name="class">even</xsl:attribute></xsl:if>
					<td>
						<xsl:if test='@physIndex'>
							<span style="font-weight: bold;"><xsl:value-of select="@physIndex" /></span>
							<br />
						</xsl:if>
						<xsl:value-of select="@id" />
					</td>
					<td><xsl:value-of select="@city" /></td>
					<td><xsl:value-of select="@address" /></td>
					<td><xsl:value-of select="@phone" /></td>
					<td><xsl:value-of select="@b_stock" /></td>
					<td><xsl:value-of select="@timetable" /></td>
					<td><xsl:value-of select="@b_closed" /></td>
				</tr>
			</xsl:for-each>
		</table>
	</div>
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template match="/page/collections">
	<div id="collections"  style="display: none;">
		<xsl:for-each select="./collection">
			<table cellpadding="3" cellspacing="1" class="StdTable">
				<tr>
					<th style="width: 100px;" title="ID коллекции на сайте. Если нету - сгенерировать по имени.
						   По этому ID будет определяться, появилась-ли новая коллекция  на сайте.
						   Например: http://incity.ru/collection/3/
						   Здесь нужно делать id = '3 ВЕСНА-ЛЕТО 2010', т. к. по этому
						   УРЛу могут выдаваться новые коллекции.">id</th>
					<th style="width: 150px;" title="Название коллекции">name</th>
					<th title="Описание коллекции">descr</th>
				</tr>
				<tr>
					<td>
						<a href="{@url}" target="_blank">
							<xsl:value-of select="@id" />
						</a>
					</td>
					<td><xsl:value-of select="@name" /></td>
					<td><xsl:value-of select="@descr" /></td>
				</tr>
				<tr>
					<td colspan="3" style="text-align: center;">
						<a href="#" onclick="return toggle('itemsOfCol_{@__id}');">
							Показать товары
						</a> 
						 (<xsl:value-of select="count(./item)" />)
						<div id="itemsOfCol_{@__id}" style="display: none;">
							<table cellpadding="3" cellspacing="1" class="StdTable">
								<tr>
									<th title="ID товара на сайте">id</th>
									<th title="Название товара или артикул">name</th>
									<th title="Артикул">articul</th>
									<th title="Цена товара без скидки (число, никаких 'р', 'руб' не нужно)">
										price
									</th>
									<th title="Скидка в процентах">discount</th>
									<th title="Название категории. Если используется иерархический путь, то в виде массива">categ</th>
									<th title="Материал">material</th>
									<th title="Состав">structure</th>
									<th title="Страна-производитель">made_in</th>
									<th title="Вес">weight</th>
									<th title="Массив значений доступных размеров">sizes</th>
									<th title="Массив RGB/HEX строк с цветами (предпочтительнее), либо массив названий.
    						Т. е. для HEX записывать: #xxxxxx 
    						Для RGB:				  rgb(xxx,xxx,xxx)">colors</th>
									<th title="Описание товара.">descr</th>
									<th title="Наличие в продаже: 1 или 0. Если на сайте явно не указано,
						   в наличии этот товар или нет, то не заполнять это поле.">bStock</th>
						   			<th title="Бренд товара (фирма-производитель)">brand</th>
						   			<th title="Массив картинок товара (ParserImage)">images</th>
								</tr>
								<xsl:apply-templates select="./item" />
							</table>
						</div>
					</td>
				</tr>
			</table>
		</xsl:for-each>
	</div>
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template match="collections/collection/item">
	<tr>
		<xsl:if test='position() mod 2 = 0'><xsl:attribute name="class">even</xsl:attribute></xsl:if>
		<td>
			<a href="{@url}" target="_blank">
				<xsl:value-of select="@id" />
			</a>
		</td>
		<td><xsl:value-of select="@name" /></td>
		<td><xsl:value-of select="@articul" /></td>
		<td><xsl:value-of select="@price" /></td>
		<td><xsl:value-of select="@discount" /></td>
		<td>	
			<xsl:choose>
				<xsl:when test='./categ'>
					<ul>
						<xsl:for-each select="./categ/node">
							<li><xsl:value-of select="@value" /></li>						 
						</xsl:for-each>
					</ul>
				</xsl:when>
				<xsl:otherwise><xsl:value-of select="@categ" /></xsl:otherwise>
			</xsl:choose>
		</td>
		<td><xsl:value-of select="@material" /></td>
		<td><xsl:value-of select="@structure" /></td>
		<td><xsl:value-of select="@made_in" /></td>
		<td><xsl:value-of select="@weight" /></td>
		<td>
			<xsl:choose>
				<xsl:when test='./sizes'>
					<ul>
						<xsl:for-each select="./sizes/node">
							<li><xsl:value-of select="@value" /></li>						 
						</xsl:for-each>
					</ul>
				</xsl:when>
				<xsl:otherwise><xsl:value-of select="@sizes" /></xsl:otherwise>
			</xsl:choose>
		</td>
		<td>
			<xsl:choose>
				<xsl:when test='./colors'>
					<ul>
						<xsl:for-each select="./colors/node">
							<li><xsl:value-of select="@value" /></li>						 
						</xsl:for-each>
					</ul>
				</xsl:when>
				<xsl:otherwise><xsl:value-of select="@colors" /></xsl:otherwise>
			</xsl:choose>
		</td>
		<td><xsl:value-of select="@descr" /></td>
		<td><xsl:value-of select="@bStock" /></td>
		<td><xsl:value-of select="@brand" /></td>
		<td>
			<a href="#" onclick="return toggleImages('imagesOfItem_{@__id}');">
				<xsl:value-of select="count(./images/image)" />
			</a> 
		</td>
	</tr>
	<tr>
		<td colspan="16" style="text-align: center;">
			<div style="display: none;" id="imagesOfItem_{@__id}">
				<xsl:for-each select="./images/image">
					<a href="{@url}" target="_blank">
						<img srcOnShow="{@path}" title="ID: '{@id}', type: '{@type}', fname: '{@fname}'" 
							style="border: none;" />
					</a>
				</xsl:for-each>
			</div>
		</td>
	</tr>
</xsl:template>
<!-- ****************************************************************************************** -->
<xsl:template name="HtmlBodyDesign">
	<div class="container">
		<h1>
			<a href="#" onclick="return toggle('collections');">Коллекции</a>
				(<xsl:value-of select="count(/page/collections/collection)" />)
		</h1>
		<xsl:apply-templates select="/page/collections" />
	</div>
	<div class="container">
		<h1>
			<a href="#" onclick="return toggle('physPoints');">Торговые точки</a> 
			(<xsl:value-of select="count(/page/physPoints/phys)" />)
		</h1>
		<xsl:apply-templates select="/page/physPoints" />
	</div>
	<div class="container">
		<h1>
			<a href="#" onclick="return toggle('news');">Акции/новости</a> 
			(<xsl:value-of select="count(/page/news/block)" />)
		</h1>
		<xsl:apply-templates select="/page/news" />
	</div>
</xsl:template>
<!-- ****************************************************************************************** -->

</xsl:stylesheet>
