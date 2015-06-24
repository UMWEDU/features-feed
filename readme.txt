=== Featured Images in Feeds ===
Contributors: cgrymala
Donate link: http://giving.umw.edu/
Tags: feeds, featured images, thumbnails, rss, atom
Requires at least: 3.9.1
Tested up to: 4.1.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Implements a new feed within WordPress that includes the featured images as enclosures. Also adds query parameters to all feeds that allow you to specify a post offset or change the number of posts included in the feed.

== Description ==

This plugin adds a new feed format to WordPress installations that automatically includes the featured image for each piece of content as an enclosure. Rather than modifying the standard WordPress feeds, this plugin creates a new format of feed, to avoid using the featured image when it's not necessary. The featured image enclosures are fully compatible with other enclosures that may already be included in the standard feeds.

You can adjust the size of the images included as enclosures. Because of the processing power that is required in order to include large files as enclosures, it's recommended that you configure the plugin to use the smallest viable version of images for the intended purpose.

This plugin also adds two new query parameters that can be used to control the behavior of all feeds within your WordPress installation. These query parameters allow you to specify an offset (skip the first X number of posts - using the `posts` parameter) and allows you to specify how many items should be included in the feed (using the `posts` query parameter).

Finally, this plugin adds an extra feed that allows you to pull multiple sizes of the featured image as enclosures. You can use the `with-custom-features` feed to pull multiple sizes. For this method, you will need to separate the different sizes with a pipe character (`|`) and, if you are defining width & height, you will need to separate those with an asterisk (`*`). Usage might look like `http://www.example.com/feed/with-custom-features?size=50px-thumb|1140*400`, which would pull a 50x50 thumbnail and an image closest to 1140px wide & 400px tall.

== Installation ==

1. Upload `features-feed.php` to the `/wp-content/mu-plugins/` directory

== Frequently Asked Questions ==

= How do I access the new feed created by this plugin? =

Simply append /with-features/ or /with-thumbs/ to the end of your original feed URL. For instance, if your main feed is located at http://www.example.org/feed/, you can access the new feeds at http://www.example.org/feed/with-features/ and http://www.example.org/feed/with-thumbs/

= What is the difference between the features feed and the thumbs feed? =

The main features feed only includes the "large" image size, and is not configurable beyond that. The thumbs feed allows you to specify, using an extra parameter, the size of image you would like it to attempt to include.

= How do I adjust the image size in the thumbs feed? =

Simply append a "size" query parameter to the feed. That query parameter can accept a named WordPress image size (such as "large", "medium" or "thumbnail") or it can accept a height & width parameter, separated by either the pipe character (`|`) or the asterisk character (`*`).

For instance, to use the thumbnail size in the feed, you could use http://www.example.org/feed/with-thumbs/?size=thumbnail; to use the size closest to 100px wide by 150px tall, you could use http://www.example.org/feed/with-thumbs/?size=100|150 or http://www.example.org/feed/with-thumbs/?size=100*150.

= Why are my enclosures not cropped/resized exactly the way I want them? =

This plugin creates two new image sizes:

1. 50px-thumb - cropped to a 50x50 square
1. 75px-thumb - cropped to a 75x75 square

Other than that, it does not create any additional image sizes, to it is entirely dependent on the image sizes that already exist within your WordPress installation. If you need images to be cropped/sized to exact specifications, you will need to register those image sizes yourself, and then run the [Regenerate Thumbnails](https://wordpress.org/plugins/regenerate-thumbnails/) plugin to generate those appropriate sizes.

= How do I use the `offset` and `posts` parameters? =

Let's say your feed is located at http://example.org/feed/. If you want to skip the first 3 posts that would normally appear in that feed, you can go to http://example.org/feed/?offset=3 instead.

If you want to load 27 items in your feed, rather than whatever your admin settings indicate, you can go to http://example.org/feed/?posts=27.

If you want to skip the first 3 items that would normally load in your feed, and output 27 items in your feed, you would go to http://example.org/feed/?offset=3&posts=27.

These parameters should work on all feeds within your installation. You can even use them in combination with the featured images feeds, such as http://example.org/feed/with-thumbs/?size=100|150&offset=3&posts=27.

== Changelog ==

= 0.3 =
* Add `posts` and `offset` parameters to allow control over feed output

= 0.2 =
* Begin converting plugin to OOP

= 0.1 =
* Initial tracked version
