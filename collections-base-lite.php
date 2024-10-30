<?php
/*
	Plugin Name: Collections Base Lite
	Plugin URI: http://www.orangeleaf.com
	Description: Search a over 2 million museum and archive records from the Orangeleaf Systems Ltd Collections-Base repository and display them on your website.
	Version: 1.1.3
	Author: Mark Oliver Brawn, Orangeleaf Systems Ltd
	Author URI: http://www.orangeleaf.com
	License: GPL2
*/
/* 
	Copyright 2012  Orangeleaf Systems Ltd (email : info@orangeleaf.com)

	This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License, 
	version 2, as published by the Free Software Foundation. This program is distributed in the hope that it will be useful, 
	but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See 
	the GNU General Public License for more details. You should have received a copy of the GNU General Public License along 
	with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  
	02110-1301  USA
*/

/** 
 * PHP version 5
 * 
 * @category Wordpress
 * @package  Cblite
 * @author   Mark Oliver Brawn <mark@orangeleaf.com>
 * @license  GPL2 http://www.gnu.org/licenses/gpl-2.0.html
 * @version  SVN: 609564
 * @link     http://www.orangeleaf.com
 */

// Global defines for the plugin
define('CBLITE_ITEMS_PER_PAGE', 10);
define('CBLITE_LISTING_TITLE_MAX_LENGTH', 100);
define('CBLITE_GETRECORD_MAX_TEXTNODE_LENGTH', 255);
define('CBLITE_PARTNERPROJECT_CODE_REQUIRED', false);

// Sanitise submitted variables
$_GET['cbq']   = @strval($_GET['cbq']);
$_GET['cbp']   = @intval($_GET['cbp']);
$_GET['cblid'] = @strval($_GET['cblid']);

/**
 * Add the help page to the admin menu
 *
 * @return void
 */
function Cblite_helpPageAdd() 
{
    add_menu_page('Collections Base Lite Help', 'Collections Base Lite Help', 5, __FILE__, 'Cblite_helpPage');
}
/**
 * Draw the help page in the admin: This page contains some example usage of the plugin 
 * shortcode and the current list of supported partner/project codes
 *
 * @return void
 */
function Cblite_helpPage() 
{
    echo '<style type="text/css">table.cblite td, table.cblite th{border:1px solid #d0d0d0;font-size:0.9em;padding:2px 5px}table.cblite tr:nth-child(odd){background:#f0f0f0;}table.cblite tr:nth-child(even){background:#f9f9f9;}table.cblite td:last-child{text-align:right;}.cblite-scroller{max-height:250px;overflow:auto;}</style><div class="wrap"><h2>Collections Base Lite</h2><h3>Useage</h3><p>Place the following <a href="http://codex.wordpress.org/Shortcode" target="_blank">shortcode</a> on any page: <span style="background:#ffffcc;border:1px dotted silver;display:block;font-size:1.7em!important;margin:10px 0px;padding:10px;text-align:center;width:200px">[collectionsbase_lite]</span></p><h4>Options</h4><p>You can optionally add one or more of the following <a href="http://codex.wordpress.org/Shortcode" target="_blank">shortcode-attributes</a> to refine the display:</p><p><code>project="XXX"</code> Specify the partner code to filter for only records from that organisation</p><p><code>partner="YYY"</code> Specify the project code to filter for only records from that project</p><p><code>images="true"</code> Add images="true" to only show records with at least one image</p><p><code>query="some-text"</code> Prepopulate the page with a query results.</p><h4>Examples</h4><p><code>[collectionsbase_lite project="BCH"]</code> will filter for only <i>Black Country</i> records.</p><p><code>[collectionsbase_lite partner="WAGMU"]</code> will filter for only <i>Wolverhampton Arts and Museums</i> records.</p><p><code>[collectionsbase_lite images="true"]</code> will filter for only records with <i>images</i> records.</p><p><code>[collectionsbase_lite project="BCH" images="true"]</code> will filter for only <i>Black Country</i> records with <i>images</i>.</p><p><code>[collectionsbase_lite partner="WAGMU" images="true" query="painting"]</code> will automatically run a search for <b>painting</b> from <i>Wolverhampton Arts and Museums</i> that have <i>images</i>.</p>';
    $files = array('projects', 'partners');
    foreach($files as $file)
    {
    	$row = 1;
   		$display = '';
		if (($handle = fopen(plugin_dir_path(__FILE__).'/'.$file.'.csv', "r")) !== false)
		{
			$display.= '<div class="cblite-scroller"><table class="cblite">';
			while (($data = fgetcsv($handle, 1000, ", ")) !== false)
			{			
				$tag = $row==1 ? 'th' : 'td';
				$num = count($data);
				$row++;
				$display.= '<tr>';
				for ($c=0; $c < $num; $c++)
				{
					$display.= "<$tag>{$data[$c]}</$tag>";
				}
				$display.= '</tr>';
			}
			$display.= '</table></div>';
			
			echo '<h3>Current '.ucfirst($file).' ('.$row.')</h3>'.$display;
			
			fclose($handle);
		}
    }    
    echo '</div>';
}
add_action('admin_menu', 'Cblite_helpPageAdd');
/**
 * Get the name of a partner from their code
 *
 * @return array
 */
