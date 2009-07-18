<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

// Get extension configuration
$confArr = unserialize($_EXTCONF);

$doktypes = TYPO3_branch>4.1 ? '1,6' : 2;

if ($confArr['keywordfield']=='tx_pmkautokeywords_autokeywords') {
	
	$tempColumns = Array (
		'tx_pmkautokeywords_autokeywords' => Array (
			'exclude' => 1,		
			'label' => 'LLL:EXT:pmkautokeywords/locallang_db.xml:pages.tx_pmkautokeywords_autokeywords',
			'config' => Array (
				'type' => 'text',
				'cols' => '40',	
				'rows' => '3',
				'readOnly' => 1,
			)
		),
		'tx_pmkautokeywords_useautokeywords' => Array (
			'exclude' => 1,		
			'label' => 'LLL:EXT:pmkautokeywords/locallang_db.xml:pages.tx_pmkautokeywords_useautokeywords',
			'config' => Array (
				'type' => 'check',
				'default' => 1,
			)
		),
	);
	// Add new fields to pages table
	t3lib_div::loadTCA('pages');
	t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
	$TCA['pages']['palettes'][$_EXTKEY]['showitem'] = 'tx_pmkautokeywords_useautokeywords';
	t3lib_extMgm::addToAllTCAtypes('pages','tx_pmkautokeywords_autokeywords;;'.$_EXTKEY.';;',$doktypes,'after:keywords');
	
	// Add new fields to pages_language_overlay table
	t3lib_div::loadTCA('pages_language_overlay');
	t3lib_extMgm::addTCAcolumns('pages_language_overlay',$tempColumns,1);
	$TCA['pages_language_overlay']['palettes'][$_EXTKEY]['showitem'] = 'tx_pmkautokeywords_useautokeywords';
	t3lib_extMgm::addToAllTCAtypes('pages_language_overlay','tx_pmkautokeywords_autokeywords;;'.$_EXTKEY.';;','','after:keywords');
	
	if (t3lib_extMgm::isLoaded('tt_news')) {
		// Add new fields to tt_news table
		t3lib_div::loadTCA('tt_news');
		t3lib_extMgm::addTCAcolumns('tt_news',$tempColumns,1);
		// Remove palettes 4 from the bodytext field, since the meta fields now gets their own tab
		$TCA['tt_news']['types']['0']['showitem'] = preg_replace('/bodytext;;4;/', 'bodytext;;;',$TCA['tt_news']['types']['0']['showitem']);
		$TCA['tt_news']['palettes'][$_EXTKEY]['showitem'] = 'tx_pmkautokeywords_useautokeywords';
		t3lib_extMgm::addToAllTCAtypes('tt_news','--div--;LLL:EXT:cms/locallang_tca.xml:pages.tabs.metadata,keywords,tx_pmkautokeywords_autokeywords;;'.$_EXTKEY.';;','','after:news_files');
	}
	t3lib_extMgm::addStaticFile($_EXTKEY,'static/pmkautokeywords/', 'PMK Autokeywords');
}
else {
	$tempColumns = Array (
		'tx_pmkautokeywords_useautokeywords' => Array (
			'exclude' => 1,		
			'label' => 'LLL:EXT:pmkautokeywords/locallang_db.xml:pages.tx_pmkautokeywords_useautokeywords',
			'config' => Array (
				'type' => 'check',
				'default' => 1,
			)
		),
	);
	// Add new fields to pages table
	t3lib_div::loadTCA('pages');
	t3lib_extMgm::addTCAcolumns('pages',$tempColumns,1);
	t3lib_extMgm::addToAllTCAtypes('pages','tx_pmkautokeywords_useautokeywords;;;;',$doktypes,'after:keywords');
	
	// Add new fields to pages_language_overlay table
	t3lib_div::loadTCA('pages_language_overlay');
	t3lib_extMgm::addTCAcolumns('pages_language_overlay',$tempColumns,1);
	t3lib_extMgm::addToAllTCAtypes('pages_language_overlay','tx_pmkautokeywords_useautokeywords;;;;','','after:keywords');
	
	if (t3lib_extMgm::isLoaded('tt_news')) {
		// Add new fields to tt_news table
		t3lib_div::loadTCA('tt_news');
		t3lib_extMgm::addTCAcolumns('tt_news',$tempColumns,1);
		$TCA['tt_news']['palettes']['4']['showitem'] .= ',tx_pmkautokeywords_useautokeywords';
	}
	
	t3lib_extMgm::addStaticFile($_EXTKEY,'static/pmkautokeywords2/', 'PMK Autokeywords');
	
}

?>