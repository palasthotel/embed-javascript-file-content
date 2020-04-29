<?php

/**
 * Plugin Name: Embed JavaScript File Content
 * Description: Boosts performance of critical short JavaScript files by embedding their code instead of linking to files. Script positions and extra scripts are preserved.
 * Version: 1.0
 * Author: Palasthotel <rezeption@palasthotel.de> (Kim Meyer)
 * Author URI: https://palasthotel.de
 */

namespace EmbedJavaScriptFileContent;

use DOMDocument;

// If this file is called directly, abort.
if (!defined('ABSPATH')) {
    die();
}

/**
 * List of all filter hooks of this plugin
 */
const FILTER_HANDLES = 'embed_javascript_file_content_handles';

/**
 * Echo content of scripts being returned by FILTER_HANDLES hook instead of
 * linking to their source file.
 */
function script_loader_tag($tag, $handle, $src)
{
    $handles = apply_filters(FILTER_HANDLES, []);
    if (!in_array($handle, $handles)) {
        return $tag;
    }

    // Try to get server file path from script url.
    $script_path = get_server_path_from_url($src);
    if ($script_path === false) {
        return $tag;
    }

    // Try to get file content.
    $script_content = file_get_contents($script_path);
    if ($script_content === false) {
        return $tag;
    }
    // It’s too late to use `print_extra_script` or traverse `WP_Scripts`
    // object. Maybe there are already some other modifications made, so we
    // prefer to keep the final html output as untouched as possible.
    $html = replace_src_with_content($src, $script_content, $tag);
    return $html;
}
add_filter('script_loader_tag', __NAMESPACE__ . '\script_loader_tag', 20, 3);

/**
 * Helper function:
 * Parses string of $html code and returns all script tags as html string,
 * replacing the src attribute for the script, if src equals $src,
 * with given $content.
 *
 * @param String $src JavaScript src attribute to be removed
 * @param String $content JavaScript code to be filled inside the former
 * `<script>` tag with src attribute $src
 * @param String $html haystack html code
 * @return String
 */
function replace_src_with_content($src, $content, $html)
{
    $html_doc = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>_</title></head><body>$html</body></html>";
    $dom = DOMDocument::loadHTML($html_doc);
    $dom_scripts = $dom->getElementsByTagName('script');
    if (empty($dom_scripts)) {
        return '';
    }
    $scripts = [];
    foreach ($dom_scripts as $dom_script) {
        if ($dom_script->getAttribute('src') === $src) {
            $dom_text = $dom->createTextNode($content);
            $dom_script->appendChild($dom_text);
            $dom_script->removeAttribute('src');
            $dom_script->removeAttribute('async');
            $dom_script->removeAttribute('defer');
        }
        $scripts[] = $dom->saveHTML($dom_script);
    }
    $result = implode(PHP_EOL, $scripts) . PHP_EOL;
    return $result;
}

/**
 * Helper function:
 * Parse $url and return server path, if file exists.
 *
 * @param $url Absolute or relative
 * @return String|false Server path or false, if not found
 */
function get_server_path_from_url($url)
{
    // First we need some information about our own server:
    // Split home URL into pieces and resemble them into the base URL,
    // because `get_home_url()` might also contain sub-paths and we need
    // the root.
    $wp_base_url_parsed = parse_url(get_home_url());
    if ($wp_base_url_parsed === false) {
        return false;
    }
    $wp_base_url = sprintf(
        '%s://%s%s',
        $wp_base_url_parsed['scheme'],
        $wp_base_url_parsed['host'],
        $wp_base_url_parsed['port'] ? ':' . $wp_base_url_parsed['port'] : ''
    );

    // `parse_url` cannot use protocol relatives starting with `//`,
    // so we add the scheme of our server.
    if (mb_strpos($url, '//') === 0) {
        $url = sprintf('%s:%s', $wp_base_url_parsed['scheme'], $url);
    }

    // Parse URL for further examination.
    $parsed_url = parse_url($url);

    // No valid URL or no valid path?
    if ($parsed_url === false || empty($parsed_url['path'])) {
        return false;
    }

    // Make "get_home_path()" function callable on frontend
    if (!is_admin()) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    // Compare WordPress base URL with beginning of given $url.
    if (!empty($parsed_url['host']) && mb_strpos($url, $wp_base_url) !== 0) {
        // No match = other server.
        return false;
    }

    // We have a relative path on our own site, beginning with a slash.
    $guessed_path = untrailingslashit(get_home_path()) . $parsed_url['path'];
    if (!file_exists($guessed_path)) {
        return false;
    }

    return $guessed_path;
}
