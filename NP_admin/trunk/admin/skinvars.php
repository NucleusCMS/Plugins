<?php
class NP_admin_skinvars extends NP_admin_basic {
	function var_admin(){
		global $manager;
		$action=$this->action;
		$this->properties['action']=$action;
		$this->svm->parse_parsedinclude('index',"actions/$action.inc");
	}
	function var_global($p1,$p2=''){
		if (strlen($p1)) self::p($GLOBALS[$p1][$p2]);
		else self::p($GLOBALS[$p1]);
	}
	var $msg='';
	function var_message($prefix=false){
		if ($prefix===false) $prefix=_MESSAGE.': ';
		$msg=$this->msg;
		if (strlen($msg)) {
			if (defined($msg)) $msg=constant($msg);
			self::p($prefix.$msg);
		}
	}
	function var_setmessage($msg){
		if (defined($msg)) $msg=constant($msg);
		$this->msg=$msg;
	}
	function var_date($p1,$p2='',$mode='blogtime'){
		global $blog;
		if (!is_object($blog)) $mode='servertime';
		if ($p2=='') {
			switch($mode){
				case 'servertime':
					return self::p(date($p1));
				case 'blogtime':
				default:
					return self::p(date($p1,$blog->getCorrectTime()));
			}
		}
		$row=&$this->rowdata;
		$time=$row[$p2];
		if (!is_numeric($time)) $time=strtotime($time);
		self::p(date($p1,$time));
	}
	var $properties=array();
	function var_set($key,$value=''){
		$this->properties[$key]=$value;
	}
	function var_get($key){
		self::p($this->properties[$key]);
	}
	var $ob=false;
	function var_begin($key){
		if (ob_start()) $this->ob=$key;
	}
	function var_end(){
		$key=$this->ob;
		$this->ob=false;
		if ($key===false) return;
		$data=ob_get_contents();
		ob_end_clean();
		$this->properties[$key]=$data;
	}
	function var_sprintf($text,$key){
		$data=self::hsc($this->_contents($key),'noampamp');
		if (defined($text)) echo sprintf(constant($text),$data);
		else echo sprintf(self::hsc($text),$data);
	}
	function var_getvar($key,$default=''){
		return $this->_requestvar('getVar',$key,$default);
	}
	function var_postvar($key,$default=''){
		return $this->_requestvar('postVar',$key,$default);
	}
	function var_requestvar($key,$default=''){
		return $this->_requestvar('requestVar',$key,$default);
	}
	function _requestvar($gpr,$key,$default=''){
		//$gpr is either getVar, postVar, or requestVar
		$value=call_user_func($gpr,$key);
		if (strlen($value)==0) $value=$default;
		self::p($value);
	}
	function var_inputhiddenfromget($key,$mode=''){
		return $this->_inputhiddenfromrequest('getVar',$key,$mode);
	}
	function var_inputhiddenfrompost($key,$mode=''){
		return $this->_inputhiddenfromrequest('postVar',$key,$mode);
	}
	function var_inputhiddenfromrequest($key,$mode=''){
		return $this->_inputhiddenfromrequest('requestVar',$key,$mode);
	}
	function _inputhiddenfromrequest($gpr,$key,$mode=''){
		//$gpr is either getVar, postVar, or requestVar
		$amount=(int)call_user_func($gpr,'amount');
		if ($amount==0) $amount=10;
		switch($mode){
			case 'prev':
				$value=(int)call_user_func($gpr,'start')-$amount;
				if ($value<0) $value=0;
				break;
			case 'next':
				$value=(int)call_user_func($gpr,'start')+$amount;
				break;
			case '':
			default:
				$value=call_user_func($gpr,$key);
		}
		if (strlen($value)==0) return;
		echo self::fill('<input type="hidden" name="<%key%>" value="<%value%>" />',
			array('key'=>$key,'value'=>$value));
	}
	var $rowdata=array();
	function var_rowdata($name,$mode='hsc', $maxlength=10, $toadd=''){
		$row=&$this->rowdata;
		if (isset($row[$name])) $this->_showContents($row[$name],$mode,$maxlength,$toadd);
	}
	function var_ticket($mode='row'){
		static $ticket;
		global $manager;
		if (!isset($ticket)) $ticket=$manager->getNewTicket();
		switch($mode){
			case 'hidden':
				$manager->addTicketHidden();
				break;
			case 'row':
			default:
				self::p($ticket);
				break;
		}
	}
	function var_bookmarklet($blogid=''){
		global $blog;
		if ($blogid) self::p(getBookmarklet($blogid));
		else self::p(getBookmarklet($blog->getID()));
	}
	function var_blogsetting($type){
		self::p($this->_blogsetting($type));
	}
	function _blogsetting($type){
		global $blog;
		if (method_exists($blog,"get$type")) return call_user_func(array(&$blog,"get$type"));
		elseif (preg_match('/^notifyon/i',$type) && method_exists($blog,$type)) return call_user_func(array(&$blog,$type));
		else return $blog->getSetting($type);
	}
	function var_help($id){
		global $CONF;
		ob_start();
		help($id);
		$html=ob_get_contents();
		ob_end_clean();
		echo str_replace('"documentation/','"'.self::hsc($CONF['AdminURL']).'documentation/',$html);
	}
	function var_insertPluginOptions($context){
		switch($context){
			case 'blog':
				// skin var
				$contextid=requestVar('blogid');
				break;
			case 'member':
				// template var
				$contextid=$this->rowdata['mnumber'];
				break;
			case 'item':
				// skin var
				$contextid=intRequestVar('itemid');
				break;
			case 'global':
				break;
			default:
				exit('<td>insertPluginOptions: wrong context.</td></tr></table>');
		}
		$admin=$this->getAdminObject();
		// I know it isn't good to call methods whose names start from '_',
		// but, this is the better way than copying the whole code from the method,
		// because of supporting the new version of nucleus.
		// Let's think about this again later when 'private' is set to these methods.
		$admin->_insertPluginOptions($context, $contextid);
	}
	function var_showerror($msg){
		if (defined($msg)) $msg=constant($msg);
		$this->msg=$msg;
		$this->svm->parse_parsedinclude('index','actions/error.inc');
	}
	function var_insertJavaScriptInfo($authorid=''){
//TODO: support passing authorid information when editing item.
		global $blog;
		$blog->insertJavaScriptInfo($authorid);
	}
	/*
	 * Stuffs for overriding skinvars.
	 * Note that NP_SkinVarManager is required to do this.
	 * Note that <%if(admin)%> is also overrided.
	 */
	function event_RegisterSkinVars(&$data) {
		$data['skinvars']['text']=array(&$this,'parse_text');
		$data['skinvars']['conf']=array(&$this,'parse_conf');
		$data['skinvars']['note']=array(&$this,'parse_note');
		$data['skinvars']['callback']=array(&$this,'parse_callback');
		$data['skinvars']['contents']=array(&$this,'parse_contents');
		$data['ifvars']['admin']=array(&$this,'parse_ifadmin');
		$data['ifvars']['blogadmin']=array(&$this,'parse_ifblogadmin');
		$data['ifvars']['contents']=array(&$this,'parse_ifcontents');
	}
	function parse_text($skinType,$type) {
		if (defined($type)) echo constant($type);
		else echo htmlspecialchars($type,ENT_QUOTES,_CHARSET);
	}
	function parse_conf($skinType,$type) {
		global $CONF;
		echo htmlspecialchars($CONF[$type],ENT_QUOTES,_CHARSET);
	}
	function parse_note(){
		// Don't do anything.
	}
	function parse_callback($skinType,$eventName, $type=''){
		static $cbobj;
		if (!isset($cbobj)) {
			require_once(dirname(__FILE__).'/callback.php');
			$cbobj=new NP_admin_callback($this);
		}
		return $cbobj->parse_callback($skinType,$eventName,$type);
	}
	function parse_contents($skinType,$key,$mode='hsc', $maxlength=10, $toadd=''){
		$data=$this->_contents($key);
		if ($data!==false) $this->_showContents($data,$mode,$maxlength,$toadd);
	}
	function _showContents($data,$mode='hsc', $maxlength=10, $toadd=''){
		switch($mode){
			case 'row':
				echo $data;
				break;
			case 'strip_tags':
				self::p(strip_tags($data));
				break;
			case 'shorten':
				self::p(shorten(strip_tags($data),$maxlength,$toadd));
				break;
			case 'hsc':
			default:
				self::p($data);
		}
	}
	function _contents($key){
		if (isset($this->rowdata[$key])) return (string)$this->rowdata[$key];
		if (isset($this->properties[$key])) return (string)$this->properties[$key];
		return false;
	}
	function parse_ifblogadmin($p1='',$p2=''){
		return $this->svm->handler->checkCondition('admin',$p1,$p2);
	}
	function parse_ifcontents($p1='',$p2=''){
		if (preg_match('/^isset:(.*)$/',$p1,$m)) return ($this->_contents($m[1])!==false);
		if (preg_match('/^(.*)=(.*)$/',$p1) && $p2=='') {
			$p1=$m[1];
			$p2=$m[2];
		}
		return ( $this->_contents($p1) == $p2 );
	}
	function parse_ifadmin($p1='',$p2=''){
		if ($p1=='') $p1='admin';
		// If there isn't the corresponding method, a fatal error occurs.
		// Probably, this is better way than returning false to continue the process.
		return call_user_func(array(&$this,"if_$p1"),$p2);
	}
	function if_admin($p2){
		global $member;
		return ( $member->isLoggedIn() && $member->isAdmin() );
	}
	function if_rowdata($p2){
		$row=&$this->rowdata;
		if (!preg_match('/^(.*)=(.*)$/',$p2,$m)) return isset($row[$p2]);
		return $row[$m[1]]==$m[2];
	}
	function if_conf($p2){
		global $CONF;
		if (!preg_match('/^(.*)=(.*)$/',$p2,$m)) return isset($CONF[$p2]);
		return $CONF[$m[1]]==$m[2];
	}
	function if_future($p2){
		// template var
		global $blog,$manager;
		$row=&$this->rowdata;
		$t=$row[$p2];
		if (!is_numeric($t)) $t=strtotime($t);
		if (isset($row['bnumber'])) $b=&$manager->getBlog($row['bnumber']);
		else $b=&$blog;
		return $t>$b->getCorrectTime(time());
	}
	function if_set($p2){
		if (!preg_match('/^(.*)=(.*)$/',$p2,$m)) return isset($this->properties[$p2]);
		return $this->properties[$m[1]]==$m[2];
	}
	function if_getvar($p2){
		list($key,$value)=$this->keyAndValue($p2);
		if ($value===false) return getVar($key)!='';
		else return getVar($key)==$value;
	}
	function keyAndValue($p2){
		$ret=array();
		if (preg_match('/^(.*)=(.*)$/',$p2,$m)) return array($m[1],$m[2]);
		else return array($p2,false);
	}
	function if_eventSubscribed($p2){
		return 0<numberOfEventSubscriber($p2);
	}
	function if_compare($p2){
		if (!preg_match('/^(.*)=(.*)$/',$p2,$m)) exit('Fetal error: if_compare');
		return $this->properties[$m[1]]==$this->properties[$m[2]];
	}

}