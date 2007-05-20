<?php 

define('_NPMC_DESCRIPTION',              'Multiple Categories, Sub Categories.'); 

define('_MC_EDIT_PLUGIN_OPTIONS',        'Edit Plugin Options'); 
define('_MC_SUB_CATEGORIES',             'Sub Categories'); 
define('_MC_EDIT_SUB_CATEGORIES',        'Edit sub categories'); 
define('_MC_EDIT_SUB_CATEGORIES_OF',     'Edit sub categories of'); 
define('_MC_CREATE_NEW_SUB_CATEGORY',    'Create New Sub Category'); 
define('_MC_SCAT_NAME',                  'Name');
define('_MC_SCAT_DESC',                  'Description');
define('_MC_SCAT_CREATE',                'Create Sub Category');
define('_MC_SCAT_UPDATE',                'UPDATE Sub Category');
define('_MC_SCAT_EDIT',                  'Edit');
define('_MC_SCAT_DATA_UPDATE',           'Sub category data has been saved.');
define('_MC_SCAT_MISSING',               'Sub category is missing...');
define('_MC_SCAT_ERROR_NAME',            'Error! Input a name.');
define('_MC_CONFIRMTXT_SCAT',            'The sub category you are going to delete is: ');

define('_MC_SCAT_PARENT_NAME',           'Parent');
define('_MC_SCAT_ORDER_UPDATE',          'Sub category order has been updated.');
define('_MC_MODIFY_CHILDREN_ORDER',      'Modify Children Order');
define('_MC_SUBMIT_CHILDREN_ORDER',      'Submit Children Order');
define('_MC_NO_CHILDREN_ORDER',          '( No child categories are here )');
define('_MC_SCAT_DELETE_NOTE_LIST',      'This subcategory you\'re going to delete has child(ren) listed below.');
define('_MC_SCAT_DELETE_NOTE_PARENT',    'If delete, these subcategoris will be automatically belong to');
define('_MC_SCAT_TABLE_UPDATE_INFO',     'If you want to use UNLIMITED DEPTH SUBCATEGORY, table upgrade is required.');
define('_MC_SCAT_TABLE_UPDATE',          'upgrade successed.');

define('_MC_SCAT_PARENT_NAME_DESC',      "iWhen oneself is specified, it doesn't change.j");
define('_MC_SHOW_ORDER_MENU_KEY',        'sort key');
define('_MC_SHOW_ORDER_MENU_SNAME',      'sname');
define('_MC_SHOW_ORDER_MENU_SDESC',      'sdesc');
define('_MC_SHOW_ORDER_MENU_INDIVIDUAL', 'individual');

define('_NP_MCOP_ADDINDEX',              '[When URL-Mode is normal] If a blog URL ends with "/", add "index.php" before query strings.');
define('_NP_MCOP_ADBIDDEF',              'Add blogid to default blog\'s category URLs.');
define('_NP_MCOP_ADBLOGID',              'When a blog URL is different from default blog URL, add blogid to its category URLs.');
define('_NP_MCOP_MAINSEP',               "Separate character between a category and additional categories");
define('_NP_MCOP_ADDSEP',                "Separate character between additional categories");
define('_NP_MCOP_SUBFOMT',               "Display form of a category name when the item belongs to one or more sub categories.");
define('_NP_MCOP_CATHEADR',              "[Category list] Header Template. You can use <%blogid%>, <%blogurl%>, <%self%>");
define('_NP_MCOP_CATLIST',               "[Category list] List item Template. You can use <%catname%>, <%catdesc%>, <%catid%>, <%catlink%>, <%catflag%>, <%catamount%>, <%subcategorylist%>");
define('_NP_MCOP_CATFOOTR',              "[Category list] Footer Template. You can use <%blogid%>, <%blogurl%>, <%self%>");
define('_NP_MCOP_CATFLAG',               "[Category list] Flag Template");
define('_NP_MCOP_SUBHEADR',              "[Category list] Sub-Category Header Template");
define('_NP_MCOP_SUBLIST',               "[Category list] Sub-Category List item Template. You can use <%subname%>, <%subdesc%>, <%subcatid%>, <%sublink%>, <%subflag%>, <%subamount%>");
define('_NP_MCOP_SUBFOOTR',              "[Category list] Sub-Category Footer Template");
define('_NP_MCOP_SUBFLAG',               "[Category list] Sub-Category Flag Template");
define('_NP_MCOP_REPLACE',               '[Category list] a-1: When a category has sub categories, replace "<%amount%>" of category list template to another character.');
define('_NP_MCOP_REPRCHAR',              '[Category list] a-2: The character to replace.');
define('_NP_MCOP_ARCHEADR',              "[Archive list] Header Template. You can use <%blogid%>");
define('_NP_MCOP_ARCLIST',               "[Archive list] List item Template. You can use <%archivelink%>,<%blogid%>, month/year/day like \"%B, %Y\"");
define('_NP_MCOP_ARCFOOTR',              "[Archive list] Footer Template. You can use <%blogid%>");
define('_NP_MCOP_LOCALE',                "[Archive list] Locale");
define('_NP_MCOP_QICKMENU',              "Show in quick menu");
define('_NP_MCOP_DELTABLE',              "Delete tables on uninstall?");

?>