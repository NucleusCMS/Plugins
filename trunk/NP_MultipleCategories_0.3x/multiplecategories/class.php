<?php

/* 
  Class for Plugin Admin Area 
  Taka http://vivian.stripper.jp/ 2004-04-13

     Copied / Arranged => nucleus/libs/ADMIN.php, TEMPLATE.php
     And a few was added...
*/
	
	// class PULUG_ADMIN
	// class PLUG_TEMPLATE_MANAGER

class PLUG_ADMIN {
	
	function action($action) {
		$methodName = 'action_' . $action;
		if (method_exists($this, $methodName)) {
			call_user_func(array(&$this, $methodName));
		} else {
			$this->error(_BADACTION . " ($action)");
		}
	}

	function disallow() {
		global $HTTP_SERVER_VARS;
		
		ACTIONLOG::add(WARNING, _ACTIONLOG_DISALLOWED . $HTTP_SERVER_VARS['REQUEST_URI']);
		
		$this->error(_ERROR_DISALLOWED);
	}

	function error($msg) {
		global $oPluginAdmin;
		
		$oPluginAdmin->start();
		$dir=$oPluginAdmin->plugin->getAdminURL();
		?>
		<h2>Error!</h2>
		<?php		echo $msg;
		echo "<br />";
		echo "<a href='".$dir."index.php' onclick='history.back()'>"._BACK."</a>";
		
		$oPluginAdmin->end();
		exit;
	}

	function requestEx($name) {
		if(phpversion()<"4.1.0") {
			global $HTTP_POST_VARS, $HTTP_GET_VARS;
			$data = $HTTP_POST_VARS[$name] ? $HTTP_POST_VARS[$name] : $HTTP_GET_VARS[$name];
		} else {
			$data = $_REQUEST[$name];
		}
		if (is_array($data) && get_magic_quotes_gpc()) {
				return array_map("stripslashes",$data);
		} else {
				return get_magic_quotes_gpc() ? stripslashes($data) : $data;
		}
	}

	function help($name) {
		echo $this->helpHtml($name);
	}

	function helpHtml($name) {
		return $this->helplink($name) . '<img src="documentation/icon-help.gif" width="15" height="15" alt="'._HELP_TT.'" /></a>';
	}

	function helplink($name) {
		global $oPluginAdmin;
		
		$dir=$oPluginAdmin->plugin->getAdminURL();
		return '<a href="'.$dir.'help.html#'. $name . '" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return help(this.href);">';
	}

	function showRadioButton($name, $radio_array, $checked, $tabindex=0) {
   /*
      $name : The name of the radiobutton element.
      $radio_array :An associative array of option element data.
        [Format] $radio_array = array('radiobutton value' => 'radiobutton title')
      $checked : The value of the checked element.
   */
		foreach ($radio_array as $k => $v) {
			echo '<input type="radio" name="'.$name.'" id="'.$name.$k.'" value="'.$k.'"';
			if ($k == $checked) echo ' checked="checked"';
			echo ' tabindex="'.$tabindex.'" />';
			echo '<label for="'.$name.$k.'">'.$v.'</label>';
		}
	}

	function showBlogCheckbox($name, $checked_array=array(), $tabindex=0) {
   /*
      $name : The name of the input(checkbox) element.
           !!! Checked blogID data is returned as array. !!!
      $checked_array : Array containing blogID which should be checked beforehand.
   */

		$query = 'SELECT bnumber, bname FROM '.sql_table('blog').' ORDER BY bnumber';
		$res = sql_query($query);
		
		while ($data = mysql_fetch_assoc($res)) {
			$data['bname'] = htmlspecialchars(shorten($data['bname'],16,'..'));
			
			echo '<span style="white-space:nowrap"><input type="checkbox" name="'.$name.'[]" value="'.$data['bnumber'].'"';
			
			if(in_array($data['bnumber'],$checked_array)) {
				echo ' checked="checked"';
			}
			echo ' tabindex="'.$tabindex.'" id="blog_checkbox'.$data['bnumber'].'" />';
			echo '<label for="blog_checkbox'.$data['bnumber'].'">';
			echo $data['bnumber'].":".$data['bname'].'</label></span>';
		}
	}

	function showCategoryCheckbox($name, $blogid, $checked_array=array(), $tabindex=0) {
   /*
      $name : The name of the input(checkbox) element.
           !!! Checked catID data is returned as array. !!!
      $blogid : blogID to which a category belongs
      $checked_array : Array containing catID which should be checked beforehand.
   */

		$query = 'SELECT catid, cname FROM '.sql_table('category').'WHERE cblog='.$blogid.' ORDER BY catid';
		$res = sql_query($query);
		
		while ($data = mysql_fetch_assoc($res)) {
			$data['cname'] = htmlspecialchars(shorten($data['cname'],16,'..'));
			
			echo '<span style="white-space:nowrap"><input type="checkbox" name="'.$name.'[]" value="'.$data['catid'].'"';
			
			if(in_array($data['catid'],$checked_array)) {
				echo ' checked="checked"';
			}
			echo ' tabindex="'.$tabindex.'" id="category_checkbox'.$data['catid'].'" />';
			echo '<label for="category_checkbox'.$data['catid'].'">';
			echo $data['catid'].":".$data['cname'].'</label></span>';
		}
	}