function Cblite_partnerCodesToNames()
{	
	if(!isset($_SESSION['partner_codes']))
	{
		$s = new CB_InstitutionSearch(cb_get_option('feedurl').'/os/');
		$s->listRecords(cb_get_option('projectcode'));	
		$items = $s->getItems();
		
		foreach($items as $item)
		{
			$_SESSION['partner_codes'][strtolower($item['prefix'])] = $item['name'];
		}
	}
	return @$_SESSION['partner_codes'];
}
/**
 * Emulate getting a random object from the repository. There is no ORDER BY RAND() or such in SOLR
 * so emulate this by getting the first page of results, then running again and selecting a random
 * page based on the totalRecords available
 *
 * @param string $query   A query string to use for the initial solor search
 * @param array  $filters An array of filters for the initial search
 * 
 * @return string
 */
function Cblite_getRandomIdentifier($query='', $filters=null)
{
	$s = Cblite_search($query, $filters, 1, 1);
	$total = $s->totalRecords();
	$record = rand(0, $total);
	$page = ceil($record/CBLITE_ITEMS_PER_PAGE);
	$s = Cblite_search($query, $filters, $page);	
	
	$items = $s->getItems();
	return (is_array($items[0]['dcterms:identifier']) ? $items[0]['dcterms:identifier'][0] : $items[0]['dcterms:identifier']);
}
/**
 * Draw the getrecord display.
 *
 * @param string $identifier The unique identifier of the record
 * 
 * @return string
 */
