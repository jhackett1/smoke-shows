<?php
/*
Plugin Name: Smoke Shows
Plugin URI: https://github.com/jhackett1/smoke-shows
Description: Adds show profile and scheduling functionality for Smoke Radio, and creates new REST API endpoints for consumption by site widgets and the app.
Version: 1.0.0
Author: Joshua Hackett
Author URI: http://joshuahackett.com
*/

// Flush URL rewrites on activation
function smoke_rewrite_flush() {
    register_shows();
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'smoke_rewrite_flush' );

// Create a custom post type for shows
function register_shows(){
  $labels = array(
    'name'               => _x( 'Shows', 'post type general name'),
    'singular_name'      => _x( 'Show', 'post type singular name'),
    'menu_name'          => _x( 'Shows', 'admin menu'),
    'name_admin_bar'     => _x( 'Show', 'add new on admin bar'),
    'add_new'            => _x( 'Add New', 'show'),
    'add_new_item'       => __( 'Add New Show'),
    'new_item'           => __( 'New Show'),
    'edit_item'          => __( 'Edit Show'),
    'view_item'          => __( 'View Show'),
    'all_items'          => __( 'All Shows'),
    'search_items'       => __( 'Search Shows'),
    'parent_item_colon'  => __( 'Parent Shows:'),
    'not_found'          => __( 'No shows found.'),
    'not_found_in_trash' => __( 'No shows found in Trash.')
  );
  $args = array(
    'labels'  => $labels,
    'public' => true,
    'show_in_rest' => true,
    'menu_icon'   => 'dashicons-album',
    'query_var'          => true,
    'rewrite'            => array( 'slug' => 'show' ),
    'capability_type'    => 'post',
    'has_archive'        => true,
    'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' )
  );
  register_post_type( 'shows', $args );
};
add_action('init', 'register_shows');



// Create a genre taxonomy
function genre_init() {
  $labels = array(
		'name'              => _x( 'Genres', 'taxonomy general name', 'textdomain' ),
		'singular_name'     => _x( 'Genre', 'taxonomy singular name', 'textdomain' ),
		'search_items'      => __( 'Search Genres', 'textdomain' ),
		'all_items'         => __( 'All Genres', 'textdomain' ),
		'parent_item'       => __( 'Parent Genre', 'textdomain' ),
		'parent_item_colon' => __( 'Parent Genre:', 'textdomain' ),
		'edit_item'         => __( 'Edit Genre', 'textdomain' ),
		'update_item'       => __( 'Update Genre', 'textdomain' ),
		'add_new_item'      => __( 'Add New Genre', 'textdomain' ),
		'new_item_name'     => __( 'New Genre Name', 'textdomain' ),
		'menu_name'         => __( 'Genre', 'textdomain' ),
	);
	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'genre' ),
	);
	register_taxonomy( 'genre', array( 'shows' ), $args );
}
add_action( 'init', 'genre_init' );





