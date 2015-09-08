[![Build Status](https://travis-ci.org/dashaluna/hm-content-toc.svg?branch=master)](https://travis-ci.org/dashaluna/hm-content-toc)

=== HM Content TOC ===
Contributors: dashaluna, tcrsavage, johnbillion, sanchothefat
Tags: TOC, TOC shortcode, shortcode, content TOC, post TOC, page TOC, TOC for content, table of content, HM, Human Made
Requires at least: 4.2
Tested up to: 4.3
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Creates TOC (table of content) for specified HTML elements from post/page content; to allow jumping to corresponding header by clicking a link in TOC.

== Description ==

The plugin provides:

* Shortcode `[hm_content_toc]` with `title` and `headers` attributes.
 The shortcode should be inserted into post/page content with optional attributes
 Example: `[hm_content_toc title="TOC title" headers="h2, h3, h4"]`
* Plugin settings to specify default TOC settings for title and header, which will
 be used in case a shortcode is specified without attributes, i.e. `[hm_content_toc]`
 Plugin's defaults are: empty title and headers `h2, h3, h4, h5, h6`
* Integration with [Shortcake (Shortcode UI) plugin](https://wordpress.org/plugins/shortcode-ui/)

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

= Supported WordPress versions =

The plugin has been extensively tested on both standard and multisite installations
for the following WordPress versions:

* latest stable version [refer to Release Archive on wordpress.org site](https://wordpress.org/download/release-archive/)
* 4.2

== Screenshots ==

== Changelog ==

== Upgrade Notice ==

== Bugs or feature requests ==

To report bugs or feature requests, [please use Github issues](https://github.com/dashaluna/hm-content-toc/issues).

== License: GPLv2 or later ==

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
