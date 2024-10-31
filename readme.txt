=== ONet Header Linkifier ===
Contributors: orosznyet
Donate link: http://onetdev.com/repo/onet-header-linkifier/
Tags: header, anchor, auto, post, toc
Requires at least: 3.5
Tested up to: 3.7.1
Stable tag: 1.12
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

For advanced users! Github-like header parser for your posts, pages and custom contents. Also lets you fetch TOC for any content.

== Description ==

**Do you like to write long articles with many headers?** Then this is the perfect plugin for you. This plugin will add a link next to your headers so your user can share your posts pointing to specific parts. Plus a smooth scroll script is also included which will make the overall experience even better.

The package contains a predefined style for Git-like link display but it is hardly recommend to change the style to fit your needs (and your design).

The plugin uses internal cache which may interfere with other cache plugins, if you experience anything strange feel free to contact the plugin author.

== Installation ==

1. Install ONet Header Linkifier either via the WordPress.org plugin directory, or by uploading the files to your server
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Chose a method listed in description.

== Frequently Asked Questions ==

= I can't see any changes on the site. Why this plugin does nothing? =

Check if the plugin is enabled in the Plugin manager and in the *Settings > Reading*.

= How can I use custom styles for Git-like display? =

First of all turn off "load predefined styles" in *Settings > Reading* then add your own styles.

= Why does it not work on archives page? =

In order to avoid multiple instances of the same link name the plugin works only on single pages. (You can still call it manually using ONetHeaderLinkifier::perform_parse([content]) )

= How can I get TOC of a content? =

It is simplitcity itself. Use ONetHeaderLinkifier::get_toc([content],[optional: return hierarchical or raw list]).

== Screenshots ==

1. /assets/screenshot-1.png
2. /assets/screenshot-2.jpg

== Changelog ==

= 1.12 =
* Name changed

= 1.02 =
* Missing settings link fix

= 1.01 =
* Updated readme details
* Fixed screenshot path.

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.12 =
Missing settings link issue fix and Name change.

= 1.01 =
Negligible update with few text changes.

= 1.0 =
Initial release