<?php 

define('_NPMC_DESCRIPTION',              'Multiple Categories, Sub Categories.'); 

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