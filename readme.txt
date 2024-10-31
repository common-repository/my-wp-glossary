=== My WP Glossary ===
Contributors: audrasjb, alexischenal, leprincenoir, whodunitagency, virginienacci, bmartinent
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 5.6
Stable tag: 0.6.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A glossary block for your WordPress website, with structured data and powered by a Gutenberg block or a shortcode.

== Description ==

This plugin make it really simple to add a glossary page to your website.

It uses Schema.org `DefinedTermSet` structured data for better search engine optimization (SEO) of your definitions list.

Once you definition terms are ready, you can easily include them into a glossary page using our Glossary block for Gutenberg (or the `[glossary]` shortcode if you’re not using Gutenberg yet).

Plus, this plugin will automatically add a link to the related glossary definitions each time the term appears in all your posts and pages. This is super helpful for your internal linking.

By default, this plugin use a very minimal amount of CSS styles (so it works well on all WordPress themes!), but it provides all the CSS classes you’ll need to style it on your own :)

== Screenshots ==

1. Glossary definition items.
2. Glossary Gutenberg block in the editor.
3. Glossary page on Twenty Twenty-One bundled theme.
4. Glossary page on Twenty Twenty bundled theme.
5. Definition are linked to your Glossary Page each time they appear on your website’s Posts.

== Installation ==

1. Install the plugin and activate.
2. Go to Glossary Admin Menu.
3. Add definitions to your glossary.
4. Create a page to host your Glossary.
5. Insert your Glossary into this page using the Glossary Gutenberg Block or the `[glossary]` shortcode.

== Changelog ==

= 0.6.4 =
Back to the regular versioning system. We won't use alphabetical letters anymore.
Also: WP 6.4 compatibility.

= 0.6.3c =
quick fix compatibility issues - add a simple check before loading simple_html_dom

= 0.6.3b =
rollback - fixe a bug in term detection when the same term is repeated.
this fixe add more indesired cases that it's resolved

= 0.6.3 =
fixe a bug where the plugin fail to detect the glossary page if the shortcode was use.
fixe a bug in term detection when the same term is repeated.
update term encapsulation behavior for a better detection of parenthesis, brackets, punctuations and quotations marks around terms.
cache is updated when term change title, status or deleted.
cache processing is less resources intensive when a term are added, updated or deleted
add filter 'mywpglossary_alpha' for index chars used by the glossay
add filter 'mywpglossary_encapsulation_chars' for accepted encapsulations chars
add filter 'mywpglossary_term_transient_key' for cache transient key
add filter 'mywpglossary_term_transient_expiration' for cache expiration time

= 0.6.2 =
* fix cache bug introduced by the last version
* update modal display on mobile ( fixed at the bottom of the screen )

= 0.6.1 =
* change default term display mode 'popin' for 'link' ( check "mywpglossary_insertion_style" hook )
* fix unescaped terms into regex search pattern.
* fix term cache constant reload when no term are publish.
* new filters
	* "mywpglossary_use_single" enable terms single, replace terms content into the block by links to the single

= 0.6 =
Plugin complete refactorization. Props @bmartinent @leprincenoir.

* fix an admin default sort behavior, letter and date are now sortable
* add a simple popin display style base on css
* add a term indexation tool
* add two utility function mywpglossary_get_posts_by_term, mywpglossary_get_terms_by_post
* add support for polylang
* add tippy support check https://atomiks.github.io/tippyjs/ for more detail
* rework term matching with a html parser ( simplehtmldom 1.9.1 ) check http://sourceforge.net/projects/simplehtmldom/ form more details.
* new filters
	* "mywpglossary_matching" change term matching rules ( is_singular or in the in_the_loop and is_main_query and not in a glossary page by default )
	* "mywpglossary_insertion_style" change term display mode use 'link', 'popin' or 'tippy_poppin' ( 'popin' by default )
	* "mywpglossary_override_term" change the terms data ( content, link, etc ) match in the current context
	* "mywpglossary_exclude_tags" change parent tags ignored when searching for glossary terms ( hx and a by default )
	* "mywpglossary_override_glossary_link" change the link generated for each terms
	* "mywpglossary_display_term_content" change the content of each terms
	* "mywpglossary_override_tag_limit" change the number of terms who can be displayed by pages ( -1 for infinite by default )
	* "mywpglossary_tippy_theme" change the tippy theme
	* "mywpglossary_glossary_term_limit" change the maximum display term in the glossary page ( 200 by default )
	* "mywpglossary_glossary_term_archive" change the archive markup for the glossary page

= 0.5 =
* Maintenance update.

= 0.4 =
* In WP-Admin, order definitions by letter and alphabetical order. Props Denis @ [escargote.fr](https://www.escargote.fr/) 🐌

= 0.3 =
* Regex change to avoid false positive (HTML attributes injections). Props @leprincenoir.

= 0.2 =
* Few small enhancements.

= 0.1 =
* Plugin initial release. Works fine :)
