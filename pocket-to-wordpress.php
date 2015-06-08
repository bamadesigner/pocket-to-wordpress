<?php

/**
 * Plugin Name:       Pocket to WordPress
 * Plugin URI:        https://wordpress.org/plugins/ @TODO update
 * Description:       Moves Pocket stuff to WordPress. @TODO update
 * Version:           1.0
 * Author:            Rachel Carden
 * Author URI:        http://bamadesigner.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       pocket-to-wp
 * Domain Path:       /languages @TODO setup
 */

/**
 * This adventure into the Pocket API was inspired
 * because I wanted to get all of my Pocket items
 * that were tagged "reading" and display them as
 * a reading list on my WordPress site.
 *
 * Kudos to Rob Neu (@rob_neu) for some helpful tweaks.
 *
 * This code will cache the item data in a transient
 * at a default time of 2 hours. You can tweak
 * this time with the 'store_items' argument.
 *
 * You'll need a Pocket consumer key and access token
 * in order to use their API. Visit https://getpocket.com/developer/
 * to register an app and get a key.
 *
 * You can then use https://github.com/jshawl/pocket-oauth-php
 * to get your access token.
 *
 * If you don't mind trusting some random site, you can use
 * http://reader.fxneumann.de/plugins/oneclickpocket/auth.php
 * to get your access token a lot quicker.
 *
 * Then use the shortcode [pocket_items] or the function
 * get_pocket_items_html() to display your pocket items.
 *
 * Or you can use get_pocket_items() to retrieve the item
 * data and display as you like.
 */

add_shortcode( 'pocket_items', 'get_pocket_items_html' );
/**
 * Shortcode and function to print your Pocket items.
 *
 * Pass a 'tag' to get category-specific Pocket items.
 * It seems they only allow one tag at a time.
 *
 * @TODO: Get your Pocket authentication info.
 *
 * You'll need a Pocket consumer key and access token
 * in order to use their API. Visit https://getpocket.com/developer/
 * to register an app and get a key.
 *
 * You can then use https://github.com/jshawl/pocket-oauth-php
 * to get your access token.
 *
 * You can get more info in their docs: https://getpocket.com/developer/docs/authentication
 */

/**
 * Returns markup for all pocket items.
 *
 * @access public
 * @param  array - $args - the arguments to create/format the list
 * @return string - $pocket_html - the items markup
 */
function get_pocket_items_html( $args = array() ) {

    // Handle the arguments
    $defaults = array(
        'key'           => null,
        'token'         => null,
        'tag'           => NULL,
        'store_items'   => '7200' // Will cache the item for 2 hours by default. Set to false for no cache.
    );
    extract( wp_parse_args( $args, $defaults ), EXTR_OVERWRITE );

    // Build html
    $pocket_html = NULL;

    // Get the items
    $pocket_items = get_pocket_items( shortcode_atts( $defaults, $args ) );

    // If we have no items
    if ( ! ( isset( $pocket_items ) && is_array( $pocket_items ) ) )
        return NULL;

    // Start building the HTML
    $pocket_html .= '<div class="pocket-items">';

    // Loop through each item
    foreach( $pocket_items as $item ) {
        $pocket_html .= get_pocket_item_html( $item );
    }

    $pocket_html .= '</div>';

    return $pocket_html;

}

/**
 * Returns markup for individual pocket items.
 *
 * @access public
 * @param  array - $item - the data for the item to be displayed
 * @return string - $pocket_html - the item's markup
 */
