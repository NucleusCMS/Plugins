<?php
define('_NP_EZCOMMENT2_DESC', 'Insert a comment form and a list of comments.'
						    . 'Usage : &lt;%EzComment2(mode, amount/list order/show order, destinationurl, Form Template, ListTemplate)%&gt;'
							. ' on your ITEM skin or template.'
							. 'When "list order" is made more than 1, a comment equals descending order in the date and time.'
							. 'When "show order" is made more than 1, a comment is indicated first. When it\'s made 0, the form is indicated first.');

define('_NP_EZCOMMENT2_FORM_TEMPLATES',       'NP_EzComment2 CommentForm Template');
define('_NP_EZCOMMENT2_FORM_LOGGEDIN_IDX',    'Comment form for loggedin(for idex page)');
define('_NP_EZCOMMENT2_FORM_NOTLOGGEDIN_IDX', 'Comment form for not loggedin(for idex page)');
define('_NP_EZCOMMENT2_FORM_LOGGEDIN_ITM',    'Comment form for loggedin(for item page)');
define('_NP_EZCOMMENT2_FORM_NOTLOGGEDIN_ITM', 'Comment form for not loggedin(for item page)');
define('_NP_EZCOMMENT2_COMMENTS_BODY_IDX',    'Comments body(for idex page)');
define('_NP_EZCOMMENT2_COMMENTS_HEADER_IDX',  'Comments header(for idex page)');
define('_NP_EZCOMMENT2_COMMENTS_FOOTER_IDX',  'Comments footer(for idex page)');

define('_NP_EZCOMMENT2_OP_SECRETMODE',      'Is the secret mode made effective ?');
define('_NP_EZCOMMENT2_OP_SUBSTIUTION',     'Substitution seacret comment.');
define('_NP_EZCOMMENT2_OP_CHECKLABEL',      'Label in a check box.');
define('_NP_EZCOMMENT2_OP_DROPTABLE',       'When uninstalling, is a table eliminated ?');
define('_NP_EZCOMMENT2_OP_SUBSTIUTION_VAL', 'Only an administrator can read this comment.');
define('_NP_EZCOMMENT2_OP_CHECKLABEL_VAL',  'Indication is permitted only an administrator.');
