=== Lity - Responsive Lightboxes ===
Author URI: https://www.evanherman.com
Contributors: eherman24
Tags: responsive, lightbox, captions
Requires at least: 5.0
Tested up to: 6.0
Requires PHP: 5.6
Stable tag: 1.0.0
License: GPL-2.0
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A lightweight, accessible and responsive <a href="https://wordpress.org" target="_blank">WordPress</a> lightbox plugin.

== Description ==

Lity - Responsive Lightboxes is a lightweight, accessible and responsive lightbox plugin.

After installation, all images on your site will open up in a beautiful lightbox.

With additional granular controls, users can turn on or off lightboxes for specific images by class, ID or other attribute, or turn them off for specific pages or posts.

- Lightweight
- Accessible
- Responsive
- Support for Lightbox Captions
- Translation Ready

== Screenshots ==

1. Lity settings page.
2. Demo of Lity - Respnsive Lightboxes opening a lightbox.
3. Lightbox open displaying an image.
4. Lightbox open displaying an image on mobile.
5. Lightbox with image title and caption displaying an image.
6. Lightbox with image title and caption displaying an image on mobile.

== Installation ==

1. Upload the `lity` folder to your `/wp-content/plugins/` directory or alternatively upload the lity.zip file via the plugin page of WordPress by clicking 'Add New' and selecting the zip from your computer.
2. Activate the Lity - Responsive Lightboxes WordPress plugin through the 'Plugins' menu in WordPress.
4. Navigate to 'Settings > Lity - Responsive Lightboxes' to alter the plugin settings.

== Credits ==

This software uses the following open source packages:

- [Lity](https://sorgalla.com/lity/) by [Jan Sorgalla](https://github.com/jsor)
- [Slimselect](https://github.com/brianvoe/slim-select) by [Brian Voelker](https://github.com/brianvoe)
- [Tagify](https://github.com/yairEO/tagify) by [Yair Even Or](https://github.com/yairEO)

Props to [Ben Rothman](https://profiles.wordpress.org/brothman01/) for the plugin idea and testing.

== Frequently Asked Questions ==

= Will this plugin only work on images I have uploaded to my site? =
No, any images on the page can be opened in a lightbox. You can copy images from Google images and paste them into your content, and they will also open in a lightbox.

= Where are the plugin settings? =
The plugins settings are nested inside of the 'Tools' menu item from the WordPress admin dashboard.

= The metadata and cache notice won't go away. Why? =
If the notice at the top of dashboard about fetching your image metadata and caching won't go away, head into 'Tools > Scheduled Actions'. In the list, find the `lity_generate_media` action that has a `pending` status, hover over it and click on 'Run'. If all `lity_generate_media` are all set to 'Complete' and the notice is still visible, head back to the Lity options page and click on 'Clear Lity Transient'.

= Does Lity - Responsive Lightboxes work with videos or other media types? =
At this time no, the plugin will only open up images in a responsive lightbox. Videos and other media types may come at a later time.

= Does Lity - Responsive Lightboxes work with galleries? =
At this time, no. Lity - Responsive Lightboxes only works with single images. Users are not able to navigate through a gallery without closing the lightbox. Users will have to close the lightbox and click on the next image they would like to view. Gallery support and keyboard navigation may come at a later time.

= I have the option set to use full size images, but it's not working. Why? =
Double check the URL of the image is correct and matches the URL of your current site. If you migrated your site from another domain you may not have updated the URLs in the database and/or post content.

= Is there a GitHub repository that I can submit issues or contribute to? =
Yes, our GitHub repository can be found at [https://github.com/EvanHerman/lity](https://github.com/EvanHerman/lity).

== Changelog ==

## 1.0.0
- Initial Release.
