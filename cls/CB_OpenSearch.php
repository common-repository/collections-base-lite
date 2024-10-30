<?php
require_once(dirname(__FILE__).'/CB_Search.php');
/**
 * A class to query the API using the OpenSearch interface.
 *
 * @author Mark Oliver Brawn
 * @copyright Orangeleaf Systems Ltd
 * @version 1.0.1
 */
class CB_OpenSearch extends CB_Search
{
	const NAMESPACE_OS = 'http://a9.com/-/spec/opensearch/1.1/';
	const NAMESPACE_SRU = 'http://a9.com/-/opensearch/extensions/sru/2.0/';
	
	const ARCHIVE = 'Archive'; //jg changed from museum
	const MUSEUM = 'Museum';
	const LOCAL_HISTORY = 'LocalHistory';
	const HER = 'HER';
	
	/**
	 * The query string
	 *
	 * @var string
	 */
	public $query = '';
	public $itemsPerPage = 10;
	public $startPage = 1;	
	/**
	 * The main feed url
	 *
	 * @var string
	 */
	private $_url = '';
	/**
	 * Class constructor
	 *
	 * @param string $url The feed url
	 */
	public function __construct($url)
	{
		$this->_url = $url;
	}
	/**
	 * Search the index and retieve a result set. Example queries:
	 * 
	 * http://api.collectionsbase.org.uk/os/?query=lamp&fq=have_thumbnail:1&fq=dc.subject:light&fq=dcterms.spatial:cyprus&queryType=lucene&facet=on
	 * I.e.
	 * facet=on  [turns facets on]
	 * fq=dcterms.spatial:cyprus   [filters the results just by cyprus
	 * dcterms.spatial]
	 * fq=have_thumbnail:1 [only those with images]
	 *
	 * @param string $query The raw query string
	 * @param integer $startPage (optional) The start page of results to return
	 * @param integer $itemsPerPage (optional) Max number of records per result page
	 * @param array $facetsOn (optional) Whether to include facets
	 * @param array $facetsMin (optional) The min count for a facet to be included
	 * @param array $facetFilters (optional) Associative array of facetName=>term filters
	 * @param boolean $imagesOnly (optional) only return items have_thumbnail
	 * @param string $sortField (optional) field to order by
	 * @param string $partnerCodeFilter (optional) filter by partner code
	 * @param string $projectCodeFilter (optional) filter by project code
	 * @param string $recordTypeFilter (optional) on of Museum | Archive
	 * @param string $georss (optional) whether to return as GEORSS or normal RSS
	 */
	public function listRecords($query,$startPage=1,$itemsPerPage=10,$facetsOn=false,$facetsMin=10,$facetFilters=null,$imagesOnly=0,$sortField=null,$partnerCodeFilter='',$projectCodeFilter='',$recordTypeFilter='',$georss=false,$defaultoperator='OR')
	{		
		$query = str_replace('\"','"',$query);
		$this->query = $query;
		$this->itemsPerPage = $itemsPerPage;
		$this->startPage = $startPage;
		
		$facetFilter = '';
		$imagesOnlyFilter = '';
		
		//$encodedQuery = $query=='*:*' ? $query : urlencode($query);
		
		if(/*preg_match('/^[\w\d_]+$/',$query) || preg_match('/^dcterms\.relation\:[_\-\w]+$/',$query) || */preg_match('/^dc\.identifier\:[_\-\w]+$/',$query) && !isset($_GET['cb_wildcardoff']))
		{
			$encodedQuery = $query=='*:*' ? $query : $query.'*';
		}
		else 
		{
			$encodedQuery = $query=='*:*' ? $query : urlencode($query);	
		}
		
		$url = $this->_url.'?q.op='.$defaultoperator.'&queryType=lucene&startPage='.$startPage.'&q='.$encodedQuery.'&count='.$itemsPerPage;
				
		$i = 0;
		// Facet filter
		if(is_array($facetFilters))// && $facetsOn==true)
		{
			foreach($facetFilters as $facetName=>$facetTerms)
			{
				//if($facetName!='partner')
				$facetName = str_replace('_facet','',$facetName);
				foreach($facetTerms as $k=>$v)
				{
					//$v = str_replace(' ','\ ')
					$v = stripslashes($v);
					$url.= '&'.rawurlencode('fq['.$i++.']').'='.$facetName.':'.rawurlencode('"'.$v.'"');
				}
			}
		}
		// With images filter
		if($imagesOnly)
		{
			$url.= '&'.rawurlencode('fq[]').'=have_thumbnail:1';
		}
		if($recordTypeFilter==CB_OpenSearch::MUSEUM || $recordTypeFilter==CB_OpenSearch::ARCHIVE)
		{
			$url.= '&'.rawurlencode('fq[]').'=type:'.$recordTypeFilter;
		}
		// Partner code filter
		if($partnerCodeFilter)
		{
			//$url.= '&'.rawurlencode('fq[]').'=dc.source:'.$partnerCodeFilter;
			
			
			$url.= '&'.rawurlencode('fq[]').'='.rawurlencode('(dc.source:'.implode(' OR dc.source:',explode(',',$partnerCodeFilter)).')');
		}
		// Project code filter
		if($projectCodeFilter)
		{
			$url.= '&'.rawurlencode('fq[]').'=dcterms.isPartOf:'.$projectCodeFilter;
		}
		// With images filter
		if($sortField)
		{
			$url.= '&sort=s_'.$sortField.'%20asc';
		}
		
		// Display Facets
		if($facetsOn) 
		{
			$url.= '&facet=on&facet.mincount='.$facetsMin;
		}
		
		if($georss)
		{
			$url.= '&format=georss';
		}
	

		$this->submit($url);
		
	}
	/**
	 * Get the facet_fields
	 *
	 * @return array
	 */
	public function getFacets()
	{
		$return = array();
		if($this->response->getElementsByTagName('facet_fields'))
		{
			if($facetFields = $this->response->getElementsByTagName('facet_fields')->item(0)->childNodes)
			{
				foreach($facetFields as $facetFieldNode)
				{
					$field = $facetFieldNode->nodeName;
					foreach($facetFieldNode->childNodes as $facetNode)
					{
						$return[$field][$facetNode->getAttribute('name')] = $facetNode->nodeValue;
					}
				}
			}
		}
		//print_r($return);
		return $return;
	}
	/**
	 * Return the items as an associative array
	 *
	 * @return array
	 */
	public function getItems()
	{		
		$items = array();
		$itemNodes = $this->response->getElementsByTagName('item');
		foreach($itemNodes as $itemNode)
		{			
			$items[] = $this->nodeToArray($itemNode);
		}
		return $items;
	}
	protected function nodeToArray($node)
	{		
		if(@$node->firstChild->nodeType==XML_TEXT_NODE)
		{		
			return $node->firstChild->nodeValue;
		}		
		else
		{
			foreach($node->childNodes as $child)
			{
				$nodeName = $child->nodeName;
				if(isset($arr[$nodeName]))
				{
					if(!is_array($arr[$nodeName]))
					{
						$arr[$nodeName] = array($arr[$nodeName]);
					}
					$arr[$nodeName][] = $this->nodeToArray($child);
				}
				else
				{
					$arr[$nodeName] = $this->nodeToArray($child);
				}
			}
		}		
	  	return @$arr;
	} 
	/**
	 * Return the total number of records found (unlimitted)
	 *
	 * @return integer
	 */
	public function totalRecords()
	{
		if($this->response->getElementsByTagNameNS(CB_OpenSearch::NAMESPACE_OS,'totalResults'))
		{
			return intval($this->response->getElementsByTagNameNS(CB_OpenSearch::NAMESPACE_OS,'totalResults')->item(0)->nodeValue);
		}
	}
}
?>