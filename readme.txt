=== Collections Base Lite ===

Contributors: markorangeleafcom
Donate link: http://orangeleaf.com/
Tags: collections base, museums, archives
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 1.1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The collections base lite plugin provides a simple search and display of records held in the Orangeleaf Systems Ltd Collections Base repository.

== Description ==

The collections base lite plugin provides a simple search and display of records held in the Orangeleaf Systems Ltd Collections Base repository. 
This is a repository of Museum, Archive and Local History data from around the UK. It currently (as at Sep-2012) holds around 2 million records 
crosswalked to spectrum XML from all of the major collections management systems.

CollectionsBase Pro is a suite of specially developed web applications that enable the large scale aggregation, indexing and integration of a 
wide set of digital heritage data. This data can come in many forms, from many different systems:  Archive data from Axiell CALM, HER data from 
Exegesis, Museums data from Adlib and MODES to name a few. The system can extract from SQL Server, Access and Excel based databases of metadata

CollectionsBase helps people to be actively engaged with digital heritage material via web sites, subject specialist micro-sites, crowd-sourced 
commenting and tags, social media, apps and multiple other platforms. 

Collections Base Lite is freely available for your website or blog. If you wish to use Collection Base Pro, please contact us on 01743 352000 
or using our contact form on our website http://www.orangeleaf.com and we'll provide more information on CollectionsBase.

== Installation ==

This section describes how to install the plugin and get it working.

1. Upload 'plugin-name.php' to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add the shortcode [collectionsbase_lite] with any required attributes to the page where you want the display to appear. Supported attributes are:

* partner: Results are filtered by the given partner code
* project: Results are filtered by the given project code
* images:  If this is set then only records with images will be returned
* query:   A search to prepopulate the listing with (if this is specified, no for will display)

Currently supported project codes (as at 20120925)

* BCH:  Black Country History (10 partners)
* CFTF: Connecting for the Future (9 partners)
* DSH:  Shropshire History (7 partners)
* ESP:  Exploring Surrey's Past (6 partners)
* HELO: Heritage East Lothian Online (5 partners)
* COV:  Coventry (3 partners)
* GWY:  Gwynedd Collections (1 partner)

== Frequently Asked Questions ==

None yet

== Screenshots ==

1. Search results listing
2. Get record display

== Changelog ==

= 1.1.3 =
* Fixed minor bug with Partner display in getrecord
= 1.1.2 =
* Minor styling changes
= 1.0.1 =
* Security and config changes
= 1.0 =
* Created

== Upgrade Notice ==

= 1.1.2 =
* Authentication fixes

= 1.0.1 =
* Minor security issues fixed. Please update. 