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

/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   62: class tx_pmkautokeywords
 *   76:     function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$reference)
 *  172:     function getConfig($pageId)
 *  194:     function isUpdateNeeded($newFields, $cfgFields)
 *  206:     function updateAutoKeywords($keywordArray, $pageId, $table = 'pages')
 *  221:     function getStopWords($typo3Lang)
 *  241:     function getPageUid($uid, $table)
 *  256:     function useAutokeywords($uid, $table)
 *  268:     function getNewsContent($pluginId)
 *  288:     function getPageContent($pageId)
 *  331:     function getPageOverlayContent($overlayId, $pageId, $languageId)
 *  363:     function getPageLanguageOverlayUids($pageId)
 *  379:     function getSysLanguageUid($overlayId)
 *  392:     function getTypo3Lang($languageId)
 *  407:     function addWordsToArray($value, &$wordList)
 *  431:     function getFlexFormUids($pid)
 *
 * TOTAL FUNCTIONS: 15
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

	// Include TemplaVoila API if available:
	if (t3lib_extMgm::isLoaded('templavoila')) {
		require_once(t3lib_extMgm::extPath('templavoila').'class.tx_templavoila_api.php');
	}
	require_once(PATH_typo3.'sysext/lang/lang.php');

	/**
	 * Class for generating automated META keywords, based on the text of the page.
	 *
	 */
	class tx_pmkautokeywords {

		var $extKey = 'pmkautokeywords';

		/**
 * Main function. Hook from t3lib/class.t3lib_tcemain.php
 *
 * @param	string		$status: Status of the current operation, 'new' or 'update
 * @param	string		$table: The table currently processing data for
 * @param	string		$id: The record uid currently processing data for, [integer] or [string] (like 'NEW...')
 * @param	array		$fieldArray: The field array of a record
 * @param	object		$reference: reference to parent object
 * @return	void
 */
		function processDatamap_afterDatabaseOperations($status, $table, $id, &$fieldArray, &$reference) {
			if ($status == 'new') {
				$id = $reference->substNEWwithIDs[$id];
			}
			switch ($table) {
				case 'pages':
					$pageId = intval($id);
				break;
				case 'tt_news':
					$newsId = intval($id);
				case 'pages_language_overlay':
				case 'tt_content':
					$pageId = $this->getPageUid(intval($id), $table);
				break;
				default:
					return;
				break;
			}
			// Get config options.
			$this->conf = $this->getConfig($pageId);
			// Do we really need to update autokeywords?
			$updateFlag = 0;
			switch ($table) {
				case 'pages':
				case 'pages_language_overlay':
					$updateFlag += $this->isUpdateNeeded(array_keys($fieldArray), $this->conf['pageFields']);
				break;
				case 'tt_content':
					$updateFlag += $this->isUpdateNeeded(array_keys($fieldArray), $this->conf['contentFields']);
				break;
				case 'tt_news':
					$updateFlag += $this->isUpdateNeeded(array_keys($fieldArray), $this->conf['newsFields']);
				break;
			}
			if (!$updateFlag) return;

			if ($table =='tt_news') {
				if ($this->conf['keywordfield']=='tx_pmkautokeywords_autokeywords' || $this->useAutokeywords($newsId,'tt_news')) {
					// Process tt_news records
					$this->typo3Lang = $this->conf['defLangCode'];
					$this->LANG = t3lib_div::makeInstance('language');
					$this->LANG->init($this->typo3Lang);

					$stopwords = $this->getStopWords($this->typo3Lang);

					$keywordArray = array_diff_key($this->getNewsContent($newsId), $stopwords);
					arsort($keywordArray, SORT_NUMERIC);
					$keywordArray = array_keys(array_slice($keywordArray, 0, $this->conf['maxKeywords'], true));

					// Write the autokeywords to the tt_news table
					$this->updateAutoKeywords($keywordArray, $newsId,'tt_news');
				}
			}
			else {
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
		}

		/**
 * Returns config array with plugin options from Page TSConfig and Extension Config
 *
 * @param	integer		$pageId: id of pages record
 * @return	array		Array of config options.
 */
		function getConfig($pageId) {
			$PageTSconfig = t3lib_BEfunc::getPagesTSconfig($pageId);
			$conf = $PageTSconfig['tx_pmkautokeywords.'];
			$conf['pageFields'] = $conf['pageFields'] ? $conf['pageFields'] : 'title';
			$conf['contentFields'] = $conf['contentFields'] ? $conf['contentFields'] : 'bodytext';
			$conf['newsFields'] = $conf['newsFields'] ? $conf['newsFields'] : 'title,bodytext';
			$conf['minWordLength'] = max(4,intval($conf['minWordLength']));
			$conf['maxKeywords'] = min(150, max(intval($conf['maxKeywords']), 8));
			$conf['defLangCode'] = isset($conf['defLangCode']) ? $conf['defLangCode'] : 'default';
			$conf['respectHiddenHeaders'] = $conf['respectHiddenHeaders'] ? 1 : 0;
			$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
			$conf['keywordfield'] = $extConf['keywordfield'] ? $extConf['keywordfield'] : 'tx_pmkautokeywords_autokeywords';
			return $conf;
		}

		/**
 * Returns true if a field in $fieldArray matches on of the fields used for collecting words
 *
 * @param	array		$newFields: array of submitted fieldnames
 * @param	string		$cfgFields: comma seperated list of fieldsnames
 * @return	boolean
 */
		function isUpdateNeeded($newFields, $cfgFields) {
			return count(array_intersect(preg_split('/[\s,]+/i', $cfgFields), $newFields)) > 0;
		}

		/**
 * Update tx_pmkautokeywords_autokeywords field in either pages or pages_language_overlay table
 *
 * @param	array		$keywordArray: array of keywords
 * @param	integer		$pageId: id of pages or pages_language_overlay record
 * @param	string		$table: pages or pages_language_overlay
 * @return	void
 */
		function updateAutoKeywords($keywordArray, $pageId, $table = 'pages') {
			// set keywords in page
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'uid='.$pageId,
				array(
					$this->conf['keywordfield'] => implode(', ', $keywordArray)
				)
			);
		}

		/**
 * Processes stopwords list
 *
 * @param	string		$typo3Lang: TYP3 language code.
 * @return	array		array of stopwords (key=word,value=count)
 */
		function getStopWords($typo3Lang) {
			$wordList = array();
			$stopwords = '';
			if (isset($this->conf['stopwords.']['file.'][$typo3Lang])) {
				$stopwords.= preg_replace('/\r\n|\r|\n/', ',', @file_get_contents(t3lib_div::getFileAbsFileName($this->conf['stopwords.']['file.'][$typo3Lang])));
			}
			else if (isset($this->conf['stopwords.'][$typo3Lang])) {
				$stopwords.=','.$this->conf['stopwords.'][$typo3Lang];
			}
			if ($stopwords) $this->addWordsToArray($stopwords, $wordList);
			return $wordList;
		}

		/**
 * Returns pid from tt_content or pages_language_overlay record
 *
 * @param	integer		$uid: uid of tt_content or pages_language_overlay record
 * @param	string		$table: tt_content or pages_language_overlay
 * @return	integer		id of parent page
 */
		function getPageUid($uid, $table) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('pid', $table, 'uid='.$uid);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				return $row['pid'];
			}
			die;
		}

		/**
 * Returns tx_pmkautokeywords_useautokeywords from pages or pages_language_overlay record
 *
 * @param	integer		$uid: uid of pages or pages_language_overlay record
 * @param	string		$table: pages or pages_language_overlay
 * @return	boolean		tx_pmkautokeywords_useautokeywords field
 */
		function useAutokeywords($uid, $table) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tx_pmkautokeywords_useautokeywords', $table, 'uid='.$uid);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return $row['tx_pmkautokeywords_useautokeywords'];
		}

		/**
 * Processes tt_news content
 *
 * @param	integer		$pluginId: id of news record
 * @return	array		array of words (key=word,value=count)
 */
		function getNewsContent($pluginId) {
			$wordArray = array();

			// get text fields from plugin record
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($this->conf['newsFields'], 'tt_news', 'uid='.$pluginId );
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ($row) {
				foreach($row as $field => $value) {
					$this->addWordsToArray($value, $wordArray);
				}
			}
			return $wordArray;
		}

		/**
 * Processes general content
 *
 * @param	integer		$pageId: id of page
 * @return	array		array of words (key=word,value=count)
 */
		function getPageContent($pageId) {

			$wordArray = array();

			// get text fields from page record
			$fields = $this->conf['pageFields'];
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'pages', 'uid='.$pageId );
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			if ($row) {
				foreach($row as $field => $value) {
					$this->addWordsToArray($value, $wordArray);
				}
			}

			// get text fields from tt_content records
			$fields = $this->conf['contentFields'];
			if ($this->conf['respectHiddenHeaders'] && t3lib_div::inList($fields,'header')) {
				$fields .= ',header_layout';
			}
			$ffWhere = $this->getFlexFormUids($pageId);
			$ffWhere = $ffWhere ? ' AND uid'.$ffWhere :
			 '';
			$where = 'pid='.$pageId.' AND deleted=0 AND hidden=0 AND sys_language_uid=0'.$ffWhere;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'tt_content', $where);
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				foreach($row as $key => $value) {
					if ($this->conf['respectHiddenHeaders'] && (($key=='header' && $row['header_layout']==99) || $key=='header_layout')) {
						continue;
					}
					$this->addWordsToArray($value, $wordArray);
				}
			}
			return $wordArray;
		}

		/**
 * Processes language specific content
 *
 * @param	integer		$overlayId: id of pages_language_overlay record
 * @param	integer		$pageId: id of pages record
 * @param	integer		$languageId: id of sys_language_uid
 * @return	array		array of words (key=word,value=count)
 */
		function getPageOverlayContent($overlayId, $pageId, $languageId) {
			$wordArray = array();

			// get text fields from pages_language_overlay record
			$fields = $this->conf['pageFields'] ? $this->conf['pageFields'] : 'title';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'pages_language_overlay', 'uid='.$overlayId);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			foreach($row as $field => $value) {
				$this->addWordsToArray($value, $wordArray);
			}

			// get text fields from tt_content records
			$fields = $this->conf['contentFields'] ? $this->conf['contentFields'] : 'bodytext';
			$ffWhere = $this->getFlexFormUids($pageId);
			$ffWhere = $ffWhere ? ' AND t3_origuid'.$ffWhere : '';

			$where = 'pid='.$pageId.' AND deleted=0 AND hidden=0 AND sys_language_uid='.$languageId.$ffWhere;
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, 'tt_content', $where );
			while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				foreach($row as $value) {
					$this->addWordsToArray($value, $wordArray);
				}
			}
			return $wordArray;
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

		/**
 * Returns sys_language_uid from pages_language_overlay table
 *
 * @param	integer		$overlayId: uid of pages_language_overlay record
 * @return	integer		sys_language_uid from pages_language_overlay record
 */
		function getSysLanguageUid($overlayId) {
			$where = 'uid='.$overlayId.' AND deleted=0 AND hidden=0';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('sys_language_uid', 'pages_language_overlay', $where );
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			return $row['sys_language_uid'] ? $row['sys_language_uid'] : 0;
		}

		/**
 * Returns TYPO3 language abbrevation
 *
 * @param	integer		$languageId: sys_language_uid
 * @return	string		TYPO3 name of language in lowercase
 */
		function getTypo3Lang($languageId) {
			$where = 'sys_language.uid='.$languageId.' AND static_languages.uid=sys_language.static_lang_isocode AND sys_language.hidden=0';
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('LCASE(lg_iso_2) as lg_iso_2,lg_typo3', 'sys_language,static_languages', $where );
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$result = $row['lg_typo3'] ? $row['lg_typo3'] : $row['lg_iso_2'];
			return $result ? $result : 'default';
		}

		/**
 * adds all words from $value to the $wordList array
 *
 * @param	string		$value: text string to parse
 * @param	array		$wordList: array passed by reference
 * @return	void
 */
		function addWordsToArray($value, &$wordList) {
			$value = preg_replace('%([^\s])(</(td|th|li|dt|dd)>|<br[^>]*>)%i', '$1 $2', $value);	// Fix for truncated table cells and other tags
			$value = strip_tags($value);
			$value = html_entity_decode($value);
			// Pattern of chars that should act as word splitters
			// Currently the chars: .,:;?!()[{}+="'#%&\/_| plus linefeeds, linebreaks, tabs and spaces.
			$pattern = '~([^]\\\\\'.,:;?!()[{}+="#%&/_|\s]+)~si';
			if (preg_match_all($pattern, $value, $matches)) {
				foreach ($matches[1] as $word) {
					$word = $this->LANG->csConvObj->conv_case($this->LANG->charSet, $word, 'toLower');
					if ($this->LANG->csConvObj->strlen($this->LANG->charSet, $word) < $this->conf['minWordLength']) continue;
					if (isset($wordList[$word])) $wordList[$word]++;
					else $wordList[$word] = 1;
				}
			}
		}

	/**
	 * Returns comma seperated list of tt_content uids used on TemplaVoila page.
	 * this is done to exclude the "unused elements", since TV doesn't set the delete field by default.
	 *
	 * @param	integer		id of pages record
	 * @return	string		SQL ready string
	 */
		function getFlexFormUids($pid) {
			if (!t3lib_extMgm::isLoaded('templavoila')) return '';
			$templaVoilaAPI = t3lib_div::makeInstance('tx_templavoila_api');
			$subElementUids = $templaVoilaAPI->flexform_getListOfSubElementUidsRecursively('pages', $pid, $dummyArr);
			return $subElementUids ? ' IN ('.implode(',', $subElementUids).')' : '';
		}

	}

	if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkautokeywords/class.tx_pmkautokeywords.php'])	{
		include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pmkautokeywords/class.tx_pmkautokeywords.php']);
	}
?>
