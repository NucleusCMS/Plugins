<?php

class ALBUM {
	var $id;
	var $setid;
	var $title;
	var $description;
	var $ownerid;
	var $modified;
	var $noi;
	var $ownername;
	var $thumbnail;
	var $options;
	
	var $totalpictures;
	var $displayoffset;
	var $pageamount;
	
	var $template;
	var $query;
	
	
	function ALBUM($id = 0){
		//check if exists, populate variables, etc.
		if($id) {
			$data = $this->get_data($id);
			$this->id = $data->albumid;
			$this->title = $data->title;
			$this->description = $data->description;
			$this->ownerid = $data->ownerid;
			$this->modified = $data->modified;
			$this->noi = $data->numberofimages;
			$this->ownername = $data->name;
			$this->thumbnail = $data->thumbnail;
			$this->options['commentsallowed'] = $data->commentsallowed;
			$this->options['publicalbum'] = $data->publicalbum;
		}
		
	}
	
	function getIDfromPictureID($pictureid) {

	}
	
	function commentsallowed($pictureid) {
		$query = 'select a.commentsallowed from '.sql_table('plug_gallery_album').' as a, '.sql_table('plug_gallery_picture').' as b where a.albumid=b.albumid and pictureid='.intval($pictureid);
		$res = sql_query($query);
		$row = mysql_fetch_object($res);
		return $row->commentsallowed;
		
	}
	
	function settemplate($template) {
		$this->template = & $template;
	}
	
	function setquery($query) {
		$this->query = & $query;
	}
	
	function add_new($data) {
		$atitle = addslashes($data['title']);
		$adescription = addslashes($data['description']);
		$aowner = intval($data['ownerid']);
		$apublicalbum = addslashes($data['publicalbum']);
		if(!$aowner) $aowner = 0; //make the owner guest
		$query = "insert into ".sql_table('plug_gallery_album')." (albumid, title, description, ownerid, modified, numberofimages, commentsallowed, publicalbum) values ".
					"(NULL, '$atitle','$adescription',$aowner,NULL,0,1,'$apublicalbum')";
		sql_query($query);
		return mysql_insert_id();
	}
	
	function get_data($id) {
		$result = sql_query("select a.*,b.mname as name from ".sql_table('plug_gallery_album').' as a left join '.sql_table('member')." as b on a.ownerid=b.mnumber where a.albumid=".intval($id) );
		if(mysql_num_rows($result)) $data = mysql_fetch_object($result); 
		else {
			$data->albumid = 0;
			return $data;
		}
		
		if(!$data->name) $data->name='guest';
		
		//default album thumbnail if thumbnail is blank
		if(!$data->thumbnail) {
			$query = 'select thumb_filename from '.sql_table('plug_gallery_picture').' where albumid='.intval($data->albumid).' LIMIT 1';
			$result = sql_query($query);
			if(mysql_num_rows($result) ){
				$row = mysql_fetch_object($result);
				$data->thumbnail = $row->thumb_filename;
				sql_query('update '.sql_table('plug_gallery_album').' set thumbnail=\''.addslashes($row->thumb_filename).'\' where albumid='.intval($data->albumid));
			}
		}
		return $data;
	}
	
	function get_team($id) {
		$result = sql_query("select a.*, b.mname from ".sql_table('member').' as b, '.sql_table('plug_gallery_album_team')." as a where a.talbumid=".intval($id)." and a.tmemberid=b.mnumber");
		if(!mysql_num_rows($result)) return false;
		$j=0;
		while ($team[$j] = mysql_fetch_object($result)) {
			$j++;
		}
		return $team;
	}
	
	function get_pictures($id = 0,$so) {
		if($this->query == '' && $id == 0) return null;
		if($this->query == '') $this->query = "select * from ".sql_table('plug_gallery_picture')." where albumid=".intval($id)." $so";
		$result = sql_query($this->query);
		$i=0;
		while ($row = mysql_fetch_object($result)) {
			$data[$i] = $row;
			$res = sql_query('select views from '.sql_table('plug_gallery_views').' where vpictureid = '.intval($row->pictureid));
			if(mysql_num_rows($res)) {
				$row2 = mysql_fetch_object($res);
				$data[$i]->views = $row2->views;
			}
			else $data[$i]->views = 0;
			mysql_free_result($res);
			$i++;
		}
		$this->totalpictures = $i;
		
		return $data;
	}
	
