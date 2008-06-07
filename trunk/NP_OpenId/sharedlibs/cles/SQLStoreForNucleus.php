<?php

/**
 * MySQLStoreForNucleus.php ($Revision: 1.2 $)
 * based on Auth_OpenID_SQLStore
 * by hsur ( http://blog.cles.jp/np_cles )
 * $Id: SQLStoreForNucleus.php,v 1.2 2008-06-07 19:33:43 hsur Exp $
 */

/*
 * Copyright (C) 2008 CLES. All rights reserved.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301 USA
 *
 * In addition, as a special exception, cles( http://blog.cles.jp/np_cles ) gives
 * permission to link the code of this program with those files in the PEAR
 * library that are licensed under the PHP License (or with modified versions
 * of those files that use the same license as those files), and distribute
 * linked combinations including the two. You must obey the GNU General Public
 * License in all respects for all of the code used other than those files in
 * the PEAR library that are licensed under the PHP License. If you modify
 * this file, you may extend this exception to your version of the file,
 * but you are not obligated to do so. If you do not wish to do so, delete
 * this exception statement from your version.
 */

/**
 * @access private
 */
require_once 'Auth/OpenID/Interface.php';
require_once 'Auth/OpenID/Nonce.php';

/**
 * @access private
 */
require_once 'Auth/OpenID.php';

/**
 * @access private
 */
require_once 'Auth/OpenID/Nonce.php';

class cles_SQLStoreForNucleus extends Auth_OpenID_OpenIDStore {
    function cles_SQLStoreForNucleus()
    {
		$this->associations_table_name = sql_table('plugin_openid_assc');
		$this->nonces_table_name = sql_table('plugin_openid_nonce');
		$this->max_nonce_age = 6 * 60 * 60;
    	
        $this->sql = array();
        $this->setSQL();
    }

    function tableExists($table_name)
    {
        return false !== sql_query( sprintf("SELECT * FROM %s LIMIT 0", mysql_real_escape_string($table_name)));
    }

    /**
     * Returns true if $value constitutes a database error; returns
     * false otherwise.
     */
    function isError($value)
    {
        return false !== $value;
    }

