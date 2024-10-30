<?php
require_once(dirname(__FILE__).'/CB_Search.php');
/**
 * A wrapper for the OAI search interface
 *
 * @author Mark Oliver Brawn
 * @copyright Orangeleaf Systems Ltd
 * @version 1.0.0
 */
class CB_OAISearch extends CB_Search
{
	/**
	 * The host for images
	 *
	 */
	const CDN_HOST = 'http://cdn.collectionsbase.org.uk';
	/**
	 * Result format: OAI:DC
	 *
	 */
	const PREFIX_OAI_DC = 'oai_dc';
	/**
	 * Result format: PNDS:DC
	 *
	 */
	const PREFIX_OAI_PNDS = 'pnds_dc';
	/**
	 * Result format: OAI:SPECTRUM
	 *
	 */
	const PREFIX_OAI_SPECTRUM = 'oai_spectrum';
	/**
	 * The getRecord identifier
	 *
	 * @var string
	 */
	private $_identifier = '';
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
	 * Search the repository for a record by identifier
	 *
	 * @param string $identifier
	 * @param string $metadataPrefix (optional)
	 */
	public function getRecord($identifier,$metadataPrefix=null)
	{
		$this->_identifier = $identifier;
		if($metadataPrefix==null)
		{
			$metadataPrefix = CB_OAISearch::PREFIX_OAI_DC;
		}
		$url = $this->_url.'?verb=GetRecord&metadataPrefix='.$metadataPrefix.'&identifier='.urlencode($identifier);
		
		$this->submit($url);
	}
	/**
	 * Get the identifier from the last query
	 *
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->_identifier;
	}
	/**
	 * Get the title of the object from the result
	 *
	 * @return string
	 */
	public function getDescription()
	{
		$xml = new SimpleXMLElement($this->response->saveXML());
		foreach($xml->getDocNamespaces() as $prefix=>$uri)
		{
			$xml->registerXPathNamespace($prefix,$uri);
		}
		
		$paths = array('//*/spectrum:Data/spectrum:Object/spectrum:Identification/spectrum:BriefDescription','//*/spectrum:Data/spectrum:Object/spectrum:Description/spectrum:Content/spectrum:Description');
		foreach($paths as $p)
		{
			//print($p);
			$result = $xml->xpath($p);
			while(list( , $node) = each($result)) 
			{
				$v = trim(strip_tags($node->saveXML()));
				if(!empty($v))
				{
					return $v;
				}
			}
		}
	}
	/**
	 * Get the images
	 *
	 * @return array
	 */
	public function getImages()
	{
		$xml = new SimpleXMLElement($this->response->saveXML());
		foreach($xml->getDocNamespaces() as $prefix=>$uri)
		{
			$xml->registerXPathNamespace($prefix,$uri);
		}
		
		$result = $xml->xpath('//*/spectrum:Metadata/spectrum:AmendmentHistory/spectrum:Change/spectrum:Source/spectrum:Organisation/spectrum:Reference');
		$partnercode = $result[0];
					
		
		$imgs = array();
		$paths = array('//*/spectrum:Data/spectrum:Object/spectrum:Reproduction/spectrum:Location');
		foreach($paths as $p)
		{
			//print($p);
			$result = $xml->xpath($p);
			while(list( , $node) = each($result)) 
			{
				$v = trim(strip_tags($node->saveXML()));
				if(!empty($v))
				{
					$imgs[] = CB_OAISearch::CDN_HOST .'/'.$partnercode.'/'.$v;
				}
			}
		}
		return $imgs;
	}
	/**
	 * Get the title of the object from the result
	 *
	 * @return string
	 */
	public function getTitle()
	{
		$xml = new SimpleXMLElement($this->response->saveXML());
		foreach($xml->getDocNamespaces() as $prefix=>$uri)
		{
			$xml->registerXPathNamespace($prefix,$uri);
		}
		
		$paths = array('//*/spectrum:Data/spectrum:Object/spectrum:Identification/spectrum:ObjectTitle','//*/spectrum:Data/spectrum:Object/spectrum:Identification/spectrum:ObjectName','//*/spectrum:Data/spectrum:Object/spectrum:Identification/spectrum:BriefDescription');
		foreach($paths as $p)
		{
			//print($p);
			$result = $xml->xpath($p);
			while(list( , $node) = each($result)) 
			{
				$v = trim(strip_tags($node->saveXML()));
				if(!empty($v))
				{
					return $v;
				}
			}
		}
	}
	/**
	 * List the partners
	 */
	public function getPartners()
	{
		return array(
			'WAGMU'=>'Wolverhampton Museums'
		);
	}
}
?>