function Cblite_getRecord($identifier)
{
	if(!class_exists('CB_OAISearch'))
	{
		include_once plugin_dir_path(__FILE__).'/cls/CB_OAISearch.php';
	}
	
	// Add the styling and scripts
	wp_enqueue_style('cblite-tree', plugin_dir_url(__FILE__).'/js/treeview/jquery.treeview.css');
	wp_enqueue_script('jquery');
	wp_enqueue_script('cblite', plugin_dir_url(__FILE__).'/js/cblite.js');
	wp_enqueue_script('cblite-treeview', plugin_dir_url(__FILE__).'/js/treeview/jquery.treeview.js');
	
	// Set the base URL
	$url = parse_url($_SERVER['REQUEST_URI']);
	$url = $url['path'];
	
	// Perform the get-record search
	$s = new CB_OAISearch('http://api.collectionsbase.org.uk/oai/');
	$s->getRecord($identifier, CB_OAISearch::PREFIX_OAI_SPECTRUM);
	
	// Start the rendering
	$display = '<div class="cblite-getrecord">';
	$display.= '<a href="javascript:history.go(-1)" title="back" style="display:inline;float:right;">Back</a>';
	$display.= '<h2 style="clear:left;">'.Cblite_getRecordParseNodeDrawTextNode($s->getTitle()).'</h2>';
	
	// Get the images and draw slide-gallery
	if($images = $s->getImages())
	{
		$pathinfo = pathinfo($images[0]);
		$display.= '<div class="cblite-slides">';
		$display.= '<img src="'.$pathinfo['dirname'].'/300_'.$pathinfo['basename'].'" class="cblite-main"/>';
		if(count($images)>1)
		{
			foreach($images as $k=>$image)
			{						
				$pathinfo = pathinfo($image);
				$display.= '<img src="'.$pathinfo['dirname'].'/75_'.$pathinfo['basename'].'" class="cblite-thumb'.($k==0?' cblite-selected':'').'" onclick="cblite_select_image(this)"/>';
			}
		}
		$display.= '<div style="clear:both;"></div></div>';
	}
	
	// Who owns the record
	//$partners = Cblite_partnerCodesToNames();
	
	// Parse the data
	$xpath = new DOMXPath($s->response);
	$xpath->registerNamespace('oai', 'http://www.openarchives.org/OAI/2.0/');
	$xpath->registerNamespace('spectrum', 'http://www.collectionstrust.org.uk/spectrumXML/Schema-v3.1'); 		
	
	
	$result = $xpath->query('//*/spectrum:Metadata/spectrum:AmendmentHistory/spectrum:Change/spectrum:Source/spectrum:Organisation/spectrum:Name');
	if($result->length > 0)
	{
		foreach($result as $item)
		{			
			$display.= '<p class="cblite-rightsholder">'.$item->nodeValue.'</p>';
		}
	}
	
	// The description
	$display.= '<p class="cblite-description">'.nl2br($s->getDescription()).'</p>';	
	
	
	// Draw `keywords`
	$result = $xpath->query('//*/spectrum:Interchange/spectrum:Record/spectrum:Data/spectrum:Object/spectrum:Description/spectrum:Content/spectrum:Keyword');
	if($result->length > 0)
	{
		foreach($result as $keyword)
		{
			$keywords[] = '<a href="'.$url.'?cbq='.urlencode($keyword->nodeValue).'">'.$keyword->nodeValue.'</a>';
		}
		$display.= '<h3>Keywords</h3><p class="cblite-keywords">'.implode(' | ', $keywords).'</p>';
	}
	
	// Draw the record explorer
	$result = $xpath->query('//*/spectrum:Interchange/spectrum:Record/spectrum:Data/spectrum:Object');
	if($result->length > 0)
	{
		$display.= '<h3>Record Explorer</h3><ul class="filetree cblite-tree">'.Cblite_getRecordParseNode($result->item(0)).'</ul>';
	}
	
	$display.= '</div>';
	
	return $display;
}
/**
 * Callback function to parse an XML node into an HTML display
 *
 * @param XMLNode $node The node to parse
 * @param string  $path The current path (used for creating css classnames)
 * 
 * @return string
 */
function Cblite_getRecordParseNode($node, $path='')
{		
	$display = '';
	$name = str_replace('spectrum:', '', $node->nodeName);
	$classname = trim($path.' cblite_'.$name);
	
	if(@$node->firstChild->nodeType==XML_TEXT_NODE)
	{		
		if(trim($node->firstChild->nodeValue)!='')
		{
			$display.= '<li class="'.$classname.'"><span class="file cblite-label">'.$name.'</span> <span class="cblite-value">'.Cblite_getRecordParseNodeDrawTextNode(nl2br($node->firstChild->nodeValue)).'</span></li>';
		}
	}
	else
	{
		foreach($node->childNodes as $child)
		{
			$display.= Cblite_getRecordParseNode($child, $classname);
		}
				
		if($display)
		{
			if($node->nodeName!='spectrum:Object')
			{
				$display = '<li class="'.$classname.'"><span class="folder cblite-label">'.$name.'</span> <ul>'.$display.'</ul></li>';
			}
		}
	}
  	return $display;
}
/**
 * Draw a text node and (if required) do a more/less collapser
 *
 * @param string $s The text to render
 * 
 * @return string
 */
function Cblite_getRecordParseNodeDrawTextNode($s)
{
	if(strlen($s) > CBLITE_GETRECORD_MAX_TEXTNODE_LENGTH+50)
	{
		$s = '<span>'.substr($s, 0, CBLITE_GETRECORD_MAX_TEXTNODE_LENGTH).'</span><span style="display:none;">'.substr($s, CBLITE_GETRECORD_MAX_TEXTNODE_LENGTH).'</span> <a href="javascript:void(0)" onclick="cblite_toggle_text_node(this)">&raquo;</a>';
	}
	return $s;
}
/**
 * Draw and return the search form
 *
 * @param string $query The search query string
 * 
 * @return string
 */
