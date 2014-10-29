<?php
/*
Plugin Name:	functionsCapacitor
Plugin URI:		http://wordpress.org/extend/plugins/functionscapacitor/
Description:	Back WordPress API to the content. This plugin allow to apply some WordPress API's functions into your post/page content or as a widget.
Author:			oliezekat
Version:		0.9.6
Author URI:		http://life2front.com/oliezekat
Licence:		GNU-GPL version 3 http://www.gnu.org/licenses/gpl.html
*/

class functionsCapacitor
	{
	var $supported_functions = array(
									'get_the_post_thumbnail',
									'get_the_tag_list',
									'wp_get_archives',
									'wp_get_recent_posts',
									'wp_list_authors',
									'wp_list_bookmarks',
									'wp_list_categories',
									'wp_list_pages',
									'wp_nav_menu',
									'wp_tag_cloud'
									);
	var $supported_conditions = array(
									'cat_is_ancestor_of',
									'in_category',
									'in_tree_of',
									'is_category',
									'is_category_in_tree_of',
									'is_front_page',
									'is_home',
									'is_page',
									'is_page_descendant_of',
									'is_page_in_tree_of',
									'is_single',
									'is_single_in_tree_of'
									);
	var $not_canonical_conditions = array(
									'in_tree_of',
									'is_category_in_tree_of',
									'is_page_descendant_of',
									'is_page_in_tree_of',
									'is_single_in_tree_of'
									);
									
	var $current_post;		 // To save/restore it
	var $current_context;	 // To save/restore it
	
	/* Constructor */
	
	function functionsCapacitor()
		{
		}
		
	/* Callback functions for hooks, filters, widgets, etc */
	
	// Callback plugins_loaded action
	function plugins_loaded_action()
		{
		$functionsCapacitor_class = new functionsCapacitor();
		if (is_admin())
			{
			
			}
		else
			{
			add_filter('the_content',array($functionsCapacitor_class,'the_content_filter'),5);
			add_shortcode('fct', array($functionsCapacitor_class,'fct_shortcode'));
			}
		add_action('widgets_init',create_function('','return register_widget("functionsCapacitor_widget");'));
		}
	
	// Callback for the_content filter
	function the_content_filter($content)
		{
		$this->save_current_context();
		
		// Apply only if is page/post displayed
		if (!is_single($this->current_post->ID) AND !is_page($this->current_post->ID)) return $content;
		
		$custom_fields = get_post_custom($this->current_post->ID);
		$custom_fields_content = '';
		foreach ($custom_fields as $custom_key => $custom_values)
			{
			$fct_name = trim($custom_key);
			if (in_array($fct_name,$this->supported_functions) AND function_exists($fct_name))
				{
				foreach ($custom_values as $custom_value)
					{
					$fct_args = $this->decode_function_arguments_string($custom_value);
					$custom_fields_content .= $this->function_content($fct_name,$fct_args,'content');
					}
				}
			}
			
		if ($custom_fields_content != '')
			{
			$custom_fields_content = '<div class="functionsCapacitor">'.$custom_fields_content.'</div>';
			}
		
		$this->restore_current_context();
		
		return $content.$custom_fields_content;
		}
		
	// Callback for shortcode
	function fct_shortcode($atts)
		{
		$this->save_current_context();
		
		$shortcode_content = '';
		$shortcode_container = 'div';
		$shortcode_class = ' class="functionsCapacitor"';
		$shortcode_style = '';
		foreach ($atts as $att_key => $att_value)
			{
			if (is_numeric($att_key))
				{
				$fct_name = $att_value;
				$fct_args = '';
				}
			else
				{
				$fct_name = $att_key;
				$fct_args = $this->decode_function_arguments_string($att_value);
				}
				
			if (in_array($fct_name,$this->supported_functions) AND function_exists($fct_name))
				{
				$shortcode_content .= $this->function_content($fct_name,$fct_args,'content');
				}
			else if ($fct_name == 'container')
				{
				$shortcode_container = ''.$fct_args;
				}
			else if ($fct_name == 'class')
				{
				if ($fct_args == '')
					{
					$shortcode_class = '';
					}
				else
					{
					$shortcode_class = ' class="'.$fct_args.'"';
					}
				}
			else if ($fct_name == 'style')
				{
				$shortcode_style = ' style="'.$fct_args.'"';
				}
			}
			
		if (($shortcode_content != '') AND ($shortcode_container != ''))
			{
			$shortcode_content = '<'.$shortcode_container.''.$shortcode_class.''.$shortcode_style.'>'.$shortcode_content.'</'.$shortcode_container.'>';
			}
		
		$this->restore_current_context();
		
		return $shortcode_content;
		}
		
	function widget_condition($args,$instance)
		{
		$this->save_current_context();
		
		$condition_result = FALSE;
		$condition_name = $instance['condition_name'];
		
		if ($condition_name == '')
			{
			$condition_result = TRUE;
			}
		else if (in_array($condition_name,$this->supported_conditions) == FALSE)
			{
			$condition_result = FALSE;
			}
		else if (function_exists($condition_name) OR in_array($condition_name,$this->not_canonical_conditions))
			{
			$condition_args = $this->decode_condition_arguments_string($instance['condition_args']);
			$condition_result = $this->is_condition($condition_name,$condition_args,'widget');
			
			if ($instance['condition_not'] == TRUE)
				{
				if ($condition_result == TRUE)
					{
					$condition_result = FALSE;
					}
				else
					{
					$condition_result = TRUE;
					}
				}
			}
		else
			{
			$condition_result = FALSE;
			}
		
		$this->restore_current_context();
		
		return $condition_result;
		}
		
	function widget_content($args,$instance)
		{
		$this->save_current_context();
		
		$widget_content = '';
		
		$fct_name = trim($instance['function_name']);
		
		if (in_array($fct_name,$this->supported_functions) AND function_exists($fct_name))
			{
			$fct_args = $this->decode_function_arguments_string($instance['function_args']);
			$widget_content .= $this->function_content($fct_name,$fct_args,'widget');
			}
		
		$this->restore_current_context();
		
		return $widget_content;
		}
		
	/* Common used methods */
		
	function save_current_context()
		{
		GLOBAL $post;
		$this->current_post = $post;
		$this->current_context = array();
		}
		
	function restore_current_context()
		{
		GLOBAL $post;
		$post = $this->current_post;
		// reset cached values
		$this->current_post = NULL;
		$this->current_context = array();
		}
		
	// Magic keywords replacement
	function magic_keywords_replace($content)
		{
		$magic_keywords = array('%postID%','%postparent%','%postauthor%');
		$magic_values = array($this->current_post->ID,$this->current_post->post_parent,$this->current_post->post_author);
		$content = str_replace($magic_keywords,$magic_values,$content);
		if (strpos($content,'%defaultcatID%') !== FALSE)
			{
			$content = str_replace('%defaultcatID%',$this->get_magic_keyword_value('%defaultcatID%'),$content);
			}
		if (strpos($content,'%posttagIDs%') !== FALSE)
			{
			$content = str_replace('%posttagIDs%',$this->get_magic_keyword_value('%posttagIDs%'),$content);
			}
		if (strpos($content,'%posttagslugs%') !== FALSE)
			{
			$content = str_replace('%posttagslugs%',$this->get_magic_keyword_value('%posttagslugs%'),$content);
			}
		return $content;
		}
		
	function get_magic_keyword_value($key='')
		{
		if ($this->current_context[$key])
			{
			return $this->current_context[$key];
			}
		else
			{
			$value = '';
			switch($key)
				{
				case 'default_category':
					$value = get_option('default_category');
					break;
				
				case '%defaultcatID%':
					$value = ''.$this->get_magic_keyword_value('default_category');
					break;
					
				case '%posttagIDs%':
					$value = ''.implode(',',wp_get_post_tags($this->current_post->ID,array('fields'=>'ids')));
					break;
					
				case '%posttagslugs%':
					$value = ''.implode(',',wp_get_post_tags($this->current_post->ID,array('fields'=>'slugs')));
					break;
				}
			$this->current_context[$key] = $value;
			return $value;
			}
		}
		
	function decode_function_arguments_string($arguments_string)
		{
		$arguments_string = html_entity_decode($arguments_string);
		$arguments_string = trim(str_replace('&#038;','&',$arguments_string)); // fix HTML entities
		return $arguments_string;
		}
		
	function decode_condition_arguments_string($arguments_string)
		{
		return $this->decode_function_arguments_string($arguments_string);
		}
		
	function explode_condition_arguments_string($arguments_string)
		{
		if (is_numeric($arguments_string))
			{
			$arguments_string = intval($arguments_string);
			}
		return $arguments_string;
		}
		
	function explode_function_arguments_string($arguments_string)
		{
		$arguments_array = array();
		$pairs = explode("&", $arguments_string);
		foreach ($pairs as $pair)
			{
			$pair_array = explode('=', $pair,2);
			$k = trim($pair_array[0]);
			$arguments_array[$k] = '';
			if (isset($pair_array[1]))
				{
				// value is not empty
				$v = trim($pair_array[1]);
				if ((strtolower(substr($v,0,6)) == 'array(') AND (substr($v,-1) == ')'))
					{
					// value is like array('string',integer,boolean)
					$v_array = explode(',',substr($v,6,-1));
					$v = array();
					foreach ($v_array as $v_array_elt)
						{
						$v_array_elt = trim($v_array_elt);
						if ((substr($v_array_elt,0,1) == "'") AND (substr($v_array_elt,-1) == "'"))
							{
							$v_array_elt = substr($v_array_elt,1,-1);
							}
						else if (strtolower($v_array_elt) == 'true')
							{
							$v_array_elt = TRUE;
							}
						else if (strtolower($v_array_elt) == 'false')
							{
							$v_array_elt = FALSE;
							}
						else if (is_numeric($v_array_elt))
							{
							$v_array_elt = intval($v_array_elt);
							}
						if ($v_array_elt != '')
							{
							$v[] = $v_array_elt;
							}
						}
					}
				else if ((substr($v,0,1) == "'") AND (substr($v,-1) == "'"))
					{
					$v = substr($v,1,-1);
					}
				else if (strtolower($v) == 'true')
					{
					$v = TRUE;
					}
				else if (strtolower($v) == 'false')
					{
					$v = FALSE;
					}
				$arguments_array[$k] = $v;
				}
			}
		return $arguments_array;
		}
		
	function implode_function_arguments_array($arguments_array)
		{
		$ret = array();
        foreach ($arguments_array as $k => $v)
			{
            array_push($ret, $k.'='.$v);
			}
        return implode('&', $ret); 
		}
		
	function function_content($fct_name,$fct_args,$target='content')
		{
		// Magic keywords replacement
		$fct_args = $this->magic_keywords_replace($fct_args);
		
		$arguments = $this->explode_function_arguments_string($fct_args);
		$arguments['fct:target'] = $target;
		
		$function_container = '';
		$function_content = '';
		switch($fct_name)
			{
			case 'get_the_post_thumbnail':
				if (has_post_thumbnail($this->current_post->ID))
					{
					$function_container = 'div';
					if (!isset($arguments['size']))
						{
						$arguments['size'] = 'thumbnail';
						}
					$function_content = get_the_post_thumbnail($this->current_post->ID,$arguments['size'],$arguments);
					}
				break;
				
			case 'get_the_tag_list':
				if (is_single($this->current_post->ID) OR is_page($this->current_post->ID))
					{
					if (!isset($arguments['before']))
						{
						$arguments['before'] = '';
						}
					if (!isset($arguments['sep']))
						{
						$arguments['sep'] = ' ';
						}
					if (!isset($arguments['after']))
						{
						$arguments['after'] = '';
						}
					$function_container = 'div';
					$function_content = get_the_tag_list($arguments['before'],$arguments['sep'],$arguments['after']);
					}
				break;
				
			case 'wp_get_archives':
				$arguments['echo'] = '0'; // Never echo()
				$arguments['format'] = 'html'; // Only HTML output
				$function_container = 'ul';
				$function_content = wp_get_archives($arguments);
				break;
				
			case 'wp_get_recent_posts':
				if (!isset($arguments['suppress_filters']))
					{
					$arguments['suppress_filters'] = false;
					}
				if (!isset($arguments['post_status']))
					{
					$arguments['post_status'] = 'publish';
					}
				if (!isset($arguments['fct:perm']))
					{
					$arguments['fct:perm'] = 'readable';
					}
				if (!isset($arguments['exclude']))
					{
					$arguments['exclude'] = $this->current_post->ID;
					}
				if (isset($arguments['fct:thumbnail_size']))
					{
					$arguments['fct:show_thumbnail'] = TRUE;
					}
				if (($arguments['fct:show_thumbnail']) AND (!isset($arguments['fct:thumbnail_size'])))
					{
					$arguments['fct:thumbnail_size'] = 'thumbnail';
					}
				
				$function_container = 'ul';
				$function_content = $this->wp_get_recent_posts_content($arguments);
				break;
				
			case 'wp_list_authors':
				$arguments['echo'] = '0'; // Never echo()
				$function_container = 'ul';
				if (($arguments['style']=='none') OR ($arguments['html']==='0') OR ($arguments['html']===false))
					{
					$function_container = 'div';
					}
				$function_content = wp_list_authors($arguments);
				break;
				
			case 'wp_list_bookmarks':
				$arguments['echo'] = '0'; // Never echo()
				$function_container = 'ul';
				$function_content = wp_list_bookmarks($arguments);
				break;
			
			case 'wp_list_categories':
				$arguments['echo'] = '0'; // Never echo()
				$function_container = 'ul';
				$function_content = wp_list_categories($arguments);
				break;
				
			case 'wp_list_pages':
				$arguments['echo'] = '0'; // Never echo()
				$function_container = 'ul';
				$function_content = wp_list_pages($arguments);
				break;
				
			case 'wp_nav_menu':
				$arguments['echo'] = false; // Never echo()
				$function_content = wp_nav_menu($arguments);
				break;
				
			case 'wp_tag_cloud':
				$arguments['echo'] = '0'; // Never echo()
				if ($arguments['format'] != 'list')
					{
					$arguments['format'] = 'flat'; // Allow only flat or list
					}
				if ($arguments['format'] == 'flat')
					{
					$function_container = 'div';
					}
				$function_content = wp_tag_cloud($arguments);
				break;
			}
			
		if (isset($arguments['fct:container']))
			{
			$function_container = ''.$arguments['fct:container'];
			}
		if (($function_content != '') AND ($function_container != ''))
			{
			$function_id = '';
			if ($arguments['fct:container_id'] != '')
				{
				$function_id = ' id="'.$arguments['fct:container_id'].'"';
				}
			$function_style = '';
			if ($arguments['fct:container_style'] != '')
				{
				$function_style = ' style="'.$arguments['fct:container_style'].'"';
				}
			$function_class = '';
			if (isset($arguments['fct:container_class']))
				{
				if ($arguments['fct:container_class'] != '')
					{
					$function_class = ' class="'.$arguments['fct:container_class'].'"';
					}
				}
			else
				{
				$function_class = ' class="'.$fct_name.'"';
				}
			$function_content = '<'.$function_container.''.$function_id.''.$function_class.''.$function_style.'>'.$function_content.'</'.$function_container.'>';
			}
			
		return $function_content;
		}
		
	function is_condition($condition_name,$condition_args,$target='widget')
		{
		// Magic keywords replacement
		$condition_args = $this->magic_keywords_replace($condition_args);
		
		$arguments = $this->explode_condition_arguments_string($condition_args);

		$condition_result = FALSE;
		
		switch($condition_name)
			{
			case 'cat_is_ancestor_of':
				if ($condition_args == '')
					{
					$condition_result = FALSE;
					}
				else if (is_category() == FALSE)
					{
					$condition_result = FALSE;
					}
				else
					{
					$current_cat_ID = intval(get_query_var('cat'));
					$condition_result = cat_is_ancestor_of($arguments,$current_cat_ID);
					}
				break;
			
			case 'in_category':
				if (is_single() == FALSE)
					{
					$condition_result = FALSE;
					}
				else if ($condition_args != '')
					{
					$condition_result = in_category($arguments,$this->current_post->ID);
					}
				else
					{
					$condition_result = FALSE;
					}
				break;
		
			case 'in_tree_of':
				if ($condition_args == '')
					{
					$condition_result = FALSE;
					}
				else if (is_page())
					{
					$condition_result = $this->page_has_ascendant($arguments);
					}
				else if (is_single())
					{
					if (in_category($arguments,$this->current_post->ID))
						{
						$condition_result = TRUE;
						}
					else
						{
						$condition_result = $this->post_is_in_descendant_category($arguments,$this->current_post->ID);
						}
					}
				else if (is_category())
					{
					if (is_category($arguments))
						{
						$condition_result = TRUE;
						}
					else
						{
						$current_cat_ID = intval(get_query_var('cat'));
						$condition_result = cat_is_ancestor_of($arguments,$current_cat_ID);
						}
					}
				else
					{
					$condition_result = FALSE;
					}
				
				break;
			
			case 'is_category':
				if (is_category() == FALSE)
					{
					$condition_result = FALSE;
					}
				else if ($condition_args != '')
					{
					$condition_result = is_category($arguments);
					}
				else
					{
					$condition_result = TRUE;
					}
				break;
				
			case 'is_category_in_tree_of':
				if ($condition_args == '')
					{
					$condition_result = FALSE;
					}
				else if (is_category() == FALSE)
					{
					$condition_result = FALSE;
					}
				else if (is_category($arguments))
					{
					$condition_result = TRUE;
					}
				else
					{
					$current_cat_ID = intval(get_query_var('cat'));
					$condition_result = cat_is_ancestor_of($arguments,$current_cat_ID);
					}
				break;
			
			case 'is_front_page':
				$condition_result = is_front_page();
				break;
				
			case 'is_home':
				$condition_result = is_home();
				break;
				
			case 'is_page':
				if (is_page() == FALSE)
					{
					$condition_result = FALSE;
					}
				else if ($condition_args != '')
					{
					$condition_result = is_page($arguments);
					}
				else
					{
					$condition_result = TRUE;
					}
				break;
		
			case 'is_page_descendant_of':
				if ($condition_args == '')
					{
					$condition_result = FALSE;
					}
				else if (is_page() == FALSE)
					{
					$condition_result = FALSE;
					}
				else if (is_page($arguments))
					{
					$condition_result = FALSE;
					}
				else
					{
					$condition_result = $this->page_has_ascendant($arguments);
					}
				break;
		
			case 'is_page_in_tree_of':
				if ($condition_args == '')
					{
					$condition_result = FALSE;
					}
				else
					{
					$condition_result = $this->page_has_ascendant($arguments);
					}
				break;
			
			case 'is_single':
				if (is_single() == FALSE)
					{
					$condition_result = FALSE;
					}
				else if ($condition_args != '')
					{
					$condition_result = is_single($arguments);
					}
				else
					{
					$condition_result = TRUE;
					}
				break;
		
			case 'is_single_in_tree_of':
				if ($condition_args == '')
					{
					$condition_result = FALSE;
					}
				else if (is_single() == FALSE)
					{
					$condition_result = FALSE;
					}
				else if (in_category($arguments,$this->current_post->ID))
					{
					$condition_result = TRUE;
					}
				else
					{
					$condition_result = $this->post_is_in_descendant_category($arguments,$this->current_post->ID);
					}
				break;
			}
				
		return $condition_result;
		}
		
	function esc_textarea($text)
		{
		if (function_exists('esc_textarea'))
			{
			return esc_textarea($text);
			}
		else
			{
			$safe_text = htmlspecialchars( $text, ENT_QUOTES, get_option( 'blog_charset' ) );
			return apply_filters( 'esc_textarea', $safe_text, $text );
			}
		}
		
	function page_has_ascendant($root_post)
		{
		global $post;
		if (is_page())
			{
			if (is_page($root_post))
				{
				return true;
				}
			$ancestors = get_post_ancestors($post->ID);
			foreach ($ancestors as $ancestor)
				{
				if($ancestor == $root_post)
					{
					return true;
					}
				}
			}
		return false;
		}
		
	function post_is_in_descendant_category($cats, $_post = null)
		{
		foreach((array) $cats as $cat)
			{
			// get_term_children() accepts integer ID only
			$descendants = get_term_children((int) $cat, 'category');
			if ($descendants && in_category($descendants, $_post))
				{
				return true;
				}
			}
		return false;
		}

	/* methods to render API result as HTML ouput */

	function wp_get_recent_posts_content($arguments)
		{
		$result_content = '';
		$excerpt_length = apply_filters('excerpt_length',55);
		$excerpt_more = '[...]';
		$strip_shortcodes_exists = function_exists('strip_shortcodes');
		$get_the_post_thumbnail_exists = function_exists('get_the_post_thumbnail');
		
		$recent_posts = wp_get_recent_posts($arguments);
		foreach($recent_posts as $recent)
			{
			$recent_permalink = get_permalink($recent['ID']);
			
			if ($recent['post_status'] == 'private')
				{
				if ($arguments['fct:perm'] == 'readable')
					{
					if (current_user_can('read_private_posts') == FALSE)
						{
						continue;
						}
					}
				else
					{
					if (current_user_can('read_private_posts') == FALSE)
						{
						$recent_permalink = '';
						}
					}
				}
			else if ($recent['post_status'] != 'publish')
				{
				if ($arguments['fct:perm'] == 'readable')
					{
					continue;
					}
				$recent_permalink = '';
				}
			else if ($recent['post_password'] != '')
				{
				if ($arguments['fct:perm'] == 'readable')
					{
					continue;
					}
				}
			
			$result_content .= '<li class="post">';
			
			if ($get_the_post_thumbnail_exists AND ($arguments['fct:show_thumbnail'] == TRUE) AND has_post_thumbnail($recent['ID']))
				{
				if ($recent_permalink != '')
					{
					$result_content .= '<a class="thumbnail" href="'.$recent_permalink.'" title="'.$recent['post_title'].'">';
					}
				else
					{
					$result_content .= '<a class="thumbnail" title="'.$recent['post_title'].'">';
					}
				$result_content .= get_the_post_thumbnail($recent['ID'],$arguments['fct:thumbnail_size'])."\r\n";
				$result_content .= '</a>';
				}
			
			if ($recent_permalink != '')
				{
				$result_content .= '<a class="title" href="'.$recent_permalink.'" title="'.$recent['post_title'].'">';			
				}
			else
				{
				$result_content .= '<a class="title" title="'.$recent['post_title'].'">';
				}
			$result_content .= ''.$recent['post_title'].'';
			$result_content .= '</a>';
			
			if ($arguments['fct:show_date'])
				{
				$result_content .= "\r\n".'<p class="date">'.mysql2date(get_option('date_format'), $recent['post_date']).'</p>';
				}
			
			if ($arguments['fct:show_excerpt'])
				{
				$post_excerpt = trim($recent['post_excerpt']);
				if (($post_excerpt == '') AND ($recent['post_password'] == '') AND ($recent['post_status'] == 'publish'))
					{
					$post_excerpt = trim($recent['post_content']);
					if ($strip_shortcodes_exists == TRUE) $post_excerpt = strip_shortcodes($post_excerpt);
					if (($more_pos = strpos($post_excerpt,'<!--more-->')) AND ($more_pos !== FALSE))
						{
						$post_excerpt = substr($post_excerpt,0,$more_pos);
						$post_excerpt = trim(strip_tags($post_excerpt));
						}
					else
						{
						$post_excerpt = trim(strip_tags($post_excerpt));
						$words = explode(' ', $post_excerpt, $excerpt_length + 1);
						if (count($words) > $excerpt_length)
							{
							array_pop($words);
							array_push($words, $excerpt_more);
							$post_excerpt = implode(' ', $words);
							}
						}
					}
				$result_content .= "\r\n".'<p class="excerpt">'.$post_excerpt.'</p>';
				}
			
			$result_content .= '</li>'."\r\n";
			}
		
		return $result_content;
		}
	}
	