	function get_set_pictures($splitdata,$so) {
		if($splitdata == '') return null;
		$j=0;
		$i=0;
		$limit = sizeof($splitdata);
		//echo $limit;
		//print_r($splitdata);
		while ($j<$limit){
			$keyword = $splitdata[$j];
			//echo $keyword;
			$this->query = "select * from ".sql_table('plug_gallery_picture')." WHERE keywords like '%".addslashes($keyword)."%' ";
			$result = sql_query($this->query);
			while ($row = @mysql_fetch_object($result)) {
				$data[$i] = $row;
				$res = sql_query('select views from '.sql_table('plug_gallery_views').' where vpictureid = '.intval($row->pictureid));
				if(mysql_num_rows($res)) {
					$row2 = mysql_fetch_object($res);
					$data[$i]->views = $row2->views;
				}
				else $data[$i]->views = 0;
				mysql_free_result($res);
			$i++;
			}
			$j++;
		}
		$this->totalpictures = $i;
		
		return $data;
	}
	
	function increaseNumberByOne($id) {
		if(!$id) $id = $this->id;
		$result = sql_query("update ".sql_table('plug_gallery_album')." set numberofimages = numberofimages + 1 where albumid =".intval($id));
	}
	
	function decreaseNumberByOne($id) {
		if(!$id) $id = $this->id;
		$result = sql_query("update ".sql_table('plug_gallery_album')." set numberofimages = numberofimages - 1 where albumid =".intval($id));
	}
	
	function fixnumberofimages($id) {
		if(!$id) {
			$id = $this->id;
			$numberofimages = $this->numberofimages;
		}
		else {
			$result = sql_query('select numberofimages from '.sql_table('plug_gallery_album'). " where albumid=".intval($id));
			$row = mysql_fetch_object($result);
			$numberofimages = $row->numberofimages;
		}
		$result = sql_query('select count(*) as noi from '.sql_table('plug_gallery_picture')." where albumid=".intval($id));
		$row = mysql_fetch_object($result);
		$noi = $row->noi;
		if($noi <> $numberofimages) {
			sql_query("update ".sql_table('plug_gallery_album')." set numberofimages=$noi where albumid=".intval($id));
		}
	}
	function write() {
		$query = "update ".sql_table('plug_gallery_album')
			." set title='".addslashes($this->title)."', "
			." commentsallowed= ".intval($this->option['commentsallowed']).", "
			." thumbnail='".addslashes($this->thumbnail)."', "
			." description='".addslashes($this->description)."', "
			." publicalbum= ".intval($this->option['publicalbum']).""
			." where albumid=".intval($this->id)."";
		sql_query($query);
	}
	
	function getId() { return $this->id; }
	function getName() {return $this->name;}
	function getDescription() {return $this->description;}
	function getNoi() {return $this->noi;}
	function getOwnerName() {}
	function getOwnerid() {return $this->ownerid;}
	function getLastModified() {return $this->modified;}
	function getOptions() {return $this->options; }
	function getTitle() {return $this->title;}
	
	function set_title($title) { $this->title = $title;}
	function set_description($description) { $this->description = $description; }
	function set_thumbnail($thumbnail) { $this->thumbnail = $thumbnail; }
	function set_commentsallowed($value) {$this->option['commentsallowed'] = intval($value);}
	function set_publicalbum($value) {$this->option['publicalbum'] = intval($value);}
	
