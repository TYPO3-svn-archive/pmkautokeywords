<?php
	/***************************************************************
	*  Copyright notice
	*
	*  (c) 2008 Peter Klein <peter@umloud.dk>
	*  All rights reserved
	*
	*  This script is part of the TYPO3 project. The TYPO3 project is
	*  free software; you can redistribute it and/or modify
	*  it under the terms of the GNU General Public License as published by
	*  the Free Software Foundation; either version 2 of the License, or
	*  (at your option) any later version.
	*
	*  The GNU General Public License can be found at
	*  http://www.gnu.org/copyleft/gpl.html.
	*
	*  This script is distributed in the hope that it will be useful,
	*  but WITHOUT ANY WARRANTY; without even the implied warranty of
	*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	*  GNU General Public License for more details.
	*
	*  This copyright notice MUST APPEAR in all copies of the script!
	***************************************************************/

require_once('class.tx_pmkautokeywords.php');
require_once(PATH_t3lib.'class.t3lib_page.php');
	
/**
 * Class for updating the db
 *
 * @author	 Peter Klein <peter@umloud.dk>
 */
class ext_update extends tx_pmkautokeywords {

	/**
	 * Main function, returning the HTML content of the module
	 *
	 * @return	string		HTML
	 */
	function main()	{
		global $LANG;
		$LANG->includeLLFile(t3lib_div::getFileAbsFileName('EXT:pmkautokeywords/locallang.xml'));

		$rootPages = $this->getRecords('pages','pid=0');
		$rootPage = intval(t3lib_div::_GP('branch'));
		$this->mode = intval(t3lib_div::_GP('mode'))===1 ? 1 : 0;;
		$this->triggerUpdate = intval(t3lib_div::_GP('triggerupdate'))===1 ? 1 : 0;
		$this->triggerNewsUpdate = intval(t3lib_div::_GP('triggernewsupdate'))===1 ? 1 : 0;
		$content = '<form name="pmkautokeywords_form" action="'.htmlspecialchars(t3lib_div::linkThisScript()).'" method="post">';
		$content .= '<p>'.$LANG->getLL('text.branch').'</p>';
		$content .= '<p>'.$this->makeSelect($rootPages,$rootPage,'branch').'</p><p>&nbsp;</p>';
		$content .= '<p>'.$LANG->getLL('text.mode').'</p>';
		$content .= '<p>'.$this->makeSelect(array(array('title' => $LANG->getLL('option.checked'), 'uid' => 1), array('title' => $LANG->getLL('option.unchecked'), 'uid' => 2)),$this->mode,'mode').'</p><p>&nbsp;</p>';
		$content .= '<p>'.$LANG->getLL('text.trigger').'</p>';
		$content .= '<p>'.$this->makeSelect(array(array('title' => $LANG->getLL('option.yes'), 'uid' => 1), array('title' => $LANG->getLL('option.no'), 'uid' => 2)),$this->triggerUpdate,'triggerupdate').'</p><p>&nbsp;</p>';
		if (t3lib_extMgm::isLoaded('tt_news')) {
			$content .= '<p>'.$LANG->getLL('text.trigger_tt_news').'</p>';
			$content .= '<p>'.$this->makeSelect(array(array('title' => $LANG->getLL('option.yes'), 'uid' => 1), array('title' => $LANG->getLL('option.no'), 'uid' => 2)),$this->triggerNewsUpdate,'triggernewsupdate').'</p><p>&nbsp;</p>';
		}
		$content .= '<input type="submit" name="update" value="'.$LANG->getLL('button.update').'" />';
		$content .= '</form>';
		if (t3lib_div::_GP('update') && $rootPage>0 && intval(t3lib_div::_GP('mode'))) {
			$content .= '<h3>'.$LANG->getLL('text.updated').'</h3>';
			$content .= '<p>'.$this->processPage($rootPage).'</p>';
			if (t3lib_extMgm::isLoaded('tt_news')) {
				if($this->triggerNewsUpdate==1) {
					$content .= '<h3>'.$LANG->getLL('text.newsupdated').'</h3>';
					$content .= '<p>'.$this->processNews($rootPage).'</p>';
				}
			}
		}
		
		return $content;
	}

	/**
	 * access is always allowed
	 *
	 * @return	boolean		Always returns true
	 */
	function access() {
		return true;
	}

