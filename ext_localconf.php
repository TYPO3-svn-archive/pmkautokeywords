<?php
if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

t3lib_extMgm::addPageTSConfig('
tx_pmkautokeywords {
	stopwords.default = about, from, that, this, what, when, where, will, with
	stopwords.dk = alle, andet, andre, begge, denne, deres, dette, eller, eneste, enhver, fire, flere, fleste, fordi, forrige, hans, hendes,  hvad, hvem, hver, hvilken, hvis, hvor, hvordan, hvorfor, hvorn�r, ikke, ingen, intet, jeres, kommer, lille, mand, mange, meget, mens, mere, nogen, noget, n�ste, n�sten, otte, over, seks, stor, store
	stopwords.de = aber, auch, bist, dadurch, daher, darum, dass, dein, deine, dessen, deshalb, dies, dieser, dieses, doch, dort, durch, eine, einem, einen, einer, eines, euer, eure, hatte, hatten, hattest, hattet, hier, hinter, ihre, jede, jedem, jeden, jeder, jedes, jener, jenes, jetzt, kann, kannst, k�nnen, k�nnt, machen, mein, meine, mu�t, musst, m�ssen, m��t, nach, nachdem, nein, nicht, oder, seid, sein, seine, sich, sind, soll, sollen, sollst, sollt, sonst, soweit, sowie, unser, unsere, unter, wann, warum, weiter, weitere, wenn, werde, werden, werdet, weshalb, wieder, wieso, wird, wirst, woher, wohin, �ber
	stopwords.fr = alors, aucuns, aussi, autre, avant, avec, avoir, cela, ceux, chaque, comme, comment, dans, dedans, dehors, depuis, deux, devrait, doit, donc, droite, d�but, elle, elles, encore, essai, fait, faites, fois, font, force, haut, hors, juste, leur, maintenant, mais, mine, moins, m�me, nomm�s, notre, nous, nouveaux, parce, parole, personnes, peut, pi�ce, plupart, pour, pourquoi, quand, quel, quelle, quelles, quels, sans, seulement, sien, sont, sous, soyez, sujet, tandis, tellement, tels, tous, tout, trop, tr�s, valeur, voie, voient, vont, votre, vous, �taient, �tat, �tions, �tre
	maxKeywords = 150
	minWordLength = 4
	pageFields = title,subtitle,description,abstract
	contentFields = header,subheader,bodytext
	newsFields = title,bodytext
	respectHiddenHeaders = 1
}
');

/**
  *  Enable hook after saving page or content element
  */

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:pmkautokeywords/class.tx_pmkautokeywords.php:&tx_pmkautokeywords';

$GLOBALS['TYPO3_CONF_VARS']['FE']['pageOverlayFields'] .= ',tx_pmkautokeywords_autokeywords,tx_pmkautokeywords_useautokeywords';
?>