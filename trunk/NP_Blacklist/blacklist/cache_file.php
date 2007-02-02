<?php

/**
* cache_file.php ($Revision: 1.2 $)
* 
* by hsur ( http://blog.cles.jp/np_cles )
* $Id: cache_file.php,v 1.2 2007-02-02 16:48:24 hsur Exp $
*/

function pbl_ipcache_write(){
	$key = sprintf("BL%u", ip2long(serverVar('REMOTE_ADDR')));
	if( ! rand(0,19) ) pbl_ipcache_gc();
	
	// FileCache
	$cacheFile = NP_BLACKLIST_CACHE_DIR.'/'.$key;
	@touch($cacheFile) && @chmod($cacheFile, 0666);
	// FileCache	
}

function pbl_ipcache_read(){
	$key = sprintf("BL%u", ip2long(serverVar('REMOTE_ADDR')));
	
	// FileCache
	$cacheFile = NP_BLACKLIST_CACHE_DIR.'/'.$key;
	if( file_exists($cacheFile) ){ 
		if( time() - filemtime($cacheFile) < NP_BLACKLIST_CACHE_LIFE ){
			return true;
		}
	}
	return false;
	// FileCache
}

function pbl_ipcache_gc(){
	$now = time();
	$lastGc = -1;
	
	// FileCache
	$gcTimestampFile = NP_BLACKLIST_CACHE_DIR.'/'.NP_BLACKLIST_CACHE_GC_TIMESTAMP;
	if(file_exists($gcTimestampFile)){
		$lastGc = filemtime($gcTimestampFile);
		if( $now - $lastGc > NP_BLACKLIST_CACHE_GC_INTERVAL ){
			$count = 0;
			pbl_log("GC started.");
			@touch($gcTimestampFile);
			foreach (glob(NP_BLACKLIST_CACHE_DIR.'/BL*', GLOB_NOSORT) as $filename) {	
				if($now - filemtime($filename) > NP_BLACKLIST_CACHE_LIFE){
					@unlink($filename) && $count += 1;
				}
			}
			pbl_log("GC finished. ($count files deleted.)");
		}
	} else {
		@touch($gcTimestampFile);
	}
	// FileCache
	return $lastGc;
}