// Add custom fields for TX day and time
add_action( 'add_meta_boxes', 'smoke_transmission_box_setup' );
function smoke_transmission_box_setup(){
	add_meta_box( 'smoke_transmission', 'Transmission', 'smoke_transmission_content', 'shows', 'side', 'high');
}
// Callback function to fill the meta box with form input content, passing in the post object
function smoke_transmission_content( $post ){
	// Fetch all post meta data and save as an array var
	$values = get_post_custom( $post->ID );
	// Save current values of particular meta keys as variables for display
	$tx_day = isset( $values['tx_day'] ) ? esc_attr( $values['tx_day'][0] ) : "";
	$tx_time = isset( $values['tx_time'] ) ? esc_attr( $values['tx_time'][0] ) : "";
	//What a nonce
	wp_nonce_field( 'smoke_post_options_nonce', 'meta_box_nonce' );
	// Display input fields, using variables above to show current values
    ?>
		<p>
      <label for="tx_day">When does this show air?</label><br/>
      <select name="tx_day" id="tx_day">
			    <option value="0" <?php selected( $tx_day, '0' ); ?>>Sundays</option>
          <option value="1" <?php selected( $tx_day, '1' ); ?>>Mondays</option>
			    <option value="2" <?php selected( $tx_day, '2' ); ?>>Tuesdays</option>
			    <option value="3" <?php selected( $tx_day, '3' ); ?>>Wednesdays</option>
          <option value="4" <?php selected( $tx_day, '4' ); ?>>Thursdays</option>
			    <option value="5" <?php selected( $tx_day, '5' ); ?>>Fridays</option>
			    <option value="6" <?php selected( $tx_day, '6' ); ?>>Saturdays</option>
      </select> at <input type="number" name="tx_time" id="tx_time" min="00" max="23" value="<?php echo get_post_meta( $post->ID, 'tx_time' )[0] ?>">:00
		</p>
    <?php
}
// Save the content
add_action( 'save_post', 'transmission_save' );
function transmission_save( $post_id ){
	// If this is an autosave, do nothing
	if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
	// Check user permissions before proceeding
	if( !current_user_can( 'edit_post' ) ) return;
  $allowed = array(
      'a' => array(
          'href' => array()
      )
  );
	// Save tx fields
	if( isset( $_POST['tx_day'] ) )
      update_post_meta( $post_id, 'tx_day', esc_attr( $_POST['tx_day'] ) );
  if( isset( $_POST['tx_time'] ) )
      update_post_meta( $post_id, 'tx_time', esc_attr( $_POST['tx_time'] ) );
}

// Improve usability by adding columns for TX info to the show admin screen
function tx_columns_head($defaults) {
    $defaults['tx_day'] = 'TX Day';
    $defaults['tx_time'] = 'TX Time';
    return $defaults;
}
add_filter('manage_shows_posts_columns', 'tx_columns_head');

// Populate it with content
function tx_columns_content($column_name, $post_ID) {
    if ($column_name == 'tx_day') {
			if( isset(get_post_custom( $post_ID )['tx_day']) ){
        // Display the relevant day for the number returned
        switch (get_post_custom( $post_ID )['tx_day'][0]) {
            case 0:
                echo 'Sundays';
                break;
            case 1:
                echo 'Mondays';
                break;
            case 2:
                echo 'Tuesdays';
                break;
            case 3:
                echo 'Wednesdays';
                break;
            case 4:
                echo 'Thursdays';
                break;
            case 5:
                echo 'Fridays';
                break;
            case 6:
                echo 'Saturdays';
                break;
            default:
                echo 'Not set';
        }
			} else {
				echo 'Not set';
			}
    }
    if ($column_name == 'tx_time') {
			// Check whether the meta is set
			if( isset(get_post_custom( $post_ID )['tx_time']) ){
				echo get_post_custom( $post_ID )['tx_time'][0] . ':00';
			} else {
				echo 'Not set';
			}
    }
}
add_action('manage_shows_posts_custom_column', 'tx_columns_content', 10, 2);


// Create new API routes to return the schedule and now playing info
add_action( 'rest_api_init', function () {
  register_rest_route( 'shows', '/schedule/', array(
    'methods' => 'GET',
    'callback' => 'get_schedule_route_response',
  ) );
} );

