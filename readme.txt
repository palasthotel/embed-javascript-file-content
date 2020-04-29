=== Embed JavaScript File Content ===
Contributors: palasthotel, greatestview
Donate link: https://palasthotel.de/
Tags: javascript, scripts, enqueue, embed, inline, head, critical, performance, filter, hook
Requires at least: 4.1
Tested up to: 5.4
Stable tag: 1.2.0
Requires PHP: 5.4
License: GNU General Public License v3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Boosts performance of critical short JavaScript files by allowing to embed their code instead of linking to files. Script positions and extra scripts are preserved.

== Description ==
In some critical cases you cannot wait for a JavaScript file to load. Then you can benefit from better performance, if you embed the JavaScript code directly into the `<script>` tag. This is where this plugin comes in: It provides a filter `embed_javascript_file_content_handles`, which takes JavaScript handles and echos their code content into the DOM instead of linking to a file.

Please beware that placing lots of embedded JavaScript code can be critical! First you lose caching benefits and second the document size can increase easily. A general rule of thumb is that you should only consider JavaScript files for inline placement, which are critical and which have a file size lower than ~500 Bytes.

= Example =

`
add_action( 'wp_enqueue_scripts', 'my_scripts' );
function my_scripts() {
	// Some critical script is enqueued
	wp_enqueue_script( 'js-detection', get_template_directory_uri() . '/js/js-detection.js' );
}

/**
 * Define JavaScript handles to be echoed inline in the html head section.
 */
add_filter( 'embed_javascript_file_content_handles', 'my_embed_javascript_file_content_handles' );
function my_embed_javascript_file_content_handles( $handles ) {
	$scripts = [ 'js-detection' ];

	return array_merge( $handles, $scripts );
}
`


== Installation ==
1. Upload `embed-javascript-file-content.zip` to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the `embed_javascript_file_content_handles` filter in your theme or plugin.
4. You’re done!

== Changelog ==

= 1.0 =
* First release
