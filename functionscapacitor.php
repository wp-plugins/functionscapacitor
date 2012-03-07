<?php
/*
Plugin Name:	functionsCapacitor
Plugin URI:		http://wordpress.org/extend/plugins/functionscapacitor/
Description:	Back WordPress API to the content. This plugin allow to apply some WordPress API's functions into your post/page content or as a widget.
Author:			oliezekat
Version:		0.7
Author URI:		http://life2front.com/oliezekat
Licence:		GNU-GPL version 3 http://www.gnu.org/licenses/gpl.html
*/

class functionsCapacitor
	{
	var $supported_functions = array(
									'wp_get_archives',
									'wp_get_recent_posts',
									'wp_list_authors',
									'wp_list_bookmarks',
									'wp_list_categories',
									'wp_list_pages',
									'wp_nav_menu',
									'wp_tag_cloud'
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
		$shortcode_class = 'functionsCapacitor';
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
				$shortcode_container = $fct_args;
				}
			else if ($fct_name == 'class')
				{
				$shortcode_class = $fct_args;
				}
			}
			
		if (($shortcode_content != '') AND ($shortcode_container != ''))
			{
			$shortcode_content = '<'.$shortcode_container.' class="'.$shortcode_class.'">'.$shortcode_content.'</'.$shortcode_container.'>';
			}
		
		$this->restore_current_context();
		
		return $shortcode_content;
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
		}
		
	// Magic keywords replacement
	function magic_keywords_replace($content)
		{
		$magic_keywords = array('%postID%','%postparent%','%postauthor%');
		$magic_values = array($this->current_post->ID,$this->current_post->post_parent,$this->current_post->post_author);
		$content = str_replace($magic_keywords,$magic_values,$content);
		return $content;
		}
		
	function decode_function_arguments_string($arguments_string)
		{
		$arguments_string = html_entity_decode($arguments_string);
		$arguments_string = trim(str_replace('&#038;','&',$arguments_string)); // fix HTML entities
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
						$v[] = $v_array_elt;
						}
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
		$arguments['fct:container'] = '';
		if (!$arguments['fct:container_class']) $arguments['fct:container_class'] = $fct_name;
		
		$function_content = '';
		switch($fct_name)
			{
			case 'wp_get_archives':
				$arguments['echo'] = '0'; // Never echo()
				$arguments['format'] = 'html'; // Only HTML output
				$arguments['fct:container'] = 'ul';
				$function_content = wp_get_archives($arguments);
				break;
				
			case 'wp_get_recent_posts':
				$arguments['fct:container'] = 'ul';
				if (!isset($arguments['exclude']))
					{
					$arguments['exclude'] = $this->current_post->ID;
					}
				if (($arguments['fct:show_thumbnail']) AND (!isset($arguments['fct:thumbnail_size'])))
					{
					$arguments['fct:thumbnail_size'] = 'thumbnail';
					}
				$function_content = $this->wp_get_recent_posts_content($arguments);
				break;
				
			case 'wp_list_authors':
				$arguments['echo'] = '0'; // Never echo()
				$arguments['fct:container'] = 'ul';
				if (($arguments['style']=='none') OR ($arguments['html']==='0') OR ($arguments['html']===false))
					{
					$arguments['fct:container'] = 'div';
					}
				$function_content = wp_list_authors($arguments);
				break;
				
			case 'wp_list_bookmarks':
				$arguments['echo'] = '0'; // Never echo()
				$arguments['fct:container'] = 'ul';
				$function_content = wp_list_bookmarks($arguments);
				break;
			
			case 'wp_list_categories':
				$arguments['echo'] = '0'; // Never echo()
				$arguments['fct:container'] = 'ul';
				$function_content = wp_list_categories($arguments);
				break;
				
			case 'wp_list_pages':
				$arguments['echo'] = '0'; // Never echo()
				$arguments['fct:container'] = 'ul';
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
					$arguments['fct:container'] = 'div';
					}
				$function_content = wp_tag_cloud($arguments);
				break;
			}
		
		if (($function_content != '') AND ($arguments['fct:container'] != ''))
			{
			$function_content = '<'.$arguments['fct:container'].' class="'.$arguments['fct:container_class'].'">'.$function_content.'</'.$arguments['fct:container'].'>';
			}
			
		return $function_content;
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
			$result_content .= '<li>';
			
			if ($get_the_post_thumbnail_exists AND $arguments['fct:show_thumbnail'])
				{
				$result_content .= get_the_post_thumbnail($recent["ID"],$arguments['fct:thumbnail_size'])."\r\n";
				}
			
			$result_content .= '<a class="title" href="'.get_permalink($recent["ID"]).'" title="'.$recent["post_title"].'">';
			$result_content .= ''.$recent["post_title"].'';
			$result_content .= '</a>';
			
			if ($arguments['fct:show_excerpt'])
				{
				$post_excerpt = $recent["post_excerpt"];
				if ($post_excerpt == '')
					{
					$post_excerpt = $recent["post_content"];
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
				if ($post_excerpt != '') $result_content .= "\r\n".'<p class="excerpt">'.$post_excerpt.'</p>';
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
		
		$widget_content = $this->functionsCapacitor_class->widget_content($args,$instance);
		
		if (($instance['hide_if_empty'] == TRUE) AND ($widget_content == ''))
			{
			
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
	
	// widget setup form
	function form($instance)
		{
		if (!isset($instance['title']))			 $instance['title']			 = '';
		if (!isset($instance['function_name']))	 $instance['function_name']	 = 'wp_get_recent_posts';
		if (!isset($instance['function_args']))	 $instance['function_args']	 = '';
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
			<textarea class="widefat" rows="6" cols="20" id="<?php echo $this->get_field_id('function_args'); ?>" name="<?php echo $this->get_field_name('function_args'); ?>"><?php echo esc_textarea($instance['function_args']); ?></textarea>
		</p>
		<p>
			<input id="<?php echo $this->get_field_id('hide_if_empty'); ?>" name="<?php echo $this->get_field_name('hide_if_empty'); ?>" type="checkbox" <?php checked(isset($instance['hide_if_empty']) ? $instance['hide_if_empty'] : 0); ?> />
			<label for="<?php echo $this->get_field_id('hide_if_empty'); ?>">
				<?php _e('Hide if empty'); ?>
			</label>
		</p>
		<?php
		}
	
	// widget setup update
	function update($new_instance,$old_instance)
		{
		$instance = $new_instance;
		$instance['hide_if_empty'] = isset($new_instance['hide_if_empty']);
		return $instance;
		}
	}

add_action('plugins_loaded',array('functionsCapacitor','plugins_loaded_action'));

?>