class functionsCapacitor_widget extends WP_Widget
	{
	var $functionsCapacitor_class;
	
	/* Constructor */
	
	function functionsCapacitor_widget()
		{
		$this->functionsCapacitor_class = new functionsCapacitor();
		$widget_ops = array();
		$widget_ops['classname'] = 'widget_functionsCapacitor';
		$widget_ops['description'] = __('Back WordPress API to the widget');
		$control_ops = array();
		$control_ops['width'] = 400;
		$control_ops['height'] = 350;
		parent::WP_Widget(false,$name='functionsCapacitor',$widget_ops,$control_ops);
		}
		
	/* widget callback methods */
	
	// widget content
	function widget($args,$instance)
		{
		if (!isset($instance['title']))			 $instance['title']			 = '';
		if (!isset($instance['condition_name'])) $instance['condition_name'] = '';
		if (!isset($instance['condition_args'])) $instance['condition_args'] = '';
		
		$widget_content = '';
		$condition_result = $this->functionsCapacitor_class->widget_condition($args,$instance);
		
		if ($condition_result == TRUE)
			{
			$widget_content = $this->functionsCapacitor_class->widget_content($args,$instance);
			
			if (($instance['hide_if_empty'] == TRUE) AND ($widget_content == ''))
				{
				// display nothing
				}
			else
				{
				extract($args);
				if ($instance["title"] != '')
					{
					$widget_content = $before_title.$instance["title"].$after_title.$widget_content;
					}
				echo($before_widget.$widget_content.$after_widget);
				}
			}
		}

	// widget setup form
	function form($instance)
		{
		if (!isset($instance['title']))			 $instance['title']			 = '';
		if (!isset($instance['function_name']))	 $instance['function_name']	 = 'wp_get_recent_posts';
		if (!isset($instance['function_args']))	 $instance['function_args']	 = '';
		if (!isset($instance['condition_name'])) $instance['condition_name'] = '';
		if (!isset($instance['condition_args'])) $instance['condition_args'] = '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id("title"); ?>">
				<?php _e( 'Title' ); ?>:
			</label>
			<input class="widefat" id="<?php echo $this->get_field_id("title"); ?>" name="<?php echo $this->get_field_name("title"); ?>" type="text" value="<?php echo esc_attr($instance["title"]); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('function_name'); ?>">
				<?php _e('Select function'); ?>:
			</label>
			<select id="<?php echo $this->get_field_id('function_name'); ?>" name="<?php echo $this->get_field_name('function_name'); ?>">
			<?php
			foreach($this->functionsCapacitor_class->supported_functions as $supported_function)
				{
				$selected = $instance['function_name'] == $supported_function ? ' selected="selected"' : '';
				echo('<option'.$selected.' value="'.$supported_function.'">'.$supported_function.'()</option>');
				}
			?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('function_args'); ?>">
				<?php _e('Parameters'); ?>:
			</label>
			<textarea class="widefat" rows="6" cols="20" id="<?php echo $this->get_field_id('function_args'); ?>" name="<?php echo $this->get_field_name('function_args'); ?>"><?php echo $this->functionsCapacitor_class->esc_textarea($instance['function_args']); ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('condition_name'); ?>">
				<?php _e('Condition'); ?>:
			</label>
			<input id="<?php echo $this->get_field_id('condition_not'); ?>" name="<?php echo $this->get_field_name('condition_not'); ?>" type="checkbox" <?php checked(isset($instance['condition_not']) ? $instance['condition_not'] : 0); ?> />
			<label for="<?php echo $this->get_field_id('condition_not'); ?>">
				<?php _e('not'); ?>
			</label>
			<select id="<?php echo $this->get_field_id('condition_name'); ?>" name="<?php echo $this->get_field_name('condition_name'); ?>">
			<option value=""></option>
			<?php
			foreach($this->functionsCapacitor_class->supported_conditions as $supported_condition)
				{
				$selected = $instance['condition_name'] == $supported_condition ? ' selected="selected"' : '';
				echo('<option'.$selected.' value="'.$supported_condition.'">'.$supported_condition.'</option>');
				}
			?>
			</select>
			(<input size="4" id="<?php echo $this->get_field_id("condition_args"); ?>" name="<?php echo $this->get_field_name("condition_args"); ?>" type="text" value="<?php echo esc_attr($instance["condition_args"]); ?>" />)
		</p>
		<p>
			<input id="<?php echo $this->get_field_id('hide_if_empty'); ?>" name="<?php echo $this->get_field_name('hide_if_empty'); ?>" type="checkbox" <?php checked(isset($instance['hide_if_empty']) ? $instance['hide_if_empty'] : 0); ?> />
			<label for="<?php echo $this->get_field_id('hide_if_empty'); ?>">
				<?php _e('Hide this widget if selected function return empty result'); ?>
			</label>
		</p>
		<?php
		}
	
	// widget setup update
	function update($new_instance,$old_instance)
		{
		$instance = $new_instance;
		$instance['condition_not'] = isset($new_instance['condition_not']);
		$instance['hide_if_empty'] = isset($new_instance['hide_if_empty']);
		return $instance;
		}
	}

add_action('plugins_loaded',array('functionsCapacitor','plugins_loaded_action'));

?>