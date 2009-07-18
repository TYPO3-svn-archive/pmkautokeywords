#
# Table structure for table 'pages'
#
CREATE TABLE pages (
	tx_pmkautokeywords_autokeywords text NOT NULL,
	tx_pmkautokeywords_useautokeywords tinyint(3) DEFAULT '0' NOT NULL
);

CREATE TABLE pages_language_overlay (
	tx_pmkautokeywords_autokeywords text NOT NULL,
	tx_pmkautokeywords_useautokeywords tinyint(3) DEFAULT '0' NOT NULL
);

CREATE TABLE tt_news (
	tx_pmkautokeywords_autokeywords text NOT NULL,
	tx_pmkautokeywords_useautokeywords tinyint(3) DEFAULT '0' NOT NULL
);