	function getRecords($table, $where) {
		$out = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, $where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$out[] = $row;
		}
		return $out;
	}
	
	function makeSelect($data,$selected,$name) {
		global $LANG;
		$out = '<option value="0">'.$LANG->getLL('option.select').'</option>';
		foreach ($data as $page) {
			$out.= '<option value="'.$page['uid'].'"'.($page['uid']==$selected ? ' selected="selected"' : '').'>'.$page['title'].'</option>';
		}
		return '<select name="'.$name.'">'.$out.'</select>';
	}
	
	function processNews($rootPage,$out='',$indent='') {
		$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
		
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid,pid,title', 'tt_news', 'deleted=0 AND hidden=0');
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			// Is newsrecord in selected branch of site?
			$curRoot = $sys_page->getRootLine($row['pid']); 
			if ($curRoot['0']['uid']==$rootPage) {
				$out.=$indent.$row['title'].'<br />';
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
					'tt_news',
					'uid='.$row['uid'],
					array(
						'tx_pmkautokeywords_useautokeywords' => $this->mode
					)
				);
				// Get TSConfig for page where news record is located
				$this->conf = $this->getConfig($row['pid']);
				
				$this->typo3Lang = $this->conf['defLangCode'];
				$this->LANG = t3lib_div::makeInstance('language');
				$this->LANG->init($this->typo3Lang);
				$stopwords = $this->getStopWords($this->typo3Lang);
				
				$keywordArray = array_diff_key($this->getNewsContent($row['uid']), $stopwords);
				arsort($keywordArray, SORT_NUMERIC);
				$keywordArray = array_keys(array_slice($keywordArray, 0, $this->conf['maxKeywords'], true));
				
				// Write the autokeywords to the tt_news table
				$this->updateAutoKeywords($keywordArray, $row['uid'],'tt_news');
			}
		}
		return $out;
	}
	

	function processPage($uid,&$out='',$indent='') {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'pages',
			'uid='.$uid,
			array(
				'tx_pmkautokeywords_useautokeywords' => $this->mode
			)
		);
		// Process subpages
		$pages = $this->getRecords('pages', 'deleted=0 AND hidden=0 AND (doktype=1 OR doktype=6 OR doktype=4 OR doktype=7 OR doktype=254) AND pid='.$uid);
		$indent.='&nbsp;&nbsp;&nbsp;';
		foreach ($pages as $page) {
				$out.=($page['doktype']==1 || $page['doktype']==6) ? $indent.$page['title'].'<br />' : $indent.'<em>'.$page['title'].'</em><br />';
			
			$this->processPage($page['uid'],$out,$indent);
			
			// Check if there exists any pages_language_overlay records on this page
			if ($overlayIds = $this->getPageLanguageOverlayUids($page['uid'])) {
				foreach ($overlayIds as $overlayId) {
					$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
						'pages_language_overlay',
						'uid='.$overlayId,
						array(
							'tx_pmkautokeywords_useautokeywords' => $this->mode
						)
					);
				}
			}
		}
		if ($this->triggerUpdate && ($page['doktype']==1 || $page['doktype']==6)) {
			$this->updateKeywords($uid);
		}
		return $out;
	}
	
	function updateKeywords($pageId) {
		// Get config options.
		$this->conf = $this->getConfig($pageId);
		if ($this->conf['keywordfield']=='tx_pmkautokeywords_autokeywords' || $this->useAutokeywords($pageId,'pages')) {
			// Process standard pages record
			$this->typo3Lang = $this->conf['defLangCode'];
			$this->LANG = t3lib_div::makeInstance('language');
			$this->LANG->init($this->typo3Lang);

			$stopwords = $this->getStopWords($this->typo3Lang);
			
			$keywordArray = array_diff_key($this->getPageContent($pageId), $stopwords);
			arsort($keywordArray, SORT_NUMERIC);
			$keywordArray = array_keys(array_slice($keywordArray, 0, $this->conf['maxKeywords'], true));
			
			// Write the autokeywords to the pages table
			$this->updateAutoKeywords($keywordArray, $pageId);
		}
		// Check if there exists any pages_language_overlay records on this page
		if ($overlayIds = $this->getPageLanguageOverlayUids($pageId)) {
			foreach ($overlayIds as $overlayId) {
				if ($this->conf['keywordfield']=='tx_pmkautokeywords_autokeywords' || $this->useAutokeywords($overlayId,'pages_language_overlay')) {
					$languageUid = $this->getSysLanguageUid($overlayId);
					$this->typo3Lang = $this->getTypo3Lang($languageUid);
					$this->LANG = t3lib_div::makeInstance('language');
 					$this->LANG->init($this->typo3Lang);
					$stopwords = $this->getStopWords($this->typo3Lang);
					$keywordArray = array_diff_key($this->getPageOverlayContent($overlayId, $pageId, $languageUid), $stopwords);
					arsort($keywordArray, SORT_NUMERIC);
					$keywordArray = array_keys(array_slice($keywordArray, 0, $this->conf['maxKeywords'], true));
					// Write the autokeywords to the pages_language_overlay table
					$this->updateAutoKeywords($keywordArray, $overlayId, 'pages_language_overlay');
				}
			}
		}
	}
	
	/**
	 * Returns array of pages_language_overlay uids
	 *
	 * @param	integer		$pageId: uid of page record
	 * @return	array		array of pages_language_overlay uids linked to page record
	 */
	function getPageLanguageOverlayUids($pageId) {
		$uidArray = array();
		$where = 'pid='.$pageId.' AND deleted=0 AND hidden=0';
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'pages_language_overlay', $where);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$uidArray[] = $row['uid'];
		}
		return $uidArray;
	}
	
}

// Include extension?
	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkautokeywords/class.ext_update.php'])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkautokeywords/class.ext_update.php']);
	}
?>