function get_pocket_item_html( $item ) {

    // Make sure it's formatted
    if ( ! isset( $item->is_formatted ) )
        $item = get_pocket_item_formatted_data( $item );

    // Make sure we at least have an ID and a title
    if ( ! ( isset( $item->ID ) && isset( $item->title ) ) )
        return null;

    // Start the item
    $pocket_html = '<div id="pocket-' . $item->ID . '" class="pocket-item' . ( $item->has_image && $item->image_src ? ' has-image' : NULL ) . '">';

    // Add image
    $pocket_html .= $item->has_image && $item->image_src ? '<div class="pocket-image" style="background-image: url(' . esc_url( $item->image_src ) . ');"></div>' : NULL;

    // Add the main stuff
    $pocket_html .= '<div class="pocket-main">';

    // Add the title
    $pocket_html .= '<h3 class="pocket-title">';

    // Add the URL
    if ( $item->url ) {
        $pocket_html .= '<a href="' . esc_url( $item->url ) . '" target="_blank">' . esc_html( $item->title ) . '</a>';
    } else {
        $pocket_html .= esc_html( $item->title );
    }

    // Close the title
    $pocket_html .= '</h3>';

    // Add meta data
    $pocket_html .= '<div class="pocket-meta">';

    // Show when it was added
    $pocket_html .= ! empty( $item->time_added ) ? '<span class="date-added meta-section">' . date( 'M\. j, Y', esc_attr( $item->time_added ) ) . '</span>' : NULL;

    // Only add the first author
    if ( $item->authors ) {

        foreach( $item->authors as $author ) {

            if ( isset( $author[ 'name' ] ) ) {

                $pocket_html .= '<span class="author meta-section">';

                // If we have a URL
                if ( isset( $author[ 'url' ] ) ) {
                    $pocket_html .= '<a href="' . esc_url( $author[ 'url' ] ) . '">' . esc_html( $author[ 'name' ] ) . '</a>';
                } else {
                    $pocket_html .= esc_html( $author[ 'name' ] );
                }

                $pocket_html .= '</span>';

                break;

            }

        }

    }

    // Show the word count
    $pocket_html .= $item->word_count > 0 ? '<span class="word-count meta-section">' . $item->word_count . ' words</span>' : NULL;

    $pocket_html .= '</div>';

    $pocket_html .= '<div class="pocket-excerpt">';

    // Add excerpt
    if ( isset( $item->excerpt ) && ! empty( $item->excerpt ) )
        $pocket_html .= wpautop( $item->excerpt, false );

    $pocket_html .= '</div>';

    // Add tag list
    if ( isset( $item->tags ) && ! empty( $item->tags ) ) {

        // Start the list
        $pocket_html .= '<ul class="pocket-item-tags">';

        foreach( $item->tags as $tag => $tag_info ) {
            $pocket_html .= '<li>' . esc_html( $tag ) . '</li>';
        }

        // End the list
        $pocket_html .= '</ul>';

    }

    $pocket_html .= '</div> <!-- .pocket-main -->';

    $pocket_html .= '</div><!-- #pocket-' . $item->ID . ' -->';

    return $pocket_html;
}

/**
 * Returns a formatted array of data about a given Pocket item.
 *
 * @access public
 * @param  array - $item - the item which holds the data we need to format
 * @return array - $data - the item's data attributes
 */
function get_pocket_item_formatted_data( $item ) {

    // Convert to object
    $item = (object) $item;

    // Create the data
    $data = (object) array(
        'ID'            => ! empty( $item->item_id )            ? $item->item_id        : null,
        'resolved_id'   => ! empty( $item->resolved_id )        ? $item->resolved_id    : null,
        'url'           => ! empty( $item->given_url )          ? $item->given_url      : null,
        'resolved_url'  => ! empty( $item->resolved_url )       ? $item->resolved_url   : null,
        'title'         => ! empty( $item->given_title )        ? $item->given_title    : null,
        'resolved_title'=> ! empty( $item->resolved_title )     ? $item->resolved_title : null,
        'time_added'    => ! empty( $item->time_added )         ? $item->time_added     : false,
        'time_updated'  => ! empty( $item->time_updated )       ? $item->time_updated   : false,
        'time_read'     => ! empty( $item->time_read )          ? $item->time_read      : false,
        'time_favorited'=> ! empty( $item->time_favorited )     ? $item->time_favorited : false,
        'favorite'      => ! empty( $item->favorite )           ? $item->favorite       : null,
        'status'        => ! empty( $item->status )             ? $item->status         : null,
        'word_count'    => ! empty( $item->word_count )         ? $item->word_count     : false,
        'authors'       => ! empty( $item->authors )            ? $item->authors        : false,
        'is_article'    => ! empty( $item->is_article )         ? $item->is_article     : false,
        'has_video'     => ! empty( $item->has_video )          ? $item->has_video      : false,
        'has_image'     => ! empty( $item->has_image )          ? $item->has_image      : false,
        'excerpt'       => ! empty( $item->excerpt )            ? $item->excerpt        : null,
        'tags'          => ! empty( $item->tags )               ? $item->tags           : false,
        'image_src'     => false,
        'images'        => false,
        'is_formatted'  => true,
    );

    // Make sure we have a title
    if ( empty( $data->title ) ) {
        if ( isset( $data->url ) ) {
            $data->title = $data->url;
        } else if ( isset( $data->resolved_url ) ) {
            $data->title = $data->resolved_url;
        }
    }

    // If we has_image, get the image src
    if ( $data->has_image && ! empty( $item->image['src'] ) ) {

        // Store image src
        $data->image_src = $item->image[ 'src' ];

        // Store images
        if ( ! empty( $item->images ) )
            $data->images = $item->images;
    }

    return $data;
}

