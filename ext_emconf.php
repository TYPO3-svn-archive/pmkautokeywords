<?php

########################################################################
# Extension Manager/Repository config file for ext: "pmkautokeywords"
#
# Auto generated 18-07-2009 20:29
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'PMK Autokeywords',
	'description' => 'Generates META keywords from page/content and tt_news records when page/content/news is created or updated. Supports multiple language sites and TemplaVoila',
	'category' => 'be',
	'author' => 'Peter Klein',
	'author_email' => 'peter@umloud.dk',
	'shy' => '',
	'dependencies' => 'static_info_tables,cms,lang',
	'conflicts' => 'autokeywords,autokeywordz,mc_autokeywords',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'pages,pages_language_overlay,tt_news',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'Umloud Untd',
	'version' => '1.1.5',
	'constraints' => array(
		'depends' => array(
			'php' => '5.0.0-0.0.0',
			'typo3' => '3.9.9-0.0.0',
			'static_info_tables' => '',
			'cms' => '',
			'lang' => '',
		),
		'conflicts' => array(
			'autokeywords' => '',
			'autokeywordz' => '',
			'mc_autokeywords' => '',
		),
		'suggests' => array(
			'templavoila' => '1.3.0-0.0.0',
			'tt_news' => '2.5.0-0.0.0',
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:20:"class.ext_update.php";s:4:"675b";s:28:"class.tx_pmkautokeywords.php";s:4:"a94c";s:21:"ext_conf_template.txt";s:4:"6d8e";s:12:"ext_icon.gif";s:4:"1bd3";s:17:"ext_localconf.php";s:4:"12f0";s:15:"ext_php_api.dat";s:4:"2bdd";s:14:"ext_tables.php";s:4:"9cfd";s:14:"ext_tables.sql";s:4:"796b";s:13:"locallang.xml";s:4:"3e08";s:16:"locallang_db.xml";s:4:"2f91";s:14:"doc/manual.sxw";s:4:"62dd";s:36:"static/pmkautokeywords/constants.txt";s:4:"284a";s:32:"static/pmkautokeywords/setup.txt";s:4:"b7dd";s:37:"static/pmkautokeywords2/constants.txt";s:4:"284a";s:33:"static/pmkautokeywords2/setup.txt";s:4:"8fc2";}',
	'suggests' => array(
	),
);

?>