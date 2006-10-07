<?php

//PLUGIN MESSAGES
	define('_DESCRIPTION',			'This plugin generates the static link from website URL created by NucleusCMS.');
	define('_OP_TABLE_DELETE',		'Drop tables on uninstall ?');
	define('_OP_QUICK_LINK',		'Show in Quick Menu ?');
	define('_OP_ITEM_PATH',			'Plugin request URI');
	define('_OP_BLOG_PATH',			'Weblog request URI');
	define('_OP_DEF_ITEM_KEY',		'Default prefix of Item request URI');
	define('_OP_DEF_CAT_KEY',		'Default prefix of Categories request URI');
	define('_OP_DEF_SCAT_KEY',		'Default prefix of Subcategories request URI');
	define('_OP_USE_CURL',			'Use URI alias in this weblog');
	define('_OP_CATEGORY_PATH',		'Category request URI');
	define('_OP_MEMBER_PATH',		'Member request URI');
	define('_OP_ARCHIVE_DIR_NAME',	'Archive directory request URI');
	define('_OP_ARCHIVES_DIR_NAME',	'Archives directory request URI');
	define('_OP_MEMBER_DIR_NAME',	'Member directory request URI');
	define('_INVALID_ERROR',		'<h2>Invalid path</h2>');
	define('_INVALID_MSG',			'Invalid caracter is included.<br /> Available characters are only  [A-Za-z0-9/-(hyphen)/_(underscore)]<br />And also extention is not allowed like [.html]');
	define('_CONFLICT_ERROR',		'<h2>Specified URI already exists.</h2>');
	define('_CONFLICT_MSG',			'Special id is automatically added at the end of URI because specified URI already exists in this weblog.<br /> Please edit again if you want to chage URI.');
	define('_DELETE_PATH',			'<h2>Alias was successfully deleted.</h2>');
	define('_DELETE_MSG',			'Drop registered URI because of empty setting.<br /> Nomal FancyURL is applied for the link URI.');
	define('_NO_SUCH_URI',			'Unable to connect requested URI.<br /> Please check URI and try again.<br />
Requested URI may exist in the other weblogs.<br />
In case「/category_12/item_123.html」 url type does not work, try 「/category/12/item/123」. The change may load the contents.');
	define('_NOT_VALID_BLOG',		'Specified weblog does not exist.');
	define('_NOT_VALID_ITEM',		'Specified item does not exist.');
	define('_NOT_VALID_CAT',		'Specified category does not exist.');
	define('_NOT_VALID_SUBCAT',		'Specified subcategory does not exist.');
	define('_NOT_VALID_MEMBER',		'Specified member does not exist.');
	define('_ADMIN_TITLE',			'URL CUSTOMIZE');
	define('_QUICK_TIPS',			'Manage link URI');
	define('_ERROR_DISALLOWED',		'Access denied.');
	define('_DISALLOWED_MSG',		'You do not log in or have the permission.');
	define('_ADMIN_AREA_TITLE',		'Manage link URI');
	define('_OPTION_SETTING',		'Back to option setting');
	define('_FOR_ITEMS_SETTING',	'Manage item URI');
	define('_FOR_MEMBER_SETTING',	'Manage member URI');
	define('_FOR_CATEGORY_SETTING',	'Manage category URI');
	define('_FOR_BLOG_SETTING',		'Manage weblog URI');
	define('_EDIT',					'Edit');
	define('_BLOG_LIST_TITLE',		'Weblog name');
	define('_BLOG_URI_SETTING',		'Manage weblog access path');
	define('_BLOG_URI_NAME',		'Weblog access path');
	define('_BLOG_SETTING',			'Edit weblogs');
	define('_ITEM_SETTING',			'Edit items');
	define('_CATEGORY_SETTING',		'Edit categories');
	define('_SUBCATEGORY_SETTING',	'Edit subcategories');
	define('_MEMBER_SETTING',		'Edit members');
	define('_LISTS_CAT_NAME',		'Category name/Subcategory name');
	define('_LISTS_ITEM_DESC',		'Description');
	define('_LISTS_PATH',			'Access path');
	define('_UPDATE_SUCCESS',		'Alias was successfully updated.');

?>