	function showSelectMenu($name, $option_array, $selected, $tabindex=0) {
   /*
      $name : The name of the select element.
      $option_array :An associative array of option element data.
        [Format] $option_array = array('option value' => 'option title')
      $selected : The value of the selected option element.
   */

		echo '<select name="'.$name.'" tabindex="'.$tabindex.'">';
		foreach ($option_array as $k => $v) {
			echo '<option value="'.$k.'"';
			if ($k == $selected) echo ' selected="selected"';
			echo '>'.$v.'</option>';
		}
		echo '</select>';
	}

	function showRankSelectMenu($name, $selected=20, $maxvalue=50, $tabindex=0) {
		echo '<select name="'.$name.'" tabindex="'.$tabindex.'">';
		for ($i=1; $i<=$maxvalue; $i++) {
			echo '<option value="'.$i.'"';
			if ($i == $selected)  echo ' selected="selected"';
			echo '>'.$i.'</option>';
		}
		echo '</select>';
	}

	function showAllCategorySelectMenu($name, $selected = '', $tabindex = 0) {
		
		echo '<select name="',$name,'" tabindex="',$tabindex,'">';
		if (!$selected) {
			echo '<option value="" selected="selected"> --- </option>';
		}

		// 1. select blogs (we'll create optiongroups)
		$queryBlogs =  'SELECT bnumber, bname FROM '.sql_table('blog').' ORDER BY bnumber';
		$blogs = sql_query($queryBlogs);
		if (mysql_num_rows($blogs) > 1) {
			$multipleBlogs = 1;
		}

		while ($oBlog = mysql_fetch_object($blogs)) {
			if ($multipleBlogs) {
				echo '<optgroup label="',htmlspecialchars($oBlog->bname),'">';
			}
		
			// 2. for each category in that blog
			$categories = sql_query('SELECT cname, catid FROM '.sql_table('category').' WHERE cblog=' . $oBlog->bnumber . ' ORDER BY cname ASC');
			while ($oCat = mysql_fetch_object($categories)) {
				if ($oCat->catid == $selected)
					$selectText = ' selected="selected" ';
				else
					$selectText = '';
				echo '<option value="',$oCat->catid,'" ', $selectText,'>',htmlspecialchars($oCat->cname),'</option>';
			}

			if ($multipleBlogs) {
				echo '</optgroup>';
			}
		
		}
		echo '</select>';
		
	}

	function templateEditRow(&$template, $description, $name, $help = '', $tabindex = 0, $big = 0) {
   /*
      $template : An associative array of current template.
         [Format] $template = array( 'template part name' => 'template part value')
      $description : Description of current template part.
      $name : The name of the current template part.
   */
	?>
		</tr><tr>	
			<td><?php echo $description ?> <?php if ($help) $this->help($help); ?></td>
			<td><textarea name="<?php echo $name?>" tabindex="<?php echo $tabindex?>" cols="50" rows="<?php echo $big ? '10' : '5'; ?>"><?php echo  htmlspecialchars($template[$name]); ?></textarea></td>
	<?php
	}

}


class PLUG_TEMPLATE_MANAGER {
	
	function PLUG_TEMPLATE_MANAGER($table,$primarykey,$namecolumn) {
		$this->table = $table;
		$this->idkey = $primarykey;
		$this->namekey = $namecolumn;
		// !!! $this->idkey must be set as "auto_increment." !!!
	}

	function getIdFromName($name) {
		return quickQuery('SELECT '.$this->idkey.' as result FROM '.$this->table.' WHERE '.$this->namekey.'="'.addslashes($name).'"');
	}

	function getNameFromID($id) {
		return quickQuery('SELECT '.$this->namekey.' as result FROM '.$this->table.' WHERE '.$this->idkey.'='.intval($id));
	}
	
	function getDataFromID($dataname,$id) {
		return quickQuery('SELECT '.$dataname.' as result FROM '.$this->table.' WHERE '.$this->idkey.'='.intval($id));
	}
	
	function exists($name) {
		$res = sql_query('SELECT * FROM '.$this->table.' WHERE '.$this->namekey.'="'.addslashes($name).'"');
		return (mysql_num_rows($res) != 0);
	}
	
	function existsID($id) {
		$res = sql_query('select * FROM '.$this->table.' WHERE '.$this->idkey.'='.intval($id));
		return (mysql_num_rows($res) != 0);
	}
	
	function getNameList($w='') {
		$where = '';
		if ($w != '') $where = ' WHERE '.$w;
		$res = sql_query('SELECT '.$this->idkey.' as id, '.$this->namekey.' as name FROM '.$this->table. $where .' ORDER BY '.$this->namekey);
		while ($obj = mysql_fetch_object($res)) {
			$templates[intval($obj->id)] = $obj->name;
		}
		return $templates;
	}
	
	function read($name) {
		$query = 'SELECT * FROM '.$this->table.' WHERE '.$this->namekey.'="'.addslashes($name).'"';
		$res = sql_query($query);
		return mysql_fetch_assoc($res);
	}

	function createTemplate($name) {
		sql_query('INSERT INTO '.$this->table.' SET '.$this->namekey.'="'. addslashes($name) .'"');
		$newid = mysql_insert_id();
		return $newid;
	}

	function updateTemplate($id, $template) {
		$query = 'UPDATE '.$this->table.' SET ';
		foreach ($template as $k => $v) {
			$query .= $k.'="'.addslashes($v).'",';
		}
		$query = substr($query,0,-1);
		$query .= ' WHERE '.$this->idkey.'='.$id;
		sql_query($query);
	}

	function deleteTemplate($id) {
		sql_query('DELETE FROM '.$this->table.' WHERE '.$this->idkey.'=' . $id);
	}

}


?>