/**
 * Get Pocket items using Pocket's API.
 *
 * @TODO: Get your Pocket authentication info.
 *
 * You'll need a Pocket consumer key and access token
 * in order to use their API. Visit https://getpocket.com/developer/
 * to register an app and get a key.
 *
 * You can then use https://github.com/jshawl/pocket-oauth-php
 * to get your access token.
 *
 * You can get more info in their docs: https://getpocket.com/developer/docs/authentication
 *
 * @access public
 * @param  $args - array - arguments to be passed to the API
 * @return array - the items, false if something went wrong
 */
function get_pocket_items( $args = array() ) {

    // Handle the arguments
    $defaults = array(
        'key'           => null,
        'token'         => null,
        'tag' 			=> NULL, // Pass a tag to get category-specific Pocket items. It seems they only allow one tag at a time.
        'count' 		=> 10,
        'store_items'	=> '7200' // Will cache the item for 2 hours by default. Set to false for no cache.
    );
    $args = wp_parse_args( $args, $defaults );

    // Bail if we don't have the required access token and consumer key data.
    if ( ! isset( $args[ 'key' ], $args[ 'token' ] ) ) {
        return false;
    }

    // Build request args
    $pocket_request_args = array(
        'consumer_key' 	=> $args[ 'key' ],
        'access_token' 	=> $args[ 'token' ],
        'tag'			=> $args[ 'tag' ],
        'detailType'	=> 'complete',
        'state'			=> 'all',
        'count'		    => $args[ 'count' ],
    );

    // If we're set to store the items...
    if ( $args[ 'store_items' ] !== false && $args[ 'store_items' ] > 0 ) {

        // See if we have data in our transient data and that it matches our args
        $pocket_transient_name = 'my_pocket_reading_list';
        $pocket_transient_args_name = 'my_pocket_reading_list_args';

        // If we have cached Pocket items...
        if ( ( $transient_pocket_items = get_transient( $pocket_transient_name ) )
            && $transient_pocket_items !== false ) {

            // Check the args to see if they match...
            if ( ( $transient_pocket_item_args = get_transient( $pocket_transient_args_name ) )
                && $transient_pocket_item_args == $pocket_request_args ) {

                // Return the cached Pocket items
                return $transient_pocket_items;

            }

        }

    }

    // Make our Pocket request
    $pocket_request = get_pocket_response( 'https://getpocket.com/v3/get', $pocket_request_args );

    // Bail if something went wrong with the request.
    if ( ! $pocket_request || empty( $pocket_request['list'] ) ) {
        return false;
    }

    // If we're set to store items, store the data and args for the set transient time
    if ( $args[ 'store_items' ] !== false && $args[ 'store_items' ] > 0 ) {

        set_transient( $pocket_transient_name, $pocket_request[ 'list' ], $args[ 'store_items' ] );
        set_transient( $pocket_transient_args_name, $pocket_request_args, $args[ 'store_items' ] );

    }

    // Return the items
    return $pocket_request[ 'list' ];
}

/**
 * Get a response from the Pocket API.
 *
 * @access public
 * @param  $url - string - the API endpoint URL
 * @param  $post - array - data to be passed to the API
 * @return mixed false if there's an error, otherwise an array of API data
 */
function get_pocket_response( $url, $post ) {

    // Get the response
    $response = wp_safe_remote_post( $url, array( 'body' => $post ) );

    // Check for an error
    if ( is_wp_error( $response ) ) {
        return false;
    }

    // Return the response
    return json_decode( wp_remote_retrieve_body( $response ), true );
}