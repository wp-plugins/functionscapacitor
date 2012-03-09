=== functionsCapacitor ===
Contributors: oliezekat
Tags: api, codex, shortcode, content, post, page, pages, posts, links, archives, categories, widget, wordpressmu, wpmu, wpms, multi-site, multisite
Requires at least: 2.5.0
Tested up to: 3.3.1
Stable tag: trunk

This plugin allow to apply some WordPress API's functions into your post/page content or as a widget.

== Description ==

> Back WordPress API to the content.

You can request some* functions of WordPress API with same syntax for arguments into your post or page content.
 functionsCapacitor will insert function result into your content as HTML output.

(*) see [Supported functions](http://wordpress.org/extend/plugins/functionscapacitor/other_notes/#Supported-functions).

= Features =

* Safe process to use this plugin on WordPress MU or WP MultiSite.
* Allow to personalize [Embedded output](http://wordpress.org/extend/plugins/functionscapacitor/other_notes/#Embedded-output)
 for each functions
* Easy and powerfull [Functions arguments syntax](http://wordpress.org/extend/plugins/functionscapacitor/other_notes/#Functions-arguments-syntax)
 to apply any WordPress API options.
* Generic functions arguments with [Magic keywords](http://wordpress.org/extend/plugins/functionscapacitor/other_notes/#Magic-keywords)
* Structured source-code to prevent any conflict.

Three methods to use WordPress API functions :

= Method by shortcode tag =

Insert a tag into your content like [fct function_name="parameter1=value&parameter2=value"].

One shortcode tag can request several functions, input [fct function_name1="arguments" function_name2="arguments"].

= Method with a widget =

Add a functionsCapacitor widget, select a function, input function parameters like "parameter1=value&parameter2=value" (without quotes).

= Method by custom fields =

Set a custom field name with function name and put function arguments into custom field value like "parameter1=value&parameter2=value" (without quotes).

Result is always inserted at end. Plugin has priority 5.
Most plugins use priority 10.
This method is applied if post/page were displayed.
	
= Examples =

* Tag cloud of popular posts tags,
 insert [fct wp_tag_cloud]
* List of children pages of current page,
 insert [fct wp_list_pages="title_li=&child_of=%postID%&depth=1"]
* List of category's recent posts with excerpts,
 insert [fct wp_get_recent_posts="category=X&fct:show_excerpt=1"]
 with X equal category ID number
* List of categories without default category,
 insert [fct wp_list_categories="title_li=&exclude=%defaultcatID%"]

== Installation ==

1. Upload `functionscapacitor.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Other Notes ==

== Embedded output ==

Any functionsCapacitor request will insert `<div class="functionsCapacitor">functions output</div>`.
 Shortcode method allow to personalize main container like
 [fct container="HTML tag name" class="class(es) name(s)" style="CSS properties" function1="args" function2="args"].
 set container="" to remove main container.

Some functions output were inserted with dedicated container like
 `<ul class="function_name">function output</ul>`
 or `<div class="function_name">function output</div>`.
 Personalize this container with [Special functions parameters](http://wordpress.org/extend/plugins/functionscapacitor/other_notes/#Special-functions-parameters).
 
== Functions arguments syntax ==

Follow these examples to setup your requests :

* "parameter1=something&parameter2=25",
 parameter1 and parameter2 typed as strings.
* "parameter1=something&parameter2=false",
 parameter2 typed as boolean.
* "parameter1=&parameter2= &parameter3=''",
 any parameters equal empty string.
* "parameter1= something &parameter2=' something '",
 parameter1 equal 'something',
 parameter2 equal ' something '.
* "parameter1=something&parameter2=array('something','something')",
 parameter2 typed as an array of 2 strings.
* "parameter1=something&parameter2=array(10,5,20)",
 parameter2 typed as an array of 3 integers.
* "parameter1=something&parameter2=array(true,false)",
 parameter2 typed as an array of 2 booleans.

Not supported issues :

* parameter's value can't contain a & character.
* parameter's value can't contain an associative array like array('name'=>value,'name'=>value).
* parameter's value can't contain an array of arrays.
* parameter's value can't contain a PHP variable like $post->ID,
 see [Magic keywords](http://wordpress.org/extend/plugins/functionscapacitor/other_notes/#Magic-keywords).
* parameter's value can't request a PHP or WordPress function.
* parameter's value can't contain PHP code.

== Magic keywords ==

Use these keywords to obtain variables values into your functions arguments :

* %postID% => $post->ID,
 ID of post or page where made the request :
 related post/page into a shortcode,
 or current post/page into a widget.
* %postparent% => $post->post_parent,
 ID of parent page where made the request.
* %postauthor% => $post->post_author,
 author ID of post where made the request.
* %defaultcatID% => default_category,
 ID of default category for newest posts.
* %posttagIDs% => wp_get_post_tags(),
 string of current post tags IDs as "1,2,3,..."
* %posttagslugs% => wp_get_post_tags(),
 string of current post tags slugs as "slug-name,slug-name,..."
 
You can use magic keywords into an array.
 Example: wp_list_categories="exclude=array(%defaultcatID%,1,2,...)"

== Special functions parameters ==

* fct:container to set HTML tag of function container.
 Set "fct:container=''" to remove this container.
 Each functions have a default container related to its output.
* fct:container_class to set container "class" attribute.
 Function name as default.
 Example with shortcode: [fct function_name="fct:container_class=name&param1=value&param2=value"].
* fct:container_style to set container "style" attribute.

functionsCapacitor not create a container if the API function still return a container.
 See [WordPress Codex](http://codex.wordpress.org/Function_Reference "Wordpress documentation") to personalize them.

== Supported functions ==

* get_the_tag_list()
 with before=''&sep=' '&after='' as default,
 apply only on page or post,
 rendered into DIV container
* wp_get_archives()
 with echo=0&format=html as default
* wp_get_recent_posts()
 rendered as list with UL container,
 with exclude=%postID%&suppress_filters=false&post_status=publish&fct:perm=readable as default
 (see bellow)
* wp_list_authors()
 with echo=0 as default
* wp_list_bookmarks()
 with echo=0 as default
* wp_list_categories()
 with echo=0 as default
* wp_list_pages()
 with echo=0 as default
* wp_nav_menu()
 with echo=false as default,
 see Codex to setting its container
* wp_tag_cloud()
 with echo=0 as default,
 format=flat|list only
 
See [WordPress Codex](http://codex.wordpress.org/Function_Reference "Wordpress documentation") about these functions and their arguments syntax.

Need you to support more functions, mail to oliezekat@yahoo.fr

= wp_get_recent_posts() =

* if fct:perm=readable hide draft, future, pending, protected posts
 but display private posts if user is allowed.
* if fct:perm='' not output permalink of draft, future, pending, private posts.
* set fct:show_excerpt=1|true to display posts excerpts.
* set fct:show_thumbnail=1|true to display post thumbnail,
 with fct:thumbnail_size=thumbnail|medium|large|post-thumbnail
 or size name defined with add_image_size() into theme's file functions.php.
* fct:show_thumbnail=true if fct:thumbnail_size is defined
* display excerpt and thumbnail for draft, future, pending, private, and protected posts
 but not create an excerpt from content.

== Frequently Asked Questions ==

= Is it safe ? =

Yes ! And you can install functionsCapacitor on WPMU or WPMS.

* functionsCapacitor not execute or eval users input (function name or arguments).
* functionsCapacitor output is managed by WordPress itself, and related to users role.
 Example: wp_list_pages() function will not return private pages if current user is anonymous.

= How come from functions capacitor idea ? =

> I was standing on my chair in front of the computer,
> I was copy-paste a hack into a template,
> the desk was wet,
> my mouse slipped, right button hit the flowerpot,
> and when I saw the result on screen I had a revelation ! A vision !
> The functions capacitor !

= D'où vient l'idée du convecteur de functions ? =

> J'étais assis devant mon ordinateur,
> j'allais copier-coller un hack dans un template,
> le bureau était mouillé,
> ma souris a glissé, le bouton-droit a heurté le pot de fleurs,
> et en voyant le résultat à l'écran j'ai eu une révélation ! Une vision !
> Le convecteur de fonctions !

== Changelog ==

= 0.9.2 =

* set post_status=publish as default for wp_get_recent_posts().
* allow post_status=draft|future|pending for wp_get_recent_posts(),
 but require to set fct:perm='' to display these posts.
* if request wp_get_recent_posts() with fct:perm='',
 any posts with as draft, future, pending, private status are display without permalink.

= 0.9 =

* add fct:perm=readable for wp_get_recent_posts().

= 0.8 =

* add fct:container special parameter.
* support get_the_tag_list().
* allow to personalize "style" attribute of shortcode main container.
* add fct:container_style special parameter.
* add %defaultcatID% magic keyword.
* add %posttagIDs% magic keyword.
* add %posttagslugs% magic keyword.

= 0.7 =

* add fct:show_thumbnail and fct:thumbnail_size parameters for wp_get_recent_posts().
* allow to personalize shortcode main container.

= 0.6 =

* support wp_list_authors().
* add %postparent% magic keyword.
* add %postauthor% magic keyword.

= 0.5 =

* purpose to use API's functions as a widget.
* set "exclude=%postID%" as default for wp_get_recent_posts().

= 0.4 =

* support wp_get_recent_posts() as UL list rendering.
* support parameter value as an array of strings, integers, or booleans
 like "parameter=array('string',integer,true,false,...)".
* check an API function exists.
* add special parameter "fct:container_class=class(es) name(s)".

= 0.3 =

* support wp_tag_cloud() function.

= 0.2 =

* support wp_nav_menu() function.
* fix function arguments processing from shortcode tag.

= 0.1 =

* Experimental release which support wp_get_archives(), wp_list_bookmarks(), wp_list_categories(), and wp_list_pages().

