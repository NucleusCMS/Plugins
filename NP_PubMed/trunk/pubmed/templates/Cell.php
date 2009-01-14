<?php

class PUBMED_TEMPLATE extends PUBMED_TEMPLATE_BASE {
	public function parse($num,$pmid,$xml,$authors,$year,$journal,$volume,$pages,$title){
		return <<<END

<p>{$this->parse_authors($authors)} ({$year}). {$title} {$journal} <i>{$volume}</i>, {$pages}<br />
</p>

END;
	}
}