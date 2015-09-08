[![Build Status](https://travis-ci.org/dashaluna/hm-content-toc.svg?branch=master)](https://travis-ci.org/dashaluna/hm-content-toc)

=== Plugin Name ===
Contributors: dashaluna, tcrsavage, johnbillion, sanchothefat
Donate link:
Tags: content TOC, TOC, table of content
Requires at least:
Tested up to:
Stable tag:
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

== License: GPLv2 or later ==

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
