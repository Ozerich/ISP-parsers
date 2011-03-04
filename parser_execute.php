<?php

/*********************************************************************/

mb_internal_encoding ('utf-8');
error_reporting (E_ALL | E_STRICT);

ini_set("max_execution_time", "600000");

define ('PARSERS_BASE_DIR',    		'.');
define ('ZEND_FRAMEWORK_PATH', 		PARSERS_BASE_DIR . '/zf-minimal');
define ('SITES_PARSERS_DATA_PATH', 	PARSERS_BASE_DIR . '/data');

set_include_path(get_include_path() . PATH_SEPARATOR . ZEND_FRAMEWORK_PATH);
require_once PARSERS_BASE_DIR . '/parsers/parserBase.php';
require_once PARSERS_BASE_DIR . '/debugFunctions.php';

/*********************************************************************/

$allParsers = array 
(
	'1'	=> new ParserExecuteInfo ('ozerich/almazholding_ru.php', 		'ISP_almazholding_ru'),		
	'2'	=> new ParserExecuteInfo ('ozerich/ringo_info.php', 		'ISP_ringo_info'),	
	'3'	=> new ParserExecuteInfo ('ozerich/wlogic_ru.php', 		'ISP_wlogic_ru'),	
	'4'	=> new ParserExecuteInfo ('ozerich/ray_sport_ru.php', 		'ISP_ray_sport_ru'),	
	'5'	=> new ParserExecuteInfo ('ozerich/estelle_ru.php', 		'ISP_estelle_ru'),	
	'6'	=> new ParserExecuteInfo ('ozerich/francesco_ru.php', 		'ISP_francesco_ru'),	
	'7'	=> new ParserExecuteInfo ('ozerich/olehouse_ru.php', 		'ISP_olehouse_ru'),
	'8'	=> new ParserExecuteInfo ('ozerich/mizuno_eu.php', 		'ISP_mizuno_eu'),
	'9'	=> new ParserExecuteInfo ('ozerich/pjl73_ru.php', 		'ISP_pjl73_ru'),
	'11'	=> new ParserExecuteInfo ('ozerich/zimaletto_ru.php', 		'ISP_zimaletto_ru'),
	'12'	=> new ParserExecuteInfo ('ozerich/td_charm_ru.php', 		'ISP_td_charm_ru'),
	'13'	=> new ParserExecuteInfo ('ozerich/cashmere_ru.php', 		'ISP_cashmere_ru'),
	'14'	=> new ParserExecuteInfo ('ozerich/moda_comfort_ru.php', 		'ISP_moda_comfort_ru'),
	'15'	=> new ParserExecuteInfo ('ozerich/shoes_ru.php', 		'ISP_shoes_ru'),
	'16'	=> new ParserExecuteInfo ('ozerich/stylepark_ru.php', 		'ISP_stylepark_ru'),
	'17'	=> new ParserExecuteInfo ('ozerich/attirance_ru.php', 		'ISP_attirance_ru'),
	'18'	=> new ParserExecuteInfo ('ozerich/bottegaverde_ru.php', 		'ISP_bottegaverde_ru'),
	'19'	=> new ParserExecuteInfo ('ozerich/buono_ru.php', 		'ISP_buono_ru'),
	'10'	=> new ParserExecuteInfo ('ozerich/courtney_g_ru.php', 		'ISP_courtney_g_ru'),
    '20'	=> new ParserExecuteInfo ('ozerich/wildorchid_ru.php', 		'ISP_wildorchid_ru', 1884),
    '21'	=> new ParserExecuteInfo ('ozerich/wildorchid_ru.php', 		'ISP_wildorchid_ru', 5229),
    '22'	=> new ParserExecuteInfo ('ozerich/wildorchid_ru.php', 		'ISP_wildorchid_ru', 22491),
    '23'	=> new ParserExecuteInfo ('ozerich/wildorchid_ru.php', 		'ISP_wildorchid_ru', 22492),
    '24'    => new ParserExecuteInfo ('ozerich/wildorchid_ru.php',      'ISP_wildorchid_ru', 1030),
	'25'	=> new ParserExecuteInfo ('ozerich/rivegauche_ru.php', 		'ISP_rivegauche_ru'),
	'26'	=> new ParserExecuteInfo ('ozerich/litgen_ru.php', 		'ISP_litgen_ru'),
	'27'	=> new ParserExecuteInfo ('ozerich/zoloto585_ru.php', 		'ISP_zoloto585_ru'),
	'28'	=> new ParserExecuteInfo ('ozerich/charmante_ru.php', 		'ISP_charmante_ru'),
	'29'	=> new ParserExecuteInfo ('ozerich/clairesrussia_ru.php', 		'ISP_clairesrussia_ru'),
	'30'	=> new ParserExecuteInfo ('ozerich/steilmann_ru.php', 		'ISP_steilmann_ru'),
	'30'	=> new ParserExecuteInfo ('ozerich/steilmann_ru.php', 		'ISP_steilmann_ru'),
	'31'	=> new ParserExecuteInfo ('ozerich/deffinesse_net.php', 		'ISP_deffinesse_net'),
	'32'	=> new ParserExecuteInfo ('ozerich/olgood_ru.php', 		'ISP_olgood_ru'),
	'33'	=> new ParserExecuteInfo ('ozerich/ugdvor_ru.php', 		'ISP_ugdvor_ru'),
	'34'	=> new ParserExecuteInfo ('ozerich/donatto_ru.php', 		'ISP_donatto_ru'),
	'35'	=> new ParserExecuteInfo ('ozerich/edmins_ru.php', 		'ISP_edmins_ru'),
	'36'	=> new ParserExecuteInfo ('ozerich/eternel_ru.php', 		'ISP_eternel_ru'),
	'37'	=> new ParserExecuteInfo ('ozerich/eyekraftoptical_ru.php', 		'ISP_eyekraftoptical_ru'),    
	'50'	=> new ParserExecuteInfo ('ozerich/inwm_ru.php', 		'ISP_inwm_ru'),
	'51'	=> new ParserExecuteInfo ('ozerich/minomin_ru.php', 		'ISP_minomin_ru'),
	'51'	=> new ParserExecuteInfo ('ozerich/naracamicie_ru.php', 		'ISP_naracamicie_ru'),
	'52'	=> new ParserExecuteInfo ('ozerich/piaget_com.php', 		'ISP_piaget_com'),
	'53'	=> new ParserExecuteInfo ('ozerich/sepalla_ru.php', 		'ISP_sepalla_ru'),
	'54'	=> new ParserExecuteInfo ('ozerich/sofrench_ru.php', 		'ISP_sofrench_ru'),
	'55'	=> new ParserExecuteInfo ('ozerich/uomo_ru.php', 		'ISP_uomo_ru'),
	'56'	=> new ParserExecuteInfo ('ozerich/vanlaack_de.php', 		'ISP_vanlaack_de'),
	'57'	=> new ParserExecuteInfo ('ozerich/velars_ru.php', 		'ISP_velars_ru'),
	'58'	=> new ParserExecuteInfo ('ozerich/new_j_ru.php', 		'ISP_new_j_ru'),
	'59'	=> new ParserExecuteInfo ('ozerich/planetakolgotok_ru.php', 		'ISP_planetakolgotok_ru'),
	'60'	=> new ParserExecuteInfo ('ozerich/sateg_ru.php', 		'ISP_sateg_ru'),
	'61'	=> new ParserExecuteInfo ('ozerich/ecolas_ru.php', 		'ISP_ecolas_ru'),
	'62'	=> new ParserExecuteInfo ('ozerich/elegant_ru.php', 		'ISP_elegant_ru'),
	'63'	=> new ParserExecuteInfo ('ozerich/enton_ru.php', 		'ISP_enton_ru'),
	'64'	=> new ParserExecuteInfo ('ozerich/kiddy_russia_ru.php', 		'ISP_kiddy_russia_ru'),
	'65'	=> new ParserExecuteInfo ('ozerich/kinderland_ru.php', 		'ISP_kinderland_ru'),
	'66'	=> new ParserExecuteInfo ('ozerich/oharamania_ru.php', 		'ISP_oharamania_ru'),
	'67'	=> new ParserExecuteInfo ('ozerich/premaman_ru.php', 		'ISP_premaman_ru'),
	'68'	=> new ParserExecuteInfo ('ozerich/rikki_tikki_ru.php', 		'ISP_rikki_tikki_ru'),
	'69'	=> new ParserExecuteInfo ('ozerich/sweetmama_ru.php', 		'ISP_sweetmama_ru'),
	'70'	=> new ParserExecuteInfo ('ozerich/skvot_com.php', 		'ISP_skvot_com'),
	'71'	=> new ParserExecuteInfo ('ozerich/cityobuv_ru.php', 		'ISP_cityobuv_ru'),
	'72'	=> new ParserExecuteInfo ('ozerich/mothercare_ru.php', 		'ISP_mothercare_ru'),
	'73'	=> new ParserExecuteInfo ('ozerich/savage_ru.php', 		'ISP_savage_ru'),
	'74'	=> new ParserExecuteInfo ('ozerich/consul_ru.php', 		'ISP_consul_ru'),
	'75'	=> new ParserExecuteInfo ('ozerich/eromoda_ru.php', 		'ISP_eromoda_ru'),

    
    
    
    
    
    
); 

/*********************************************************************/

// checkForNewParsers($allParsers);

if(isset($_GET['id']))
    $shopId = $_GET['id'];
else
    $shopId = $argv[1];

$shopExecuteInfo = $allParsers[$shopId]; 

print ("Executing script '" . $shopExecuteInfo->getFilePath() . "'...\n");
saveParseResultToHTML ($shopExecuteInfo, './parseResult.xsl');

// Для отладки только товаров:
// parseItems ($allParsers[$shopId], 'splice' /* 'splice' 'count' null */);

// Для отладки только торговых точек: 
// parsePhysical ($allParsers[$shopId]);

// Для отладки только новостей: 
// parseNews ($allParsers[$shopId]);

