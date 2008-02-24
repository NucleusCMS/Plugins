<html><body><?php
/*
 * NP_MitasNom
 * Written by Katsumi
 * This library is GPL
 */
$strRel = '../../../../'; 
include($strRel . 'config.php');

if (!($member->isLoggedIn() && $member->isAdmin())) exit('Not logged in');

echo '<a href="'.$manager->addTicketToUrl('expand.php').'">Expand</a><p/>';

if (!file_exists('compress.lst')) echo '<a href="'.$manager->addTicketToUrl('compress.php').'">Compress</a><p/>';

?></body></html>