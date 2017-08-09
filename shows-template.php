<?php
get_header('radio');


$days = array('Sundays', 'Mondays', 'Tuesdays', 'Wednesdays', 'Thursdays', 'Fridays', 'Saturdays');

// Start the loop
if (have_posts()) : while ( have_posts()) : the_post();
?>
<section class="desktop-article-masthead container">
  <h5>Show</h5>
  <h1><?php the_title(); ?></h1>
</section>
<main class="container">
  <article class="single">
    <div class="featured-image" style="background-image:url(<?php the_post_thumbnail_url( 'large' )?>)">
    </div>
    <h5>Show</h5>
    <h1><?php the_title(); ?></h1>
    <section class="meta-bar">
      <div>
        <?php smoke_share_buttons(get_the_ID()); ?>
        <span class="author"><?php echo get_the_terms( $post->ID, 'genre')[0]->name; ?></span>
      </div>
      <span class="time"><i class="fa fa-calendar"></i> <?php echo $days[get_post_custom( $post->ID )['tx_day'][0]] ?> at <?php echo get_post_custom( $post->ID )['tx_time'][0] . ':00'; ?></span>
    </section>
    <section class="content">
      <?php the_content(); ?>
    </section>


    <section class="recommended">
      <h2>Discover more shows</h2>
      <ul class="recommendations">
        <?php
        $counter = 0;
        $recommended = new WP_Query(array(
          'post_type' => 'shows',
        ));

        $big_post_id = get_the_ID();

        while ($recommended->have_posts()) : $recommended->the_post();
        // Skip over the big post, if it appears
        if ($big_post_id === get_the_ID()) { continue; };
        // Stop looping after third post
        if ($counter>2) { break; };
        ?>
        <li>
          <a href="<?php the_permalink(); ?>">
            <div class="featured-image" style="background-image:url(<?php the_post_thumbnail_url( 'large' )?>)"></div>
            <h4><?php the_title(); ?></h4>
          </a>
        </li>
        <?php
        $counter++;
        endwhile; wp_reset_postdata(); ?>
      </ul>
    </section>

  </article>

  <aside class="sidebar">
    <?php get_sidebar(); ?>
  </aside>
<?php
// What if there are no posts?
endwhile; else :
// End the loop
endif; ?>




</main>
<?php

get_footer();