function Cblite_getSearchform($query='')
{
	return '<form class="cblite-form" action="'.$_SERVER['REQUEST_URI'].'" method="get"><fieldset><legend>Search the Collections</legend><label class="assistive-text" for="cbq" style="display:none;">Search</label><input class="field" id="cbq" type="text" name="cbq" value="'.$query.'" title="Enter something to search form" placeholder="Search..." style="float:left;margin-right:5px;"/><input class="submit" type="submit" value="Submit"/><div style="clear:both;"></div></fieldset></form>';
}
/**
 * Get the search results for a given query
 *
 * @param string $query   The query string
 * @param array  $filters An array of search filters
 * 
 * @return string
 */
function Cblite_getSearchresults($query, $filters)
{
	
	$url = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));
	$page = $_GET['cbp'] > 0 ? $_GET['cbp'] : 1;
	
	$s = Cblite_search($query, $filters, $page);
	$items = $s->getItems();
	
	$display = '<h2>Search Results ('.$s->totalRecords().' items found)</h2>';
	$display.= '<!--'.$query.'-->';	
	
	foreach($items as $item)
	{
		$link = $url.'?cblid='.(is_array($item['dcterms:identifier']) ? $item['dcterms:identifier'][0] : $item['dcterms:identifier']);
		
		$thumb = '';
		if(isset($item['image']['url']))
		{
			$thumb = '<a href="'.$link.'"><img src="'.$item['image']['url'].'"/></a>';
		}		
		
		$display.= '
		<div class="cblite-result-row">
			'.$thumb.'
			<h3 style="clear:none;margin-bottom:0px;"><a href="'.$link.'">'.Cblite_getSearchresultsSensibleTextLength($item['title']).'</a></h3>
			<p>
				'.($item['dcterms:temporal'] ? '<span class="cblite-date">'.$item['dcterms:temporal'].'</span>' : '').'
				<span class="cblite-description">'.$item['description'].'</span>
				<span class="cblite-rightsholder">'.$item['dcterms:rightsHolder'].'</span>
			</p>
			<div style="clear:both;"></div>
		</div>
		<div style="clear:both;"></div>';
	}
	$display.= Cblite_getSearchresultsPaging($query, $page, $s->totalRecords());
	
	return $display;
}
/**
 * Get the paging display for the search results
 *
 * @param string  $query        The query string
 * @param integer $currentpage  The current page we're on
 * @param integer $totalrecords The total records in the set
 * 
 * @return string
 */
function Cblite_getSearchresultsPaging($query, $currentpage, $totalrecords)
{	
	
	$display = array();
	$url = parse_url(preg_replace('/\bcbp=\d+\b/', '', $_SERVER['REQUEST_URI']));
	$url = $url['path'].'?cbq='.$query;
	
	$arrPages = false;
	$totalpages = ceil($totalrecords/CBLITE_ITEMS_PER_PAGE);
	$startpage = $currentpage;
	if($currentpage < 0)
	{
		$currentpage = 1;
	}
	elseif($currentpage > $totalpages)
	{
		$intCurrentPage = $totalpages;
	}
	
	if($totalpages > 10)
	{
		if($currentpage - 5 > 0)
		{
			$startpage = $currentpage-5;
		}
	}
	else 
	{
		$startpage = 1;
	}
	
	if($startpage > 1)
	{
		$display[] = '<a href="'.$url.'&cbp='.($currentpage-1).'">&laquo; prev</a>';
	}
	
	for($i=$startpage;$i < $totalpages;$i++)
	{
		if($i==$currentpage)
		{
			$display[] = $i;
		}
		else
		{
			$display[] = '<a href="'.$url.'&cbp='.$i.'">'.$i.'</a>';
		}
		//only show 10 selectors at a time
		if($i>=$startpage+9)
		{					
			$i++;
			$display[] = '<a href="'.$url.'&cbp='.$i.'">next &raquo;</a>';
			$i = $totalpages;
		}
	}	
	return implode(' | ', $display);
}
/**
 * Truncate insanely long titles and add a title attribute
 *
 * @param string $s The string to process
 * 
 * @return string
 */
