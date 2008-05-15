<?php
/**
 * NP_List - Output various lists (with sub-plugins) for NucleusCMS
 * 
 * @author   yu
 * @license  GNU GPL2
 * @version  0.4
 * 
 * History
 * v0.4   2008-05-14  Support itemvars. (yu)
 *                    Add operator "=~"(LIKE) and "!~"(NOT LIKE) in filter. (yu)
 *                    Fix filter bug ("!=" and "<>" weren't worked correctly). (yu)
 * v0.32  2008-05-13  Support current memberid in getFilter(). (yu)
 * v0.31  2008-05-08  Fix subplugin loader (NP_ListOf*Uppercase-first*). (yu)
 * v0.3   2008-05-06  Refine subplugin class. (yu)
 * v0.2   2008-05-02  Add new methods in subplugin class. (yu)
 * v0.1   2008-04-30  First release. (yu)
 */
class NP_List extends NucleusPlugin 
{
    function getName() { return 'List'; }
    function getAuthor()  { return 'yu'; }
    function getURL() { return 'http://nucleus.datoka.jp/'; }
    function getVersion() { return '0.4'; }
    function getMinNucleusVersion() { return 330; }
    function supportsFeature($what) { return (int)($what == 'SqlTablePrefix'); }
//  function getPluginDep() { return array('NP_Container'); }

    function getDescription()
    { 
        return "Output various lists with sub-plugins.";
    }
    
    var $params;
    var $parts;
    var $container;
    
    function init()
    {
        global $manager, $blogid;
        
        $this->params = array();
        $this->parts = array();
        
        //if NP_Container is installed, hold reference of container instance.
        if ($manager->pluginInstalled('NP_Container')) {
            $this->container =& $manager->getPlugin('NP_Container');
        }
    }

    function doItemVar(&$item)
    {
        global $blogid;
        
        //check container parts
        $this->checkContainer();
        
        //init params
        $this->params = array(); //initialize
        $this->params['blogid'] = $blogid;
        $this->params['skintype'] = 'item';
        $this->params['item'] =& $item;
        
        //set params
        $params = func_get_args();
        array_shift($params); // remove item reference
        $this->setParams($params);
        
        if ($this->params['type']) {
            //dynamic call
            $sub =& $this->callSubPlugin($this->params['type']);
            if (is_object($sub)) $sub->main();
        }
    }
    
    function doSkinVar($skinType)
    {
        global $blogid;
        
        //check container parts
        $this->checkContainer();
        
        //init params
        $this->params = array(); //initialize
        $this->params['blogid'] = $blogid;
        $this->params['skintype'] = $skinType;
        
        //set params
        $params = func_get_args();
        array_shift($params); // remove skintype parameter
        $this->setParams($params);
        
        if ($this->params['type']) {
            //dynamic call
            $sub =& $this->callSubPlugin($this->params['type']);
            if (is_object($sub)) $sub->main();
        }
    }

    /**
     * parse param string and set them to $this->params
     * 
     * @access private
     * @param  $params  array of parameters
     * @return void
     */
    function setParams($params)
    {
        foreach ($params as $param) {
            if ($flg_esc = (strpos($param, '\:') !== false)) $param = str_replace('\:', '[[ESCAPED-COLON]]', $param);
            list($key, $value) = explode(':', $param);
            if ($flg_esc) $value = str_replace('[[ESCAPED-COLON]]', ':', $value);
            
            switch ($key) {
            case 'amount':
                $this->params['amount'] = (int)$value;
                break;
            case 'len':
            case 'length':
                $this->params['length'] = (int)$value;
                break;
            case 'tpl':
            case 'template':
                $this->params['template'] = $value;
                break;
            default:
                $this->params[$key] = $value;
            }
        }
    }
    
    /**
     * check container parts in skin data
     * 
     * @access private
     * @param  void
     * @return void
     */
    function checkContainer()
    {
        static $flg_setparts;
        
        //get container parts (if exists)
        if (isset($this->container) and !$flg_setparts) {
            if ( is_array($cparts = $this->container->getParts($this->getName())) ) {
                foreach ($cparts as $ckey => $cval) {
                    list($ckey2, $ckey3) = split('_', $ckey, 2);
                    $this->parts[$ckey2][$ckey3] = $cval;
                }
            }
            $flg_setparts = true;
        }
    }
    
