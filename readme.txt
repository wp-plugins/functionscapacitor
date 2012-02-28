=== functionsCapacitor ===
Contributors: oliezekat
Tags: content, post, page, api, pages, posts
Requires at least: 2.5.0
Tested up to: 3.3.1
Stable tag: trunk

This plugin allow to apply some WordPress API's functions into your post/page content.

== Description ==

> Back WordPress API to the content.

You could request some (supported*) functions of WordPress API with same syntax for arguments.
 Result will inserted into your post/page content as HTML output.

(*) see "Other Notes" for full list.

= Method by custom fields =

Set a custom field name with function name and put function arguments into custom field value.

Result is always inserted at end. Plugin has priority 5.
Most plugins use priority 10.
This method is applied if post/page were displayed.
	
= Method by shortcode tag =

Insert a tag into your content as [fct function_name="function arguments"].

One shortcode tag can request several functions.

= Examples =

* List of pages, insert [fct wp_list_pages="title_li="]
* List of children pages of current page, insert [fct wp_list_pages="title_li=&child_of=%postID%&depth=1"]

== Installation ==

1. Upload `functionscapacitor.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Other Notes ==

= HTML output =

Any functionsCapacitor request will insert `<div class="functionsCapacitor">functions output</div>`.

Some functions output were inserted as `<ul class="function_name">function output</ul>`
 or `<div class="function_name">function output</div>`.

= Magic keywords =

Use these keywords to obtain variables values into your functions arguments.

* %postID% => $post->ID

= Supported functions =

* wp_get_archives() with echo=0&format=html as default
* wp_list_bookmarks() with echo=0 as default
* wp_list_categories() with echo=0 as default
* wp_list_pages() with echo=0 as default
* wp_nav_menu() with echo=false as default

See [WordPress Codex](http://codex.wordpress.org/ "Wordpress documentation") about these functions and their arguments syntax.

Need you to support more functions, mail to oliezekat@yahoo.fr

== Frequently Asked Questions ==

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

= 0.2 =

* support wp_nav_menu() function.
* fix function arguments processing from shortcode tag.

= 0.1 =

* Experimental release which support wp_get_archives(), wp_list_bookmarks(), wp_list_categories(), and wp_list_pages().

