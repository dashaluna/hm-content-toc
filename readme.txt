=== HM Content TOC ===
Contributors: dashaluna, tcrsavage, johnbillion, sanchothefat
Tags: TOC, TOC shortcode, shortcode, content TOC, post TOC, page TOC, TOC for content, table of content, table of contents, HM, Human Made
Requires at least: 4.2
Tested up to: 4.3
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates TOC (table of contents) for specified HTML elements from post/page content; to allow jumping to corresponding header by clicking a link in TOC

== Description ==

The plugin provides:

* Shortcode `[hm_content_toc]` with `title` and `headers` attributes.
 The shortcode should be inserted into post/page content with optional attributes
 Example: `[hm_content_toc title="TOC title" headers="h2, h3, h4"]`
* Plugin settings to specify default TOC settings for title and header, which will
 be used in case a shortcode is specified without attributes, i.e. `[hm_content_toc]`
 Plugin's defaults are: empty title and headers `h2, h3, h4, h5, h6`
* Integration with ["Shortcake (Shortcode UI)" plugin](https://wordpress.org/plugins/shortcode-ui/)

== Installation ==

HM Content TOC can be installed like any other WordPress plugin.

1. Upload the entire folder `hm-content-toc` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Plugin settings will be added to WordPress Settings admin menu,
   i.e. `Settings -> HM Content TOC Settings`, to specify the default settings for the
   plugin.

   The settings page is visible only for users who can manage options, by default
   it is super administrators and administrators.
4. Now you can use `[hm_content_toc]` shortcode in your post/page content to generate
   TOC for specified headers from that post/page content.

== Frequently Asked Questions ==

= 1. What versions of WordPress are supported? =

The plugin has been extensively tested on both standard and multisite installations
for the following WordPress versions:

* latest stable version [refer to Release Archive on wordpress.org site](https://wordpress.org/download/release-archive/)
* 4.2

== Bugs or feature requests ==

To report bugs or feature requests, [please use Github issues](https://github.com/dashaluna/hm-content-toc/issues).

== Screenshots ==

1. WordPress Admin: Plugins page - a summary of the plugin with quick action links to plugin's Settings and [Documentation on github webiste](https://github.com/dashaluna/hm-content-toc#readme).
2. WordPress Admin: Settings page - plugin's default settings. These settings will be used when a shortcode `[hm_content_toc]` is specified without attributes. Shortcode attribute takes precedence over default settings.
3. WordPress Admin: Pages - example of a page that uses `[hm_content_toc]` shortcode with its attributes, to specify title that appears before TOC and HTML element names used to generate the TOC.
4. Main website: Example of a page with generated TOC with specified parameters.
5. Main website: Example of a page when a link from the TOC is clicked. In this case, the `Heading #3 - third level` link from the TOC is clicked, a visitor is taken to that heading within the page content. The URL uses an anchor that has been inserted before the heading in the page content.
6. WordPress Admin: Integration with Shortcake UI - the view when `Add Media` button is clicked. The `Insert Post Element` tab lists all registered shortcodes with Shortcake. The `HM Content TOC` is present to be inserted into post/page content.
7. WordPress Admin: Integration with Shortcake UI - the view when `HM Content TOC` box was clicked, allowing for shortcode parameters to be specified.

All screenshots are taken with WordPress version 4.3.1

== Changelog ==

= 1.0.1 =
* Removed a full stop at the end of the plugin description as it was too long by WP standards - doh!

== Translations ==

* English - default, always included
* Русский (Russian) by <a href="https://profiles.wordpress.org/dashaluna">Dasha Luna</a>
* Italiano (Italian) by <a href="https://profiles.wordpress.org/franz-vitulli">Franz Vitulli</a>
* Português (Portuguese - Portugal) by <a href="https://profiles.wordpress.org/anafransilva">Ana Silva</a>

*Note:* All my plugins are localized/translatable by default. This is very important for
all users worldwide. So please contribute your language to the plugin to make it even more useful.
Please read the [instructions on how to contribute a translation](https://github.com/dashaluna/hm-content-toc/tree/master/languages).

== License: GPLv2 or later ==

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