	function display($sort) {
		global $NPG_CONF,$manager;
		$defaultorder = $NPG_CONF['defaultorder'];
		$sorting = array('title'=>'title ASC',
						'desc'=>'description ASC',
						'owner'=>'ownername ASC',
						'date'=>'modified DESC',
						'titlea'=>'title DESC',
						'desca'=>'description DESC',
						'ownera'=>'ownername DESC',
						'datea'=>'modified ASC',
						'filenamea'=>'filename ASC',
						'filename'=>'filename DESC');
		
		
		if(array_key_exists($sort,$sorting)){
			$so = 'order by '.$sorting[$sort].', pictureid DESC';
		}
		else {
			$so = 'order by '.$sorting[$defaultorder].', pictureid DESC';
		}
		
		$page = intval(requestvar('page'));
		if(!$page) $page = 1;
		
		$amount = requestvar('amount');
		
		if (!$NPG_CONF['ThumbnailsPerPage']) {
			setNPGOption('ThumbnailsPerPage',20);
			$NPG_CONF['ThumbnailsPerPage'] = 20;
		}
		
		if($amount) $this->pageamount = intval($amount);
		else $this->pageamount = $NPG_CONF['ThumbnailsPerPage'];
		
		$offset = intval($page - 1) * $this->pageamount;
		if ($offset <= 0) $offset = 0;
		$this->displayoffset = $offset;
		
		if(!$NPG_CONF['template']) $NPG_CONF['template'] = 1;
		
		$this->template = & new NPG_TEMPLATE($NPG_CONF['template']);
		
		$template_header = $this->template->section['ALBUM_HEADER'];
		$template_body = $this->template->section['ALBUM_BODY'];
		$template_footer = $this->template->section['ALBUM_FOOTER'];

		$actions = new ALBUM_ACTIONS($this);
		$parser = new PARSER($actions->getdefinedActions(),$actions);
		$actions->setparser($parser);
		
		$data = $this->get_pictures($this->getId(),$so);
		
		//header
		$parser->parse($template_header);
		
		//body
		$i=0;
		while($data[$i]) {
			if($i >= $offset && $i < ($offset + $this->pageamount)) {
				$actions->setCurrentThumb($data[$i]);
				$parser->parse($template_body);
				}
			$i++;
		}
		
		//footer
		$parser->parse($template_footer);
	} //end of display()
	
function displayset($splitdata,$sort) {
		global $NPG_CONF,$manager;
		$defaultorder = $NPG_CONF['defaultorder'];
		$sorting = array('title'=>'title ASC',
						'desc'=>'description ASC',
						'owner'=>'ownername ASC',
						'date'=>'modified DESC',
						'titlea'=>'title DESC',
						'desca'=>'description DESC',
						'ownera'=>'ownername DESC',
						'datea'=>'modified ASC',
						'filenamea'=>'filename ASC',
						'filename'=>'filename DESC');
		if($sort){
			$so = 'order by '.$sorting[$sort].', pictureid DESC';
		}
		else {
			$so = 'order by '.$sorting[$defaultorder].', pictureid DESC';
		}
		
		if(!$NPG_CONF['template']) $NPG_CONF['template'] = 1;
		
		$this->template = & new NPG_TEMPLATE($NPG_CONF['template']);
		
		$template_setdisplay = $this->template->section['ALBUM_BODY'];

		$actions = new ALBUM_ACTIONS($this);
		$parser = new PARSER($actions->getdefinedActions(),$actions);
		$actions->setparser($parser);
		
		$data = $this->get_set_pictures($splitdata,$so);
		
		//header
		//$parser->parse($template_setdisplay);
		
		//body
		$i=0;
		while($data[$i]) {
			$actions->setCurrentThumb($data[$i]);
			$parser->parse($template_setdisplay);
			$i++;
		}
	} //end of displayset()
	
} //end album class

class ALBUM_ACTIONS extends BaseActions {
	var $CurrentThumb; //query object
	var $album;
	var $parser;

	
	function ALBUM_ACTIONS(& $currentalbum) {
		$this->BaseActions();
		$this->album = & $currentalbum;
		
	}

	function getdefinedActions() {
		return array(
			'breadcrumb',
			'sortbytitle',
			'sortbydescription',
			'sortbyowner',
			'sortbymodified',
			'sortbynumber',
			'albumtitle',
			'albumid',
			'albumdescription',
			'picturedescription',
			'picturelink',
			'thumbnail',
			'centeredtopmargin',
			'pictureviews',
			'editalbumlink',
			'addpicturelink',
			'picturetitle',
			'pages',
			'albumlink',
			'if',
			'else',
			'endif' );
			
	}
	
	function setParser(&$parser) {$this->parser =& $parser; }
	function setCurrentThumb(&$currentthumb) { $this->CurrentThumb =& $currentthumb; }
	