    /**
     * generate/reuse an instance of sub plugin
     * 
     * @access public
     * @param  $name  sub plugin name
     * @return object reference
     */
    function &callSubPlugin($name) 
    {
        global $DIR_PLUGINS;
        
        if (empty($name) or preg_match('/[^0-9a-zA-Z_-]/', $name)) return false;
        
        $classname = 'NP_ListOf'. ucfirst($name);
        if (!class_exists($classname)) {
            $filename = $DIR_PLUGINS .'list/'. $classname .'.php';
            if (!file_exists($filename)) {
                ACTIONLOG::add(WARNING, 'Plugin ' . $classname . ' was not loaded (File not found)');
                return false;
            }
            
            include_once($filename);
            
            if (!class_exists($classname)) {
                ACTIONLOG::add(WARNING, 'Plugin ' . $classname . ' was not loaded (Class not found in file, possible parse error)');
                return false;
            }
        }
        
        //$classname::show(&$params); //PHP5.3~
        //eval($classname .'::show($params);'); //call static class
        
        //eval('$sub =& '. $classname .'::getInstance($this);'); //get instance (singleton) from subplugin
        eval('$sub =& $this->getInstance('.$classname.');'); //get instance (singleton) from instance manager
        return $sub;
        
    }
    
    /**
     * get an instance of the class (singleton)
     * 
     * @access private
     * @param  $classname  class name
     * @return object
     */
    function &getInstance($classname)
    {
        static $instances;
        if ($instances[$classname] == null) {
            $instances[$classname] =& new $classname();
            $instances[$classname]->init($this);
        }
        else {
            $instances[$classname]->reset(); //reset flags and template
        }
        
        return $instances[$classname];
    }
    
    /**
     * get parsed filter string
     * 
     * @access public
     * @param  $filter    filter string
     * @param  $aliasmap  key 'search'  has an array of alias name
     *                    key 'replace' has an array of column name
     * @param  $current   an array of current values
     * @return string
     */
    function getFilter($filter, $aliasmap, $current='') 
    {
        global $blogid, $catid, $memberid, $archive;
        
        $fparams = split(' ', $filter);
        
        if (! is_array($current)) {
            $current = array(
                'blogid'   => $blogid,
                'catid'    => $catid,
                'memberid' => $memberid,
                'arcdate'  => $archive,
                );
        }
        
        $filters = array();
        foreach ($fparams as $fil) {
            //replace '@' to current id
            if (substr($fil, -1, 1) == '@') {
                preg_match('/^([a-z]+?)[!=<>]/', $fil, $match); //pick up left side
                if ($current[ $match[1] ]) {
                    $fil = substr($fil, 0, -1) . $current[ $match[1] ];
                }
                else continue; //if current key/value is not defined, skip it.
            }
            
            //replace because it can't use aliases in WHERE clause.
            $fil = str_replace($aliasmap['search'], $aliasmap['replace'], $fil);
            
            $match = null;
            preg_match('/^([a-zA-Z0-9(),._-]+?)(<>|>=|<=|!=|=~|!~|[=<>])(.+?)$/', $fil, $match);
            
            if (isset($match[1]) and isset($match[2]) and isset($match[3])) {
                if ($match[2] == '=~') $match[2] = ' LIKE ';
                if ($match[2] == '!~') $match[2] = ' NOT LIKE ';
                
                if (strpos($match[3], '|')) { //multiple values
                    $values = split('\|', $match[3]);
                    $f = array();
                    foreach ($values as $v) {
                        $f[] = $match[1] . $match[2] .'"'. mysql_real_escape_string($v) .'"';
                    }
                    if ($match[2] == '<>' or $match[2] == '!=' or $match[2] == '!~') $cond = ' AND '; //'NOT' condition
                    else $cond = ' OR '; //normal condition
                    $fil = '(' . join($cond, $f) . ')';
                    $filters[] = $fil;
                }
                else { //single value 
                    $fil = $match[1] . $match[2] .'"'. mysql_real_escape_string($match[3]) .'"';
                    $filters[] = $fil;
                }
            }
            else {
                //wrong filter. skip it.
            }
        }
        return join(' AND ', $filters);
    }
}


/**
 * base class of sub plugins
 * 
 * The class extended from this must implement main() method.
 * 
 * @abstract
 * @see NP_List
 */
class NP_ListOfSubPlugin
{
    var $caller;
    var $templates;
    var $params;
    var $flg;
    
    /**
     * main method (*MUST* implement it)
     * 
     * @access public
     * @param  void
     * @return void
     */
    function main() 
    {
        echo 'echo of sub plugin.';
    }
    
    /**
     * get default template (implement it if needed)
     * 
     * @access private
     * @param  void
     * @return array
     */
    function _getDefaultTemplate() 
    {
        return array();
    }
    
