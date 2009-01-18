<?php

class PUBMED_TEMPLATE extends PUBMED_TEMPLATE_BASE {
	public function sortPapers(){
		if ($this->sortarray=='authorname') $this->sortByAuthorName();
		else $this->manualSort();
	}
	public function parse_header(){
		global $manager;
		// Construct ticket hidden
		ob_start();
		$manager->AddTicketHidden();
		$ticket=ob_get_contents();
		ob_end_clean();
		// Construct options
		$options=<<<END
  <option value="nothing">- select action -</option>
  <option value="delete">Delete</option>
END;
		if ($this->sortarray!==false) $options.=<<<END
  <option value="moveup">Move up</option>
  <option value="movedown">Move down</option>
  <option value="totop">Bring to top</option>
  <option value="tobottom">Bring to bottom</option>
END;
		// Everything done.  Return the result.
		return <<<END

<form method="post" action="">
{$ticket}
<select name="batchaction">
{$options}
</select>
the selected items.
&nbsp;&nbsp;&nbsp;
<input type="submit" value="execute" />
&nbsp;&nbsp;&nbsp;
(
  <a href="" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return batchSelectAll(1); ">Select all</a> -
  <a href="" onclick="if (event &amp;&amp; event.preventDefault) event.preventDefault(); return batchSelectAll(0); ">Unselect all</a>
)

END;
	}
	public function parse($num,$pmid,$xml,$authors,$year,$journal,$volume,$pages,$title){
		$itemid=$this->itemid[$pmid];
		if (isset($_POST['batch'])) $checked = in_array($itemid,$_POST['batch']) ? ' checked="checked"' : '';
		else $checked='';
		return <<<END

<p>
<input id="batch{$num}" name="batch[{$num}]" value="{$itemid}" type="checkbox"{$checked}/>
{$this->parse_authors($authors)} ({$year}) <i>{$journal}</i> <b>{$volume}</b>, {$pages}
</p>

END;
	}
	public function parse_footer(){
		return <<<END

</form>
<script type="text/javascript">
/*<![CDATA[*/
function batchSelectAll(set){
  var i=1;
  var obj;
  while(obj=document.getElementById('batch'+i)){
    if (set) obj.checked='checked';
    else obj.checked='';
    i++;
  }
}
/*]]>*/
</script>

END;
	}

}