function Cblite_getSearchresultsSensibleTextLength($s)
{
	if(strlen($s)>CBLITE_LISTING_TITLE_MAX_LENGTH)
	{
		$s = '<span title="'.htmlspecialchars($s).'">'.substr($s, 0, CBLITE_LISTING_TITLE_MAX_LENGTH).'...</span>';
	}
	return $s;
}
/**
 * Handle the CB-lite shortcode:
 * Add in the search form and the getrecord display if one is specified, or the search results if a query has been submitted
 *
 * @param array  $atts    An array of settings
 * @param string $content The content to be processed
 * 
 * @return string
 */
function Cblite_shortcodeHandler( $atts, $content = null ) 
{	
	global $post;
	
	$filters = array();
	$query_filter = '';
		
	extract(
		shortcode_atts(
			array(
      			'images' => false, 
      			'partner' => '', 
      			'project' => '', 
      			'randomobject' => false, 
      			'query' => ''
      		), 
      		$atts 
      	)
	);
	
	if(!$query)
	{
		$query = $_GET['cbq'];
	}
	else
	{
		if($_GET['cbq'])
		{
			$query = $_GET['cbq'];
		}
	}
	$_GET['cbq'] = $query;
	
	// Check for minimum attributes (if required)
	if(CBLITE_PARTNERPROJECT_CODE_REQUIRED && $partner=='' && $project=='')
	{
		$content = 'You must specify a partner code or a project code!';
	}
	else 
	{	
		// Add the core styling
		wp_enqueue_style('cblite', plugin_dir_url(__FILE__).'/css/styles.css');

		// Add the filters
		if($partner)
		{
			$filters[] = '(partner_code:'.$partner.')';
		}
		if($project)
		{
			$filters[] = '(dcterms.isPartOf:'.$project.')';
		}
		if($images==='true')
		{
			$filters[] = '(have_thumbnail:1)';
		}			
		
		
		
		if($randomobject==='true')
		{
			$_GET['cblid'] = Cblite_getRandomIdentifier($query, $filters);
		}
		
		
		if($_GET['cblid'])
		{
			// Get record
			$content = Cblite_getRecord($_GET['cblid']);
		}
		elseif($query)
		{	
			// Search results
			$content = Cblite_getSearchform($query);
			$content.= Cblite_getSearchresults($query, $filters);
		}
		else 
		{
			// Search form (no query submitted)
			$content = Cblite_getSearchform($query);
		}
		
		
	}
	
	return do_shortcode($content);
}
add_shortcode('collectionsbase_lite', 'Cblite_shortcodeHandler');
/**
 * Perform a search on the repository
 *
 * @param string  $query   The query string
 * @param array   $filters An array of filters (actually extra parameters that are AND'ed to the query
 * @param integer $page    The page to fetch
 * @param integer $num     The items-per-page
 * 
 * @return CB_OpenSearch
 */
function Cblite_search($query='*:*', $filters='', $page=1, $num=CBLITE_ITEMS_PER_PAGE)
{
	if(!class_exists('CB_OpenSearch'))
	{
		include_once plugin_dir_path(__FILE__).'/cls/CB_OpenSearch.php';
	}
	
	if(empty($query))
	{
		$query = '*:*';
	}
	
	$s = new CB_OpenSearch('http://api.collectionsbase.org.uk/os');
	$s->listRecords("($query)".(!empty($filters) ? ' AND ('.implode(' AND ', $filters).')' : ''), $page, $num);
	
	return $s;
}
/**
 * Clear the post content if cblite search parameters have been passed
 *
 * @param string $content The content string to be processed
 * 
 * @return string
 */
function Cblite_theContent($content)
{
	if(in_the_loop() && ($_GET['cblid'] || $_GET['cbq']))
	{
		// Remove everything but (the first) cblite shortcode
		if(preg_match('/(\[collectionsbase_lite.*[^\]]\])/', $content, $m))
		{
			$content = $m[1];
		}
	}
	return $content;
}
add_filter('the_content', 'Cblite_theContent');
?>