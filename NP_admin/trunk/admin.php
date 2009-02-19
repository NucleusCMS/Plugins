<?php

$CONF = array();
$CONF['Self'] = 'admin.php';

$CONF['NP_admin']=array();

include('./config.php');

$p=$manager->getPlugin('NP_admin');
$p->selector();
