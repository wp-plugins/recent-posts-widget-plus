<?php
/*
Plugin Name: Recent Posts Widget Plus
Plugin URI: http://vanderwijk.com
Description: A plugin that adds a widget that lists your most recent posts with excerpts. The number of posts and the character limit of the excerpts are configurable.
Version: 1.1
Author: Johan van der Wijk
Author URI: http://vanderwijk.nl
License: GPL2

Release notes: Version 1.1 now also works for custom post types

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Custom rpwp_excerpt funtion that allows to limit the number of characters
function rpwp_excerpt( $count ) {
	$permalink = get_permalink();
	$excerpt = get_the_content();
	$excerpt = strip_tags( $excerpt );
	$excerpt = substr( $excerpt, 0, $count );
	$excerpt = substr( $excerpt, 0, strripos( $excerpt, " " ));
	$excerpt = $excerpt.'<a href="'.$permalink.'">...</a>';
	return $excerpt;
}

class RecentPostsWidgetPlus extends WP_Widget {

	function RecentPostsWidgetPlus() {
		$widget_ops = array( 'classname' => 'recent-posts-plus', 'description' => __( 'The most recent posts on your site with excerpts' ) );
		$this -> WP_Widget( 'RecentPostsWidgetPlus', __( 'Recent Posts Widget Plus' ), $widget_ops );
	}

	function widget( $args, $instance ) {
		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance[ 'title' ] ) ? __( 'Recent Posts' ) : $instance[ 'title' ] );

		echo $before_widget;
		echo $before_title . $title . $after_title; ?>

		<dl>
		<?php 
		// Get the recent posts
		$q = 'showposts=' . $instance[ 'numposts' ];
		if ( !empty( $instance[ 'post_type' ] )) $q .= '&post_type=' . $instance[ 'post_type' ];
		if ( !empty( $instance[ 'cat' ] )) $q .= '&cat=' . $instance[ 'cat' ];
		if ( !empty( $instance[ 'tag' ] )) $q .= '&tag=' . $instance[ 'tag' ];
		query_posts( $q  );

		while (have_posts()) : the_post(); ?>
			<dt>
				<a href="<?php the_permalink(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a>
			</dt>
			<dd>
				<?php echo rpwp_excerpt( $instance[ 'characters' ] ); ?>
			</dd>
		<?php endwhile; ?>
		</dl>

		<?php if( $instance[ 'linkurl' ] !="" ) { ?>
			<a href="<?php echo $instance[ 'linkurl' ]; ?>" class="morelink"><?php echo $instance[ 'linktext' ]; ?></a>
		<?php } ?>

		<?php
		echo $after_widget;
		wp_reset_query();
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance[ 'title' ] = strip_tags( $new_instance[ 'title' ] );
		$instance[ 'numposts' ] = $new_instance[ 'numposts' ];
		$instance[ 'characters' ] = $new_instance[ 'characters' ];
		$instance[ 'post_type' ] = $new_instance[ 'post_type' ];
		$instance[ 'cat' ] = $new_instance[ 'cat' ];
		$instance[ 'tag' ] = $new_instance[ 'tag' ];
		$instance[ 'linktext' ] = $new_instance[ 'linktext' ];
		$instance[ 'linkurl' ] = $new_instance[ 'linkurl' ];
		return $instance;
	}

	function form( $instance ) {
		// Widget defaults
		$instance = wp_parse_args( (array) $instance, array( 
			'title' => 'Recent Posts',
			'numposts' => 5,
			'characters' => 100,
			'post_type' => '',
			'cat' => 0,
			'tag' => '',
			'linktext' => '',
			'linkurl' => ''
		)); ?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $instance[ 'title' ]; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'numposts' ); ?>"><?php _e( 'Number of posts to show:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'numposts' ); ?>" name="<?php echo $this->get_field_name( 'numposts' ); ?>" type="text" value="<?php echo $instance[ 'numposts' ]; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'characters' ); ?>"><?php _e( 'Excerpt length in number of characters:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'characters' ); ?>" name="<?php echo $this->get_field_name( 'characters' ); ?>" type="text" value="<?php echo $instance[ 'characters' ]; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'post_type' ); ?>"><?php _e( 'Custom post type:' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'post_type' ); ?>" name="<?php echo $this->get_field_name( 'post_type' ); ?>">
			<option value=""><?php echo __( 'None' ) . ' (' . __( 'Posts' ) . ')'; ?></option>
			<?php
				$args = array(
					'public' => true,
					'_builtin' => false
				);
				$output = 'names'; // names or objects, note names is the default
				$operator = 'and'; // 'and' or 'or'
				$post_types = get_post_types( $args, $output, $operator );
				foreach ( $post_types  as $post_type ) {
					echo '<option value="' . $post_type . '"' . selected( $instance[ 'post_type' ], $post_type ). '>' . $post_type . '</option>';
				}
			?>
			</select>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'cat' ); ?>"><?php _e( 'Limit to category: ' ); ?>
			<?php wp_dropdown_categories(array( 'name' => $this->get_field_name( 'cat' ), 'show_option_all' => __( 'None (all categories)' ), 'hide_empty'=>0, 'hierarchical'=>1, 'selected'=>$instance[ 'cat' ] ) ); ?></label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'tag' ); ?>"><?php _e( 'Limit to tags:' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'tag' ); ?>" name="<?php echo $this->get_field_name( 'tag' ); ?>" type="text" value="<?php echo $instance[ 'tag' ]; ?>" />
			<br /><small><?php _e( 'Enter post tags separated by commas (\'cat,dog\' )' ); ?></small>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'linktext' ); ?>"><?php _e( 'Link text:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'linktext' ); ?>" name="<?php echo $this->get_field_name( 'linktext' ); ?>" type="text" value="<?php echo $instance[ 'linktext' ]; ?>" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'linkurl' ); ?>"><?php _e( 'URL:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'linkurl' ); ?>" name="<?php echo $this->get_field_name( 'linkurl' ); ?>" type="text" value="<?php echo $instance[ 'linkurl' ]; ?>" />
		</p>

		<?php
	}
}

function recent_posts_widget_plus_init() {
	register_widget( 'RecentPostsWidgetPlus' );
}

add_action( 'widgets_init', 'recent_posts_widget_plus_init' ); ?>