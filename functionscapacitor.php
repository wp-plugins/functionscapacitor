<?php
/*
Plugin Name:	functionsCapacitor
Plugin URI: 
Description:	Back Wordpress Codex functions into the content. This plugin allow to apply some Wordpress API's functions into your post/page content.
Author:			oliezekat
Version:		0.1
Author URI:		http://life2front.com/oliezekat
Licence:		GNU-GPL version 3 http://www.gnu.org/licenses/gpl.html
*/

add_action('plugins_loaded', array('functionsCapacitor', 'plugins_loaded_action'));

class functionsCapacitor
	{
	var $supported_functions = array(
									'wp_get_archives',
									'wp_list_bookmarks',
									'wp_list_categories',
									'wp_list_pages'
									);
	
	/* Constructor */
	function functionsCapacitor()
		{
		if (is_admin())
			{
			
			}
		else
			{
			add_filter('the_content',array(&$this,'the_content_filter'),5);
			add_shortcode('fct', array(&$this,'fct_shortcode'));
			}
		}
	
	/* WP action while plugins are loaded */
	function plugins_loaded_action()
		{
		$me = new functionsCapacitor();
		}
		
	function the_content_filter($content)
		{
		/* custom fields usage */
		// Apply only if page/post displayed
		if (!is_single() AND !is_page()) return $content;
		global $post;
		$custom_fields_content = '';
		$custom_fields = get_post_custom($post->ID);
		foreach ($custom_fields as $custom_key => $custom_values)
			{
			if (in_array($custom_key,$this->supported_functions))
				{
				foreach ($custom_values as $custom_value)
					{
					$custom_fields_content .= $this->function_content($custom_key,$custom_value);
					}
				}
			}
			
		if ($custom_fields_content != '')
			{
			$custom_fields_content = '<div class="functionsCapacitor">'.$custom_fields_content.'</div>';
			}
		
		return $content.$custom_fields_content;
		}
		
	function fct_shortcode($atts)
		{
		$shortcode_content = '';
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
				$fct_args = $att_value;
				}
				
			if (in_array($fct_name,$this->supported_functions))
				{
				$shortcode_content .= $this->function_content($fct_name,$fct_args);
				}
			}
			
		if ($shortcode_content != '')
			{
			$shortcode_content = '<div class="functionsCapacitor">'.$shortcode_content.'</div>';
			}
		
		return $shortcode_content;
		}
		
	/* Magic keywords replacement */
	function magic_keywords_replace($content)
		{
		global $post;
		
		$magic_keywords = array('%postID%');
		$magic_values = array($post->ID);
		$content = str_replace($magic_keywords,$magic_values,$content);
		
		return $content;
		}
		
	function function_content($fct_name,$fct_args)
		{
		global $post;
		
		// Magic keywords replacement
		$fct_args = $this->magic_keywords_replace($fct_args);
		$arguments = array();
		parse_str($fct_args, $arguments);
		
		$function_content = '';
		switch($fct_name)
			{
			case 'wp_get_archives':
				$arguments['echo'] = '0'; // Never echo()
				$arguments['format'] = 'html'; // Only HTML output
				$fct_args = http_build_query($arguments,'','&');
				$function_content = wp_get_archives($fct_args);
				$function_content = '<ul class="wp_get_archives">'.$function_content.'</ul>';
				break;
				
			case 'wp_list_bookmarks':
				$arguments['echo'] = '0'; // Never echo()
				$fct_args = http_build_query($arguments,'','&');
				$function_content = wp_list_bookmarks($fct_args);
				$function_content = '<ul class="wp_list_bookmarks">'.$function_content.'</ul>';
				break;
			
			case 'wp_list_categories':
				$arguments['echo'] = '0'; // Never echo()
				$fct_args = http_build_query($arguments,'','&');
				$function_content = wp_list_categories($fct_args);
				$function_content = '<ul class="wp_list_categories">'.$function_content.'</ul>';
				break;
				
			case 'wp_list_pages':
				$arguments['echo'] = '0'; // Never echo()
				$fct_args = http_build_query($arguments,'','&');
				$function_content = wp_list_pages($fct_args);
				$function_content = '<ul class="wp_list_pages">'.$function_content.'</ul>';
				break;
			}
		return $function_content;
		}
	}
?>