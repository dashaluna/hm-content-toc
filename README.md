[![Build Status](https://travis-ci.org/dashaluna/hm-content-toc.svg?branch=master)](https://travis-ci.org/dashaluna/hm-content-toc)

# HM Table of Contents #
**Contributors:** dashaluna, tcrsavage, johnbillion, sanchothefat, humanmade  
**Tags:** table of contents, table of content, toc, shortcode
**Requires at least:** 4.2  
**Tested up to:** 4.3  
**Stable tag:** 1.0.1  
**License:** GPLv2 or later  
**License URI:** http://www.gnu.org/licenses/gpl-2.0.html  

Creates table of contents (TOC) for specified HTML elements from post/page content; to allow jumping to corresponding header by clicking a link in TOC

## Description ##

### Features ###

* Shortcode `[toc]` with `title` and `headers` attributes.
 The shortcode should be inserted into post/page content with optional attributes
 Example: `[toc title="TOC title" headers="h2, h3, h4"]`
* Plugin settings to specify default table of contents (TOC) settings for title and header, which will
 be used in case a shortcode is specified without attributes, i.e. `[toc]`
 Plugin's defaults are: empty title and headers `h2, h3, h4, h5, h6`
* Integration with ["Shortcake (Shortcode UI)" plugin](https://wordpress.org/plugins/shortcode-ui/)

### Translations Available ###

* English - default, always included
* Русский (Russian) by <a href="https://profiles.wordpress.org/dashaluna">Dasha Luna</a>
* Italiano (Italian) by <a href="https://profiles.wordpress.org/franz-vitulli">Franz Vitulli</a>
* Português (Portuguese - Portugal) by <a href="https://profiles.wordpress.org/anafransilva">Ana Silva</a>

## Installation ##

HM Table of Contents can be installed like any other WordPress plugin.

1. Upload the entire folder `hm-content-toc` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Plugin settings will be added to WordPress Settings admin menu,
   i.e. `Settings -> HM Table of Contents`, to specify the default settings for the
   plugin.

   The settings page is visible only for users who can manage options, by default
   it is super administrators and administrators.
4. Now you can use `[toc]` shortcode in your post/page content to generate
   TOC for specified headers from that post/page content.

## Frequently Asked Questions ##

### 1. What versions of WordPress are supported? ###

The plugin has been extensively tested on both standard and multisite installations
for the following WordPress versions:

* latest stable version [refer to Release Archive on wordpress.org site](https://wordpress.org/download/release-archive/)
* 4.2

## Bugs or feature requests ##

To report bugs or feature requests, [please use Github issues](https://github.com/dashaluna/hm-content-toc/issues).

## Contributing to plugin ##

To contribute to the plugin, please open your Pull Request against [the **develop** branch on Github repository](https://github.com/dashaluna/hm-content-toc/tree/develop)

## Screenshots ##

## Changelog ##

### 1.0.2 ###
* Added Human Made as contributor to the plugin.
* Added `Translations Available` info to the main description.
* Added `Contributing to plugin` info under 'Other Notes' section.
* Renamed plugin to `HM Table of Contents` - only user facing part to allow for backwards compatibility, so the previously working customisation code doesn't break. Updated all corresponding info (translations, screenshots, etc).
* Added shortcode `toc` which is used as the main shortcode for the plugin.

### 1.0.1 ###
* Removed a full stop at the end of the plugin description as it was too long by WP standards - doh!

## Translations Available ##

* English - default, always included
* Русский (Russian) by <a href="https://profiles.wordpress.org/dashaluna">Dasha Luna</a>
* Italiano (Italian) by <a href="https://profiles.wordpress.org/franz-vitulli">Franz Vitulli</a>
* Português (Portuguese - Portugal) by <a href="https://profiles.wordpress.org/anafransilva">Ana Silva</a>

*Note:* All my plugins are localized/translatable by default. This is very important for
all users worldwide. So please contribute your language to the plugin to make it even more useful.
Please read the [instructions on how to contribute a translation](https://github.com/dashaluna/hm-content-toc/tree/master/languages).

## License: GPLv2 or later ##

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