    /**
     * Converts a query result to a boolean.  If the result is a
     * database error according to $this->isError(), this returns
     * false; otherwise, this returns true.
     */
    function resultToBool($obj)
    {
        if ($obj === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * This method should be overridden by subclasses.  This method is
     * called by the constructor to set values in $this->sql, which is
     * an array keyed on sql name.
     */
    function setSQL()
    {
        $this->sql['nonce_table'] =
            "CREATE TABLE ".$this->nonces_table_name." (\n".
            "  server_url VARBINARY(255),\n".
            "  timestamp INTEGER,\n".
            "  salt CHAR(40),\n".
            "  UNIQUE (server_url(150), timestamp, salt)\n".
            ")";

        $this->sql['assoc_table'] =
            "CREATE TABLE ".$this->associations_table_name." (\n".
            "  server_url VARBINARY(255),\n".
            "  handle VARCHAR(255),\n".
            "  secret BLOB,\n".
            "  issued INTEGER,\n".
            "  lifetime INTEGER,\n".
            "  assoc_type VARCHAR(64),\n".
            "  PRIMARY KEY (server_url(150), handle)\n".
            ")";

        $this->sql['set_assoc'] =
            "REPLACE INTO ".$this->associations_table_name." VALUES ('%s', '%s', '%s', '%s', '%s', '%s')";

        $this->sql['get_assocs'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM ".$this->associations_table_name." ".
            "WHERE server_url = '%s'";

        $this->sql['get_assoc'] =
            "SELECT handle, secret, issued, lifetime, assoc_type FROM ".$this->associations_table_name." ".
            "WHERE server_url = '%s' AND handle = '%s'";

        $this->sql['remove_assoc'] =
            "DELETE FROM ".$this->associations_table_name." WHERE server_url = '%s' AND handle = '%s'";

        $this->sql['add_nonce'] =
            "INSERT INTO ".$this->nonces_table_name." (server_url, timestamp, salt) VALUES ('%s', '%s', '%s')";

        $this->sql['clean_nonce'] =
            "DELETE FROM ".$this->nonces_table_name." WHERE timestamp < '%s'";

        $this->sql['clean_assoc'] =
            "DELETE FROM ".$this->associations_table_name." WHERE issued + lifetime < '%s'";
    }
    
    /**
     * Resets the store by removing all records from the store's
     * tables.
     */
    function reset()
    {
        sql_query(sprintf("DELETE FROM %s", mysql_real_escape_string($this->associations_table_name)));
        sql_query(sprintf("DELETE FROM %s", mysql_real_escape_string($this->nonces_table_name)));
    }

    function blobDecode($blob)
    {
        return pack("H*", $blob);
    }

    function blobEncode($blob)
    {
        return bin2hex($blob);
    }
    
    function createTables()
    {
        $n = $this->create_nonce_table();
        $a = $this->create_assoc_table();

        if ($n && $a) {
            return true;
        } else {
            return false;
        }
    }

    function create_nonce_table()
    {
        if (!$this->tableExists($this->nonces_table_name)) {
            $r = sql_query($this->sql['nonce_table']);
            return $this->resultToBool($r);
        }
        return true;
    }

    function create_assoc_table()
    {
        if (!$this->tableExists($this->associations_table_name)) {
            $r = sql_query($this->sql['assoc_table']);
            return $this->resultToBool($r);
        }
        return true;
    }

    /**
     * @access private
     */
    function _set_assoc($server_url, $handle, $secret, $issued,
                        $lifetime, $assoc_type)
    {
        return sql_query(
        	sprintf($this->sql['set_assoc'],
              mysql_real_escape_string($this->blobEncode($server_url)),
              mysql_real_escape_string($handle),
              mysql_real_escape_string($this->blobEncode($secret)),
              mysql_real_escape_string($issued),
              mysql_real_escape_string($lifetime),
              mysql_real_escape_string($assoc_type)
            )
        );
    }

    function storeAssociation($server_url, $association)
    {
        $this->resultToBool(
	       	$this->_set_assoc(
	             $server_url,
	             $association->handle,
	             $association->secret,
	             $association->issued,
	             $association->lifetime,
	             $association->assoc_type
            )
        );
    }

    /**
     * @access private
     */
    function _get_assoc($server_url, $handle)
    {
    	$result = sql_query(
    				sprintf($this->sql['get_assoc'],
    					$this->blobEncode($server_url),
    					mysql_real_escape_string($handle)
    				)
    	);
        if ($result) {
        	$ret = mysql_fetch_assoc($result);
        	$ret['server_url'] = $this->blobDecode($ret['server_url']);
        	$ret['secret'] = $this->blobDecode($ret['secret']);
        	return $ret;
        } else {
            return null;
      	}
    }

    /**
     * @access private
     */
    function _get_assocs($server_url)
    {
    	$assocs = array();
        $result = sql_query(sprintf($this->sql['get_assocs'], $this->blobEncode($server_url)));
        if ($result) {
        	while( $assoc = mysql_fetch_assoc($result) ){
	        	$assoc['server_url'] = $this->blobDecode($assoc['server_url']);
	        	$assoc['secret'] = $this->blobDecode($assoc['secret']);
        		$assocs[] = $assoc;
        	}
        }
        return $assocs; 
    }

    function removeAssociation($server_url, $handle)
    {
        if ($this->_get_assoc($server_url, $handle) == null) {
            return false;
        }

        $this->resultToBool(
			sql_query(
				sprintf(
                    $this->sql['remove_assoc'],
                    mysql_real_escape_string($server_url),
                    mysql_real_escape_string($handle)
	        	)
	        )
	    );
        return true;
    }

    function getAssociation($server_url, $handle = null)
    {
        if ($handle !== null) {
            $assoc = $this->_get_assoc($server_url, $handle);

            $assocs = array();
            if ($assoc) {
                $assocs[] = $assoc;
            }
        } else {
            $assocs = $this->_get_assocs($server_url);
        }

        if (!$assocs || (count($assocs) == 0)) {
            return null;
        } else {
            $associations = array();

            foreach ($assocs as $assoc_row) {
                $assoc = new Auth_OpenID_Association($assoc_row['handle'],
                                                     $assoc_row['secret'],
                                                     $assoc_row['issued'],
                                                     $assoc_row['lifetime'],
                                                     $assoc_row['assoc_type']);

                if ($assoc->getExpiresIn() == 0) {
                    $this->removeAssociation($server_url, $assoc->handle);
                } else {
                    $associations[] = array($assoc->issued, $assoc);
                }
            }

            if ($associations) {
                $issued = array();
                $assocs = array();
                foreach ($associations as $key => $assoc) {
                    $issued[$key] = $assoc[0];
                    $assocs[$key] = $assoc[1];
                }

                array_multisort($issued, SORT_DESC, $assocs, SORT_DESC,
                                $associations);

                // return the most recently issued one.
                list($issued, $assoc) = $associations[0];
                return $assoc;
            } else {
                return null;
            }
        }
    }

    /**
     * @access private
     */
    function _add_nonce($server_url, $timestamp, $salt)
    {
        $sql = $this->sql['add_nonce'];
        $result = sql_query(
        				sprintf(
	        				$sql,
	        				mysql_real_escape_string($server_url),
	                        mysql_real_escape_string($timestamp),
	                        mysql_real_escape_string($salt)
                     )
        );
        return $this->resultToBool($result);
    }

    function useNonce($server_url, $timestamp, $salt)
    {
        global $Auth_OpenID_SKEW;

        if ( abs($timestamp - mktime()) > $Auth_OpenID_SKEW ) {
            return False;
        }

        return $this->_add_nonce($server_url, $timestamp, $salt);
    }

    /**
     * "Octifies" a binary string by returning a string with escaped
     * octal bytes.  This is used for preparing binary data for
     * PostgreSQL BYTEA fields.
     *
     * @access private
     */
    function _octify($str)
    {
        $result = "";
        for ($i = 0; $i < Auth_OpenID::bytes($str); $i++) {
            $ch = substr($str, $i, 1);
            if ($ch == "\\") {
                $result .= "\\\\\\\\";
            } else if (ord($ch) == 0) {
                $result .= "\\\\000";
            } else {
                $result .= "\\" . strval(decoct(ord($ch)));
            }
        }
        return $result;
    }

    /**
     * "Unoctifies" octal-escaped data from PostgreSQL and returns the
     * resulting ASCII (possibly binary) string.
     *
     * @access private
     */
    function _unoctify($str)
    {
        $result = "";
        $i = 0;
        while ($i < strlen($str)) {
            $char = $str[$i];
            if ($char == "\\") {
                // Look to see if the next char is a backslash and
                // append it.
                if ($str[$i + 1] != "\\") {
                    $octal_digits = substr($str, $i + 1, 3);
                    $dec = octdec($octal_digits);
                    $char = chr($dec);
                    $i += 4;
                } else {
                    $char = "\\";
                    $i += 2;
                }
            } else {
                $i += 1;
            }

            $result .= $char;
        }

        return $result;
    }

    function cleanupNonces()
    {
        global $Auth_OpenID_SKEW;
        $v = time() - $Auth_OpenID_SKEW;

        sql_query(sprintf($this->sql['clean_nonce'], mysql_real_escape_string($v)));
        $num = mysql_affected_rows();
        return $num;
    }

    function cleanupAssociations()
    {
        sql_query($this->sql['clean_assoc'], mysql_real_escape_string(time()));
        $num = mysql_affected_rows();
        return $num;
    }
}
