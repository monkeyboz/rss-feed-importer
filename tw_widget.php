<?php
// Creating the widget 
class tw_widget extends WP_Widget {

    function __construct() {
        parent::__construct(
        // Base ID of your widget
        'tw_widget', 
        
        // Widget name will appear in UI
        __('TW RSS Feed Widget', 'tw_widget_domain'), 
        
        // Widget description
        array( 'description' => __( 'TW Rss Feed Widget', 'tw_widget_domain' ), ) 
        );
    }

    // Creating widget front-end
    // This is where the action happens
    public function widget( $args, $instance ) {
        $title = apply_filters( 'widget_title', $instance['title'] );
        // before and after widget arguments are defined by themes
        echo $args['before_widget'];
        if ( ! empty( $title ) )
        echo $args['before_title'] . $title . $args['after_title'];
        
        // This is where you run the code and display the output
        echo __( '', 'tw_widget_domain' );
        $string = '[feed_searches title_only="true" ';
        foreach($instance as $k=>$a){
        	$string .= $k.'="'.$a.'" ';
        }
        $string .= ']';
        echo do_shortcode($string);
        echo $args['after_widget'];
    }
		
    // Widget Backend 
    public function form( $instance ) {
        $fields = array('title','advertise','total_feeds');
        foreach($instance as $k=>$t){
            ${$k} = $t;
        }
        if ( isset( $instance[ 'title' ] ) ) {
            $title = $instance[ 'title' ];
        }
        else {
            $title = __( 'New title', 'tw_widget_domain' );
        }
        // Widget admin form
        ?>
            <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
            <input class="widefat" id="<?php echo $this->get_field_id( 'advertise' ); ?>" name="<?php echo $this->get_field_name('advertise'); ?>" type="checkbox" value="<?php echo esc_attr( $advertise ); ?>" />
            <label for="<?php echo $this->get_field_id('advertise'); ?>"><?php _e('Advertise (check to start advertisements)'); ?></label>
            <label for="<?php echo $this->get_field_id('total_feeds'); ?>"><?php _e('Total Feeds to Display'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id( 'total_feeds' ); ?>" name="<?php echo $this->get_field_name('total_feeds'); ?>" type="number" value="<?php echo esc_attr( $total_feeds ); ?>" />
            </p>
        <?php 
    }
	
    // Updating widget replacing old instances with new
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['advertise'] = ( ! empty( $new_instance['advertise'] ) ) ? strip_tags( $new_instance['advertise'] ) : '';
        $instance['total_feeds'] = ( ! empty( $new_instance['total_feeds'] ) ) ? strip_tags( $new_instance['total_feeds'] ) : '';
        return $instance;
    }
} // Class wpb_widget ends here