// Fill the two newly created routes with the appropriate content
function get_schedule_route_response(){
  // A blank array to store the response, ordered by day
  $response = array();
  // For every value of day, collect posts and push them into the array
  for ($day=0; $day < 7; $day++) {
    $today_posts = get_posts( array(
        'post_type'    => 'shows',
        // Order from earliest to latest
        'order'     => 'ASC',
        'meta_key' => 'tx_time',
        'orderby'   => 'meta_value',
        // Fetch only shows of the specified day
        'meta_query' => array(array('key' => 'tx_day','value' => $day))
    ) );
    // An empty array to store processed posts for the specified day
    $processed_posts = array();
    foreach ( $today_posts as $post ) : setup_postdata( $post );
      // How do we format a single show?
      $processed_show = array(
        'title' => isset( $post->post_title ) ? $post->post_title : '',
        'profile' => isset( $post->post_content ) ? $post->post_content : '',
        'desc' => isset( $post->post_excerpt ) ? $post->post_excerpt : '',
        'permalink' => get_permalink($post),
        'genre' => isset(get_the_terms( $post->ID, 'genre')[0]->name) ? get_the_terms( $post->ID, 'genre')[0]->name : '',
        'tx_day' => get_post_custom( $post->ID )['tx_day'][0],
        'tx_time' => get_post_custom( $post->ID )['tx_time'][0] . '00',
        'icon_full' => get_the_post_thumbnail_url($post),
        'icon_thumb' => get_the_post_thumbnail_url($post, array(200,200))
      );
      // Push this show into the array of processed posts for the specified day
      array_push($processed_posts, $processed_show);
    endforeach;
    wp_reset_postdata();
    // And push the current day of posts into the array
    array_push($response, $processed_posts);
  }
  // Pass out the finished array to the API
  return $response;
}


// Create new API routes to return the schedule and now playing info
add_action( 'rest_api_init', function () {
  register_rest_route( 'shows', '/now_playing/', array(
    'methods' => 'GET',
    'callback' => 'get_now_playing_route_response',
  ) );
} );

// Fill the two newly created routes with the appropriate content
function get_now_playing_route_response(){
  // Create an empty response scaffold
  $response = array(
    'success' => 0,
    'show' => ''
  );
  // Grab all shows
  $all_shows = get_posts( array(
      'post_type'    => 'shows',
  ) );
  // Grab the current show, if there is one
  $current_show_raw = array_filter($all_shows, function($post){
    // Grab the current hour (00-23) and day (0-6, with 0=Sunday)
    date_default_timezone_set('Europe/London');
    $currentHour = date("H");
    $currentDay = date("w");

    // Test each show against the current hour and day
    return get_post_custom( $post->ID )['tx_time'][0] == $currentHour && get_post_custom( $post->ID )['tx_day'][0] == $currentDay;
  });



  // Only continue if there is a show on right now
  if (!empty($current_show_raw)) {
    $response['success'] = 1;
    // Process the WP post object into a nicer format
    $current_show_processed = array(
      'title' => isset( $current_show_raw[0]->post_title ) ? $current_show_raw[0]->post_title : '',
      'profile' => isset( $current_show_raw[0]->post_content ) ? $current_show_raw[0]->post_content : '',
      'desc' => isset( $current_show_raw[0]->post_excerpt ) ? $current_show_raw[0]->post_excerpt : '',
      'permalink' => get_permalink($current_show_raw[0]),
      'genre' => isset(get_the_terms( $current_show_raw[0]->ID, 'genre')[0]->name) ? get_the_terms( $current_show_raw[0]->ID, 'genre')[0]->name : '',
      'tx_day' => get_post_custom( $current_show_raw[0]->ID )['tx_day'][0],
      'tx_time' => get_post_custom( $current_show_raw[0]->ID )['tx_time'][0] . '00',
      'icon_full' => get_the_post_thumbnail_url($current_show_raw[0]),
      'icon_thumb' => get_the_post_thumbnail_url($current_show_raw[0], array(200,200))
    );
    // Finally, pass the processed show into the API response
    $response['show'] = $current_show_processed;
  }
  // Pass out the finished array to the API
  return $response;
}


// Register the template for the custom post type
add_filter('single_template', 'shows_custom_template');
function shows_custom_template($single) {
  global $wp_query, $post;
  /* Checks for single template by post type */
  if ( $post->post_type == 'shows' ) {
      if ( file_exists( plugin_dir_path( __FILE__ ) . '/shows-template.php' ) ) {
          return plugin_dir_path( __FILE__ ) . '/shows-template.php';
      }
  }
  return $single;
}
