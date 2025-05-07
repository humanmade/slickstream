<?php 
namespace Slickstream;
/*
 * Plugin Name:       Slickstream Search and Engagement
 * Plugin URI:        https://slickstream.com/
 * Description:       For use with Slickstream's cloud service and widgets to increase visitor engagement
 * Version:           2.0.3
 * Requires at least: 5.2.0
 * Requires PHP:      7.4.0
 * Author:            Slickstream
 * Author URI:        https://slickstream.com
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       slick-engagement
 */

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */
function SlickstreamPluginInit(): void {
    $minimumRequiredPhpVersion = '7.4.0';

    if (version_compare((string) phpversion(), $minimumRequiredPhpVersion) < 0) {
        add_action('admin_notices', function() use ($minimumRequiredPhpVersion) {
            echo '<div class="updated fade">' .
            __('Error: plugin "Slickstream Engagement" requires a newer version of PHP to run properly.', 'slick-engagement') .
            '<br/>' . __('Minimum version of PHP required: ', 'slick-engagement') . '<strong>' . $minimumRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'slick-engagement') . '<strong>' . phpversion() . '</strong>' .
            '</div>';
        });
        return;
    }

    require_once 'SlickEngagement_Init.php';
    \SlickEngagement_init();
}

SlickstreamPluginInit();
