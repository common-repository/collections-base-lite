<?php
require_once('CB_OpenSearch.php');

class CB_GeoSearch extends CB_OpenSearch 
{
	/**
	 * Class constructor
	 *
	 * @param string $url
	 */
	public function __construct($url)
	{
		parent::__construct($url);
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
			$url.= '&'.rawurlencode('fq[]').'=dc.source:'.$partnerCodeFilter;
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
}
?>