    /**
     * initicialize a instance
     * 
     * Hold a reference of the caller object (NP_List).
     * 
     * @access public
     * @param  &$oCaller  caller object
     * @return void
     * @see    NP_List::getInstance()
     */
    function init(&$oCaller) 
    {
        //set caller
        $this->caller =& $oCaller;
        
        //set params
        $this->params =& $this->caller->params;
        
        //set default params, flags and template
        $this->reset();
    }
    
    /**
     * reset flags and template
     * 
     * @access public
     * @param  void
     * @return void
     * @see    NP_List::getInstance()
     */
    function reset() 
    {
        //set default params
        $this->_setDefaultParams();
        
        //set flags
        $this->flg = $this->_getFlag($this->params['flag']);
        
        //set template
        $this->_setTemplate();
    }
    
    /**
     * set template data to property $this->templates
     * 
     * If use standard template, get it from $manager.
     * If use container, get it with param 'type' and 'tplprefix' (if it is set). 
     * First, get default data. Second, get custom parts and merge them.
     * 
     * @access private
     * @param  $forced  'true' forces to reset template
     * @return void
     */
    function _setTemplate($forced = false) 
    {
        $default = $this->_getDefaultTemplate(); // sub plugin's original default template
        
        if ($this->params['template']) { //standard Nucleus template
            $name = $this->params['template'];
        }
        else { //custom template (container)
            $name = $this->params['tplprefix'] . strtoupper($this->params['type']);
        }
        
        if ($forced or $this->templates[$name] == null) {
            if ($this->params['template']) {
                global $manager;
                $custom =& $manager->getTemplate($name);
            }
            else {
                $custom = $this->caller->parts[$name]; //container parts
            }
            
            if ($custom) {
                $this->templates[$name] = array_merge($default, $custom);
            }
            else {
                $this->templates[$name] = $default;
            }
        }
    }
    
    /**
     * get template data from property $this->templates
     * 
     * @access private
     * @param  void
     * @return array
     */
    function &_getTemplate() 
    {
        if ($this->params['template']) { //standard Nucleus template
            $name = $this->params['template'];
        }
        else {
            $name = $this->params['tplprefix'] . strtoupper($this->params['type']);
        }
        if (! $this->templates[$name]) {
            $this->_setTemplate();
        }
        
        return $this->templates[$name];
    }
    
    /**
     * get parsed filter string (wrapper)
     * 
     * @access private
     * @param  $filter    filter string
     * @param  $aliasmap  key 'search'  has an array of alias name
     *                    key 'replace' has an array of column name
     * @param  $current   an array of current values
     * @return string
     * @see    NP_List::getFilter()
     */
    function _getFilter($filter, $aliasmap, $current='') 
    {
        return $this->caller->getFilter($filter, $aliasmap, $current);
    }
    
    /**
     * get default params
     * 
     * @access private
     * @param  void
     * @return void
     */
    function _setDefaultParams() 
    {
        if (!isset($this->params['amount'])) $this->params['amount'] = 5;
        if (!isset($this->params['length'])) $this->params['length'] = 25;
        if (!isset($this->params['trimmarker'])) $this->params['trimmarker'] = '..';
    }
    
    /**
     * get flags
     * 
     * @access private
     * @param  $str   flag string
     * @return array
     */
    function _getFlag($str) 
    {
        $keys = split(' ', $str);
        $flags = array();
        foreach ($keys as $key) {
            if (empty($key)) continue;
            $flags[$key] = true;
        }
        
        return $flags;
    }
    
    /**
     * scan a templatevar in the string
     * 
     * @access private
     * @param $str  string
     * @param $var  templatevar name
     * @return bool
     */
    function _scan($str, $var)
    {
        return (strpos($str, $var) !== false);
    }
    
    /**
     * fill the template with variables (wrapper)
     * 
     * @access private
     * @param $str   template string
     * @param $vars  array of vars
     * @return string
     */
    function _fill($str, $vars)
    {
        return TEMPLATE::fill($str, $vars);
    }
    
    /**
     * make class property
     * 
     * @access private
     * @param $class  array (classname => condition)
     * @return string
     */
    function _makeClassProperty($class)
    {
        $str = '';
        if (!count($class)) return $str;
        
        foreach ($class as $name => $bool) {
            if ($bool) $str .= ' '.$name;
        }
        if ($str) $str = ' class="'. ltrim($str) .'"';
        
        return $str;
    }
}

?>