	function parse_pages($sep = ' ') {
		
		$totalpages = $this->album->totalpictures / $this->album->pageamount;
		$currentpage = floor($this->album->displayoffset / $this->album->pageamount);

		
		for($j=0; $j < $totalpages; $j++) {
			$extra['page']=$j+1;
			$extra['amount']=$this->album->pageamount;
			if ($j == $currentpage) echo ($j+1);
			else {
				echo '<a href="';
				$this->parse_albumlink($extra);
				echo '">'.($j+1).'</a>';
			}
			if($j <> $totalpages) echo $sep;			
		}
	}
	function parse_sortbytitle() { 
		$so = requestvar('sort');
		if($so == 'title') $so = 'titlea'; else $so = 'title';
		echo generateLink('album', $so); 
	}
	function parse_sortbydescription() {
		$so = requestvar('sort');
		if($so == 'desc') $so = 'desca'; else $so = 'desc';
		echo generateLink('album', $so); 
	}
	function parse_sortbyowner() {
		$so = requestvar('sort');
		if($so == 'owner') $so = 'ownera'; else $so = 'owner';
		echo generateLink('album', $so); 
	}
	function parse_sortbymodified() {
		$so = requestvar('sort');
		if($so == 'date') $so = 'datea'; else $so = 'date';
		echo generateLink('album', $so); 
	}
	function parse_sortbynumber() {
		$so = requestvar('sort');
		if($so == 'numb') $so = 'numba'; else $so = 'numb';
		echo generateLink('album', $so); 
	}
	function parse_albumlink($extra2 = 0) {
		$type = requestvar('type');
		$knownactions = array( 'album','item' );
		if(in_array($type,$knownactions)) {
			$extra['id'] = $this->album->getID();
			$type = 'album';
		}
		else {
			$allowed = array('limit');
			foreach($_GET as $key => $value) if(in_array($key,$allowed)) $extra[$key] = $value;
		}
		$extraparams = array_merge($extra, $extra2);
		echo NP_gallery::MakeLink($type,$extraparams);
	}
	
	function parse_breadcrumb($sep = '>') {
		echo '<a href="';
		echo generateLink('list');
		echo '">'.__NPG_BREADCRUMB_GALLERY.'</a> '.$sep.' ';
		$this->parse_albumtitle();		
	}
	
	function parse_albumtitle() {
		echo $this->album->getTitle();
	}
	function parse_albumid(){
		echo $this->album->getId();
	}

	function parse_albumdescription() {echo $this->album->getDescription(); }
	function parse_picturedescription() {echo $this->CurrentThumb->description; }
	function parse_picturelink() { 
		$type = requestvar('type');
		$sort = requestvar('sort');
		if($type) {
			if($type == 'album') $ltype = 'item';
			else $ltype = $type;
		} else $ltype = 'item';
		$extra = array('id' => $this->CurrentThumb->pictureid,
						'sort' => $sort
						);
		$allowed = array('limit');
		foreach($_GET as $key => $value) if(in_array($key,$allowed)) $extra[$key] = $value;
		echo NP_gallery::MakeLink($ltype, $extra ); 
		}
		
	function parse_thumbnail() { 
		global $CONF;
		echo $CONF['IndexURL'].$this->CurrentThumb->thumb_filename;
	}
	
	function parse_picturetitle() {echo $this->CurrentThumb->title; }
	function parse_centeredtopmargin($height,$adjustment) {
		global $NP_BASE_DIR;
		$image_size = getimagesize($NP_BASE_DIR.$this->CurrentThumb->thumb_filename);
		$topmargin = ((intval($height) - intval($image_size[1])) / 2) + intval($adjustment);
		echo 'margin-top: '.$topmargin.'px;';
	}
	function parse_pictureviews() {echo $this->CurrentThumb->views; }
	function parse_editalbumlink() { if($this->album->getID()) echo generateLink('editAlbumF',$this->album->getID() );}
	function parse_addpicturelink() { if($this->album->getID()) echo generateLink('addPictF',$this->album->getID() );}
	
	function parse_if($field, $name='', $value = '') {
		global $gmember;
		
		$condition = 0;
		switch ($field) {
			case 'canaddpicture':
				$condition = $gmember->canAddPicture($this->album->getID());
				break;
			case 'caneditalbum':
				$condition = $gmember->canModifyAlbum($this->album->getID());
				break;
			default: 
				break;
		}
		
		$this->_addIfCondition($condition);
		
	}
}

?>
