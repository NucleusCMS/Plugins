<?
// plugin needs to work on Nucleus versions <=2.0 as well
if (!function_exists('sql_table')){
	function sql_table($name) {
		return 'nucleus_' . $name;
	}
}

/*
	Version history:
	- 0.1 (2003-08-13): initial version
*/
class NP_BlogList extends NucleusPlugin {

	function getName() {	return 'Blog List';	 }
	function getAuthor()  { return 'Ben Osman + nakahara21';	 }
	function getURL() {		return 'http://www.justletgo.org/'; }
	function getVersion() {	return '0.2'; }
	function getDescription() { 
		return 'List can be shown using &lt;%BlogList%&gt; OR &lt;%BlogList(bpublic = 1)%&gt;. <br /> It has following parameters : filter, header, list, footer)';
	}
	function supportsFeature($what) {
		switch($what){
			case 'SqlTablePrefix':
				return 1;
			default:
				return 0;
		}
	}

	function install() {
		$this->createOption('OrderBy','Field that list is sorted by','text','bnumber ASC');
		$this->createOption('Header','Header Template','text','<ul class="nobullets">');
		$this->createOption('List','List Template ','text','<li><a href="<%bloglink%>"><%blogname%></a><%flag%></li>');
		$this->createOption('Footer','Footer Template','text','</ul>');
	}
	
	function unInstall() {
	}
	
	function init() {
	}
	
  function doSkinVar($skinType, $filter ='', $header ='', $list='', $footer='')
  { 
  global $CONF, $blog;
	
		// determine arguments next to catids
		// I guess this can be done in a better way, but it works
		if (!$header) {
		  $header = $this->getOption('Header');
		}
		if (!$list) {
		  $list = $this->getOption('List');
		}
		if (!$footer) {
		  $footer = $this->getOption('Footer');
		}
			
		//$blogurl = $this->getURL() . $qargs;
		$blogurl = createBlogLink($this->getURL(), $linkparams);

		$template = TEMPLATE::read($template);

		echo TEMPLATE::fill($header,
							array(
							));

		$where = '';
		if ($filter <> '') {
			$where =  'WHERE '.$filter;
		}

//		$query = 'SELECT *,b.bnumber as blogid, b.bname as blogname, b.burl as bloglink  FROM nucleus_blog	as b ' . $where . ' ORDER BY ' . $this->getOption('OrderBy');
		$query = 'SELECT *,b.bnumber as blogid, b.bname as blogname  FROM '.sql_table('blog').' as b ' . $where . ' ORDER BY ' . $this->getOption('OrderBy');

		$res = sql_query($query);
		while ($data = mysql_fetch_assoc($res)) {
			$data['self'] = $CONF['Self'];
			$data['bloglink'] = createBlogidLink($data['blogid'], '');

			if( $data['blogid'] == $blog->getID() ){
			$data['flag'] = " &laquo;";	//mark this blog!
			}

			$temp = TEMPLATE::fill($list,$data);
			echo strftime($temp,$current->itime);

		}
		
		mysql_free_result($res);

		echo TEMPLATE::fill($footer,
							array(
							));
	}
}
?>