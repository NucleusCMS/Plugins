<?php

class PUBMED_TEMPLATE_BASE {
	/*
	 * This class must be inhelited by PUBMED_TEMPLATE class.
	 * Note that PHP 5 is needed.
	 */
	/* 
	 * Default methods follow.
	 * These methods will be overrided in PUBMED_TEMPLATE class.
	 */
	public function manualSort(){
		// If the order of manuscript is manually sorted (PNAS etc),
		// this method must return true.
		return false;
	}
	public function sortPapers(){
		$this->sortByAuthorName();
	}
	public function parse_header(){
		return '<br />';
	}
	public function parse_footer(){
		return '<br />';
	}
	public function parse($num,$pmid,$xml,$authors,$year,$journal,$volume,$pages,$title){
		return <<<END

<p>{$this->parse_authors($authors)} ({$year})<br />
<i>{$journal}</i> <b>{$volume}</b>, {$pages}<br />
{$title}</p>

END;
	}
	public function parse_authors($authors, $and=', and '){
		$num=count($authors);
		switch($num){
			case 1:
				$result=$this->parse_author($authors[0]);
				break;
			case 2:
				$result=$this->parse_author($authors[0]).$and.$this->parse_author($authors[1]);
				break;
			default:
				$result=$this->parse_author($authors[0]);
				for($i=1;$i<$num;$i++){
					if ($i==1) $result.=', ';
					elseif ($i==$num-1) $result.=$and;
					$result.=$this->parse_author($authors[$i]);
				}
				break;
		}
		return htmlspecialchars($result);
	}
	public function parse_author($author){
		$result=$author->LastName.', ';
		$initials=$author->Initials;
		for($i=0;$i<strlen($initials);$i++) $result.=substr($initials,$i,1).'.';
		return $result;
	}
	/*
	 * Following methods shouldn't be overrided.
	 */
	protected $sortdata=array(),$data=array();
	public static final function getTemplate($template){
		// Static method.
		// Define PUBLED_TEMPLATE class
		if (!preg_match('/^[a-z0-9A-Z_]+$/',$template)) exit('Bad template name!');
		$file=dirname(__FILE__)."/templates/{$template}.php";
		if (!file_exists($file)) return false;
		require_once($file);
		$obj = new PUBMED_TEMPLATE;
		return $obj;
	}
	public final function setData($more,$sort){
		// $more is the $item->more.
		if (!preg_match('#<MedlineCitation[^>]*>([\s\S]*?)</MedlineCitation>#',$more,$m)) return;
		$xml="<?xml version='1.0'?>\r\n<document>\r\n$m[1]\r\n</document>";
		$xml=simplexml_load_string($xml);
		$pmid=(int)$xml->PMID;
		$this->data[$pmid]=$xml;
		if (!isset($this->sortdata[$sort])) $this->sortdata[$sort]=$pmid;
		else $this->sortdata[]=$pmid;
	}
	public final function parse_all(){
		echo $this->parse_header();
		$num=0;
		$sortdata=$this->sortdata;
		ksort($sortdata);
		foreach($sortdata as $pmid) {
			$xml=$this->data[$pmid];
			$num++;
			// Get year
			$year=$xml->Article->Journal->JournalIssue->PubDate->Year;
			// Get journal name
			$journal=$xml->Article->Journal->ISOAbbreviation;
			// Get volume
			$volume=$xml->Article->Journal->JournalIssue->Volume;
			// Get paper title
			$title=$xml->Article->ArticleTitle;
			if (substr($title,-1,1)!=='.') $title.='.';
			// Get the start and end pages
			$pages=explode('-',(string)$xml->Article->Pagination->MedlinePgn);
			$pages=$pages[0].'-'.substr($pages[0],0,strlen($pages[0])-strlen($pages[1])).$pages[1];
			// Let's parse the citation
			echo $this->parse( (int)$num,(int)$pmid,$xml,$xml->Article->AuthorList->Author
				,htmlspecialchars($year) // Don't use (int) because it may be like 2008a
				,htmlspecialchars($journal)
				,(int)$volume
				,htmlspecialchars($pages)
				,htmlspecialchars($title)
				);
		}
		echo $this->parse_footer();
	}
	/* Sort methods follow.
	 * note that these methods will be called from "sortPapers" method
	 */
	public final function sortByAuthorName(){
		$citations=array();
		$papers=array();
		$i=0;
		foreach($this->data as $pmid=>&$xml){
			$i++;
			// Get date
			$year=(int)$xml->Article->Journal->JournalIssue->PubDate->Year;
			$month=(string)$xml->Article->Journal->JournalIssue->PubDate->Month;
			$month=$this->month($month);
			$month=$month<10 ? "0$month" : "$month";
			$day=(int)$xml->Article->Journal->JournalIssue->PubDate->Day;
			$day=$day<10 ? "0$day" : "$day";
			$date="$year-$month-$day";
			// Get Authors
			$authors=$xml->Article->AuthorList->Author;
			$firstauthor=$authors[0]->LastName.', '.$authors[0]->Initials;
			$authornum=count($authors);
			switch($authornum){
				case 1:
					$citation=$authors[0]->LastName.", $year";
					break;
				case 2:
					$citation=$authors[0]->LastName.' and '.$authors[1]->LastName.", $year";
					break;
				default:
					$citation=$authors[0]->LastName." et al., $year";
					break;
			}
			if (!isset($citations[$citation])) $citations[$citation]=array();
			$citations[$citation][]=$pmid;
			// Construct the sort key and cache data
			$key="$firstauthor $year $citation $date $i";
			$papers[$key]=$pmid;
		}
		// Modify Year (for example, 2008 => 2008a, 2008b, etc.
		$abc='abcdefghijklmnopqrstuvwxyz';
		foreach($citations as $key=>$value){
			if (count($value)<2) continue;
			for ($i=0;$i<count($value);$i++){
				$pmid=$value[$i];
				$year=(int)$this->data[$pmid]->Article->Journal->JournalIssue->PubDate->Year;
				$this->data[$pmid]->Article->Journal->JournalIssue->PubDate->Year=(string)$year.substr($abc,$i,1);
			}
		}
		// Sort the data
		ksort($papers);
		// Let's get the result.
		$result=array();
		foreach($papers as $pmid) $result[]=$pmid;
		$this->sortdata=$result;
	}
	protected final function month($month){
		if (is_numeric($month)) return (int)$month;
		switch(strtolower($month)){
			case 'january':
			case 'jan': return 1;
			case 'february':
			case 'feb': return 2;
			case 'march':
			case 'mar': return 3;
			case 'april':
			case 'apr': return 4;
			case 'may': return 5;
			case 'june':
			case 'jun': return 6;
			case 'july':
			case 'jul': return 7;
			case 'august':
			case 'aug': return 8;
			case 'september':
			case 'sep': return 9;
			case 'october':
			case 'oct': return 10;
			case 'november':
			case 'nov': return 11;
			case 'december':
			case 'dec': return 12;
			default: return 0;
		}
	}
	
}