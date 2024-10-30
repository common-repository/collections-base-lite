<?php
/*
wp_enqueue_script('jquery');
wp_enqueue_script('jstree1',CB_PLUGIN_URL.'js/jquery-jstree/_lib/jquery.cookie.js');
wp_enqueue_script('jstree2',CB_PLUGIN_URL.'js/jquery-jstree/_lib/jquery.hotkeys.js');
wp_enqueue_script('jstree3',CB_PLUGIN_URL.'js/jquery-jstree/jquery.jstree.js');
*/

class CB_SearchFacetsWidget extends WP_Widget 
{
	private static $_DEFAULT_LABELS = array('dc.subject'=>'Subject','dcterms.temporal'=>'Times','dcterms.spatial'=>'Places','dc.creator'=>'Creator','dcterms.format'=>'Format','partner_code'=>'Partner');	
	private static $_DEFAULT_TITLE = 'Narrow Your Search';
	/**
	 * Class constructor 
	 */
	public function CB_SearchFacetsWidget() 
	{
		$widget_ops = array('description' => 'Add a facet tree display for your search' );
		parent::WP_Widget(false, 'Collections Base Facet Tree',$widget_ops);	
	}	
	/**
	 * @see WP_Widget::widget 
	 */
    public function widget($args, $instance)
	{	
		global $__CB,$wp_query;
		
		//if((/*cb_is_facet_search() ||*/ cb_is_image_browse() || !is_a($__CB_SearchResults,'CB_OpenSearch')) && !is_search())
		//{
		//	return;
		//}
		
		$remove_filter_title = 'Click to remove filter';
		
		
		if(is_search() && isset($__CB['search_results']))
		{
			
			extract( $args );
			$title = apply_filters('widget_title', $instance['title']);
			echo $before_widget;
			
			if ( $title )
			{
				echo $before_title . $title . $after_title; 
			}
			echo '<div class="cb_facet_tree">';
			
            // Make headers expandible/collapsable [issue:138(3)]
            if(!IS_MOBILE)
            {
                echo '<script type="text/javascript">function cb_facets_toggle(li){jQuery(li).find(\'ul\').slideToggle(function(){jQuery(li).find(\'.cb_facet_expander\').toggleClass(\'open\');jQuery(li).find(\'.cb_facet_expander\').toggleClass(\'closed\');});}</script>';
                echo '<style type="text/css">.cb_facet_expander{background-position:0px 3px;background-repeat:no-repeat;padding-left:13px;}.cb_facet_expander.open{background-image:url("/wp-content/plugins/collapsing-pages/img/collapse.gif");}.cb_facet_expander.closed{background-image:url("/wp-content/plugins/collapsing-pages/img/expand.gif");}</style>';
            }
			
			$basepath = getenv('REQUEST_URI');
			if(!strstr($basepath,'?')) $basepath.= '?';
			$basepath = preg_replace('/(\/page\/\d+\/)/','',$basepath);
			
			
			// Add the ordering options
            echo '<ul><li><span></span><a class="nolink" href="#null">Sort by:</a><ul><li><span></span><a href="'.preg_replace('/&cb_sort=\w+/','',$basepath).'&cb_sort=relevance">Relevance</a></li><li><span></span><a href="'.preg_replace('/&cb_sort=\w+/','',$basepath).'&cb_sort=title">Title</a></li><li><span></span><a href="'.preg_replace('/&cb_sort=\w+/','',$basepath).'&cb_sort=date">Date</a></li></ul></li></ul></div><div class="cb_facet_tree">';
			
			
			// Add the Document types filters
			echo '<ul>';
            echo '<li><span></span><a class="nolink" href="#null">Results from</a>';
			echo cb_get_post_types_filter_links();
			echo '</li>';
            echo '<li><span></span><a class="nolink" href="#null">Results with</a>';
			 
			
			$selected = @$_GET['cbim']==1;
			$removePath = str_replace('&cbim=1','',$basepath);
            echo '<ul><li class="'.($selected?' cb-selected':'').'"><span></span><a href="'.($selected?$removePath:$basepath.'&cbim=1').'" class="'.($selected?'selected':'').'">Image Only</a>'.($selected?'<a class="removefac" href="'.$removePath.'" title="'.$remove_filter_title.'"><img src="/wp-content/plugins/collections-base/img/ico-facet-remove.gif"  align="right" style="margin:5px;"/></a>':'').'</li></ul>';
	
			echo '</li>';
		
		
			/**
			 * Draw the facet display
			 */
			if(@$_GET['cbpt']!=1)
			{
				
				if($items = $__CB['search_results']->getFacets())
				{
					
					
					
					$partnercode_mapping = cb_partnercodes_to_names();
					
					
					
					
					
					// Get the partner facet to the top of the list [issue:138(1)]
					$new_items['partner_code_facet'] = $items['partner_code_facet'];
					unset($items['partner_code_facet']);
					foreach ($items as $k=>$v)
						$new_items[$k] = $v;
					$items = $new_items;
					
					
					if(@$_GET['debug_facets']) print_r($items);
					
					//echo '<ul>';
					
					// Capitalise facets that are all CAPS or all lower
					$new_items = array();
					foreach($items as $field=>$facets)
					{
						foreach($facets as $k=>$v)
						{
							if(!preg_match('/([a-z])/',$k) || !preg_match('/([A-Z])/',$k))
							{
								$k = ucwords(strtolower($k));
							}
							$new_items[$field][$k] = $v;
						}
					}
					$items = $new_items;
					
					
					foreach($items as $field=>$facets)
					{
						
						ksort($facets);
							
						$field = str_replace(array(':','_facet'),array('.',''),$field);
						
						$facetsOn = array();
						if(isset($_GET['fq'][$field]))
						{
							foreach($_GET['fq'][$field] as $f=>$v)
							{
								$facetsOn[] = stripslashes_deep($v);
							}
						}
						
						//print_r($facetsOn);
						
						
						//echo getenv('REQUEST_URI');						
						//echo getenv('REQUEST_URI').strstr(getenv('REQUEST_URI'),'fq['.$field.']')?'open':'closed';
						
						//$basepath = getenv('REQUEST_URI');
						//if(!strstr($basepath,'?')) $basepath.= '?';
						
                        echo '<li class="'.(count($facetsOn)>0?'open':'closed').'"><span class="cb_facet_expander open" style="cursor:pointer;" onclick="cb_facets_toggle(this.parentNode)"></span><a class="nolink" href="#null"><span class="inner">'.$instance['facet_label_'.$field].'</span></a><ul>';
						foreach($facets as $name=>$count)
						{			
						
							// Hack to ignore facets where the {xxx} template placeholder has found it's way into the data
							if(substr($name,0,1)=='{')
								continue;
							
							
							// Ignore purely numeric facets - typically LonLats or EastNorths
							if(is_numeric(str_replace(' ','',$name)))
								continue;	
												
							if(@in_array($name,$facetsOn))
							{
								// Facet is selected, so remove it from the uri
								$url = parse_url($basepath);
								$exploded = explode('&',$url['query']);
								foreach($exploded as $k=>$v)
								{
									if(urldecode($v)=='fq['.$field.'][0]'.'='.$name || urldecode($v)=='fq['.$field.'][]'.'='.$name)
									{
										unset($exploded[$k]);
									}
								}
								$link = $url['path'].'?'.implode('&amp;',$exploded);				
								
								
								
								$class = 'cb-selected';
							}
							else 
							{
								// Facet not selected, so append to the uri
								$class = '';
								$link = $basepath.'&amp;'.rawurlencode("fq[$field][]").'='.rawurlencode($name);
							}
							
							
							
							//print_r($partnercode_mapping);
							
							if($field=='partner_code')
							{
								if(isset($partnercode_mapping[strtolower($name)]))
								{
									$name = $partnercode_mapping[strtolower($name)];
								}
							}
							
							$name = str_replace('&amp;amp;','&amp;',$name);
																			
							$url = parse_url($basepath);					
							
							if(stristr($url['path'],"/$field/".rawurlencode($name)))
							{
								// This facet is part of the permalink structure (e.g. /collections/search/dc.subject/Natural%20history)
								//echo '<li class="'.$class.' disabled" title="'.__('Use the search box above to perform a new search').'">'.ucwords(strtolower($name)).' ('.$count.')';
                                echo '<li class="'.$class.' disabled" title="'.__('Use the search box above to perform a new search').'"><span></span>'.$name.' ('.$count.')';
							}
							else 
							{
								//echo '<li class="'.$class.'"><a href="'.$link.'">'.ucwords(strtolower($name)).'</a> ('.$count.')'.($class=='selected'?'<a href="'.$link.'"><img src="/wp-content/plugins/collections-base/img/ico-facet-remove.gif" align="absmiddle"/></a>':'');
                                echo '<li class="'.$class.'"><span></span><a href="'.$link.'">'.$name.' ('.$count.')</a>'.($class=='cb-selected'?'<a class="removefac" href="'.$link.'" title="'.$remove_filter_title.'"><img src="/wp-content/plugins/collections-base/img/ico-facet-remove.gif" align="right" style="margin:5px;"/></a>':'');
							}
							echo '</li>';
						}
						echo '</ul></li>';
					}
				}
				//echo '</ul>';
				
					
			}
			echo '</ul></div>';
			
			//echo '<script type="text/javascript">jQuery(".cb_facet_tree").jstree({"plugins" : ["themes","html_data","ui","crrm","hotkeys"]});</script>';
			
					
			echo $after_widget;
		}
	}	
	/**
	 * @see WP_Widget::update 
	 */
	public function update($new_instance, $old_instance) 
	{
		/*
		global $__CB_DefaultFacetLabels;		
		ob_start();
		$labels = get_option('CB_FACET_DISPLAY_NAMES');
		$newFacetsLabels = array();
		foreach($labels as $f=>$v)
		{
			// Set the options
			$newFacetsLabels[$f] = $new_instance["facet_label_$f"];
		}
		print_r($newFacetsLabels);
		print_r($_POST);
		print_r($_GET);
		print_r($new_instance);
		print_r($old_instance);
		//file_put_contents(dirname(__FILE__).'/debug.txt',ob_get_clean());
		
		//update_option('CB_FACET_DISPLAY_NAMES',$newFacetsLabels);
		*/
		
		return $new_instance;
	}	
	/** 
	 * @see WP_Widget::form 
	 */
	public function form($instance) 
	{
		global $__CB_DefaultFacetLabels;
		$labels = CB_SearchFacetsWidget::$_DEFAULT_LABELS;
		$title = esc_attr((@$instance['title']?@$instance['title']:CB_SearchFacetsWidget::$_DEFAULT_TITLE));
		echo '<p>';
		echo '<label for="'.$this->get_field_id("title").'">'._('Heading').'
				<input class="widefat" id="'.$this->get_field_id("title").'" name="'.$this->get_field_name("title").'" type="text" value="'.$title.'" />
			</label>';
		foreach($labels as $f=>$v)
		{
			echo '<label for="'.$this->get_field_id("facet_label_$f").'">'._('Label for ').'<em>'.$f.'</em>
				<input class="widefat" id="'.$this->get_field_id("facet_label_$f").'" name="'.$this->get_field_name("facet_label_$f").'" type="text" value="'.(@$instance["facet_label_$f"]?$instance["facet_label_$f"]:$v).'" />
			</label>';
		}
		echo '</p>';
	}
}
?>
