=== functionsCapacitor ===
Contributors: oliezekat
Tags: content, post, page, api
Requires at least: 2.5.0
Tested up to: 3.3.1
Stable tag: trunk

This plugin allow to apply some Wordpress API's functions into your post/page content.

== Description ==

> Back Wordpress Codex functions into the content.

You could request some (supported) functions of Wordpress API with same syntax for arguments. Result will inserted into your post/page content as HTML output.

= Method by custom fields =

Set a custom field name with function name and put function arguments into custom field value.

Result is always inserted at end. Plugin has priority 5.
Most plugins use priority 10.
This method is applied if post/page were displayed.
	
= Method by shortcode tag =

Insert a tag into your content as [fct function_name="function arguments"].

One shortcode tag can request several functions.

= HTML output =

Any functionsCapacitor request will insert `<div class="functionsCapacitor">functions output</div>`.

Some functions output were inserted as `<ul class="function_name">function output</ul>` or `<div class="function_name">function output</div>`.

== Supported functions ==

* wp_get_archives() with echo=0&format=html as default
* wp_list_bookmarks() with echo=0 as default
* wp_list_categories() with echo=0 as default
* wp_list_pages() with echo=0 as default

See [Wordpress Codex](http://codex.wordpress.org/ "Wordpress documentation") about these functions and their arguments.

== Installation ==

1. Upload `functionscapacitor.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= How come from functions capacitor idea ? =

> I was standing on my chair in front of the computer,
> I was copying-paste a hack into a template,
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

= 0.1 =

* Experimental release which support wp_get_archives(), wp_list_bookmarks(), wp_list_categories(), and wp_list_pages().

