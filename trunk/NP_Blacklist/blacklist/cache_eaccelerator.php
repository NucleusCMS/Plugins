<?php

/**
* cache_eaccelerator.php ($Revision: 1.5 $)
* 
* by hsur ( http://blog.cles.jp/np_cles )
* $Id: cache_eaccelerator.php,v 1.5 2007-02-02 16:48:24 hsur Exp $
*/

function pbl_ipcache_write(){
	$key = sprintf("BL%u", ip2long(serverVar('REMOTE_ADDR')));
	if( ! rand(0,100) ) pbl_ipcache_gc();
	
	// eAccelerator Cache
	eaccelerator_lock($key);
	eaccelerator_put($key, '1', NP_BLACKLIST_CACHE_LIFE);
	eaccelerator_unlock($key);
}

function pbl_ipcache_read(){
	$key = sprintf("BL%u", ip2long(serverVar('REMOTE_ADDR')));
	// eAccelerator Cache
	if( eaccelerator_get($key) ){
		return true;	
	}
	return false;
}

function pbl_ipcache_gc(){
	$now = time();
	$lastGc = -1;
	
	// eAccelerator Cache
	$lastGc = intval(eaccelerator_get(NP_BLACKLIST_CACHE_GC_TIMESTAMP));
	if($now - $lastGc > NP_BLACKLIST_CACHE_GC_INTERVAL){
		pbl_log("GC started.");
		eaccelerator_gc();
		$lastGc = $now;
		eaccelerator_lock(NP_BLACKLIST_CACHE_GC_TIMESTAMP);
		eaccelerator_put(NP_BLACKLIST_CACHE_GC_TIMESTAMP, $lastGc, NP_BLACKLIST_CACHE_GC_TIMESTAMP_LIFE);
		eaccelerator_unlock(NP_BLACKLIST_CACHE_GC_TIMESTAMP);
	}
	
	return $lastGc;
}
