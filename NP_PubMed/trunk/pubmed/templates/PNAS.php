<?php

class PUBMED_TEMPLATE extends PUBMED_TEMPLATE_BASE {
	public function parse($num,$pmid,$xml,$authors,$year,$journal,$volume,$pages,$title){
		$year=(int)$year;
		return <<<END

{$this->parse_authors($authors,', & ')} ({$year}). {$journal} <b>{$volume}</b>, {$pages}<br />
<br />

END;
	}
	public function parse_author($author){
		$result=$author->LastName.', ';
		$initials=$author->Initials;
		for($i=0;$i<strlen($initials);$i++) $result.=substr($initials,$i,1).'. ';
		return substr($result,0,strlen($result)-1);
	}
}