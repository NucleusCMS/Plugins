<?php

define(_RELATED_MESSAGE_DESC,		'Allows to make a list of related articles/sites.
	List can be shown with &lt;%Related(local,10)%&gt; for a list of local items which are related,
	with &lt;%Related(google,10)%&gt; for a list of related sites found on Google. 
	Skinvar supported. Also available in which is not "item" skintype, with keyword --- &lt;%Related(local,10,,keyword)%&gt;.
	When you use &lt;%Related%&gt; a list of 5 local items will be shown.
	If you use google mode, you have to get AJAX Search API key.
');

define(_RELATED_OPTION_GOOGLEKEY,		'Google AJAX Search API key');
define(_RELATED_OPTION_AMAZONTOKEN,		'Amazon API key');
define(_RELATED_OPTION_ASO_ID,			'Amazon Asociate ID');
define(_RELATED_OPTION_HEADER_LC,		'Heading start (local search)');
define(_RELATED_OPTION_HEADER_GO,		'Heading start (Google search)');
define(_RELATED_OPTION_HEADER_AM,		'Heading start (Amazon search)');
define(_RELATED_OPTION_HEADER_END,		'Heading end');
define(_RELATED_OPTION_LISTHEADING,		'Related list heading');
define(_RELATED_OPTION_LISTFOOTER,		'Related list footer');
define(_RELATED_OPTION_ITEMHEADING,		'Related item heading');
define(_RELATED_OPTION_ITEMFOOTER,		'Related item footer');
define(_RELATED_OPTION_NOTITLE,			'String to display when there is no title present');
define(_RELATED_OPTION_NORESULTS,		'String to display when there are no results (can be blank)');
define(_RELATED_OPTION_FLG_NOHEADER,	'No header if no results are found');
define(_RELATED_OPTION_MORELINK,		'String to display "more" search link');
define(_RELATED_OPTION_MAXLENGTH,		'Max length of an item title');
define(_RELATED_OPTION_MAXLENGTH2,		'Max length of a snippet');
define(_RELATED_OPTION_FLG_SNIPPET,		'Show snippet');
define(_RELATED_OPTION_FLG_TIMELOCAL,	'Show timestamp (local search)');
define(_RELATED_OPTION_FLG_SRCHCOND_AND,	'"AND" condition (local search)');
define(_RELATED_OPTION_CURRENTBLOG,		'Show only items related to same weblog (local search)');
define(_RELATED_OPTION_SEARCHRANGE,		'Search range (local search)');
define(_RELATED_OPTION_INTERVAL,		'The time between two external API calls (in hours)');
define(_RELATED_OPTION_LANGUAGE,		'Language for Google API');
define(_RELATED_OPTION_TOEXCLUDE,		'Domain name to exclude from Google search');
define(_RELATED_OPTION_FLG_CACHE_ERASE,	'Erase cache data now');
define(_RELATED_OPTION_FLG_ERASE,		'Erase data on uninstall');

define(_RELATED_REGEXP_QUOTESTYLE,		"/\"(.+)\"|\'(.+)\'|\((.+)\)|\[(.+)\]/");
define(_RELATED_REGEXP_DELIMITER,		"/,|:/");

define(_RELATED_MSG_JUMP_LC,		"Jump to local search");
define(_RELATED_MSG_JUMP_GO,		"Jump to Google search");

?>