<?php

class Slick_FilmStrip_Widget extends WP_Widget
{
    /**
     * based on https://www.wpexplorer.com/create-widget-plugin-wordpress/

     * The widget simply creates a container that the Slick script will populate
     * after the page loads.
     */

    public function __construct()
    {
        parent::__construct(
            'slick_filmstrip_widget',
            __('Slick FilmStrip Widget', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'title' => '',
        );
        extract(wp_parse_args((array) $instance, $defaults));
        ?>
    <?php // Widget Title ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <?php
}

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = isset($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        extract($args);
        $title = isset($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        echo '<div class="slick-widget slick-film-strip">';
        echo '</div>';
        echo $after_widget;
    }
}

class Slick_Game_Widget extends WP_Widget
{
    /**
     * based on https://www.wpexplorer.com/create-widget-plugin-wordpress/

     * The widget simply creates a container that the Slick script will populate
     * after the page loads.
     */

    public function __construct()
    {
        parent::__construct(
            'slick_game_widget',
            __('Slick Game Widget', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'title' => '',
        );
        extract(wp_parse_args((array) $instance, $defaults));
        ?>
    <?php // Widget Title ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <?php
}

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = isset($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        extract($args);
        $title = isset($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        echo '<div class="slick-widget slick-game-panel">';
        echo '</div>';
        echo $after_widget;
    }
}

class Slick_Grid_Widget extends WP_Widget
{
    /**
     * based on https://www.wpexplorer.com/create-widget-plugin-wordpress/

     * The widget simply creates a container that the Slick script will populate
     * after the page loads with the Slick grid widget.
     */

    public function __construct()
    {
        parent::__construct(
            'slick_grid_widget',
            __('Slick Grid Widget', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'title' => '',
            'id' => '',
        );
        extract(wp_parse_args((array) $instance, $defaults));
        ?>
    <?php // Widget Title ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('id')); ?>"><?php _e('Slickstream ID', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('id')); ?>" name="<?php echo esc_attr($this->get_field_name('id')); ?>" type="text" value="<?php echo esc_attr($id); ?>" />
    </p>
    <?php
}

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = isset($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        $instance['id'] = isset($new_instance['id']) ? wp_strip_all_tags($new_instance['id']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        extract($args);
        $title = isset($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        $id = $instance['id'];
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        if (isset($id)) {
            echo '<div class="slick-content-grid" data-config="' . trim($id) . '"></div>' . "\n";
        } else {
            echo '<div class="slick-content-grid"></div>' . "\n";
        }
        echo $after_widget;
    }
}

class Slick_Story_Player_Widget extends WP_Widget
{
    /**
     * based on https://www.wpexplorer.com/create-widget-plugin-wordpress/

     * The widget simply creates a container that the Slick script will populate
     * after the page loads with the Slick grid widget.
     */

    public function __construct()
    {
        parent::__construct(
            'slick_story_player_widget',
            __('Slick Story Player Widget', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'title' => '',
            'webstoryurl' => '',
        );
        extract(wp_parse_args((array) $instance, $defaults));
        ?>
    <?php // Widget Title ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('webstoryurl')); ?>"><?php _e('Web Story URL', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('webstoryurl')); ?>" name="<?php echo esc_attr($this->get_field_name('webstoryurl')); ?>" type="text" value="<?php echo esc_attr($webstoryurl); ?>" />
    </p>
    <?php
}

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = isset($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        $instance['webstoryurl'] = isset($new_instance['webstoryurl']) ? wp_strip_all_tags($new_instance['webstoryurl']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        extract($args);
        $title = isset($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        $webstoryurl = $instance['webstoryurl'];
        if (!empty($webstoryurl)) {
            echo $before_widget;
            if ($title) {
                echo $before_title . $title . $after_title;
            }
            $storyId = $this->getStoryIdFromUrl($webstoryurl);
            echo '<slick-webstory-player id="story-' . $storyId . '">' . "\n";
            echo '  <a href="' . $webstoryurl . '"></a>' . "\n";
            echo '</slick-webstory-player>' . "\n";
            echo $after_widget;
        }
    }

    public function getStoryIdFromUrl($url)
    {
        if (strpos($url, 'slickstream.com') !== false && strpos($url, '/d/webstory') !== false) {
            $parts = explode('/', $url);
            if (count($parts) > 1) {
                if (!empty($parts[count($parts) - 1])) {
                    return $parts[count($parts) - 1];
                }
            }
        }
        return substr(hash('md5', $url), 0, 5);
    }
}

class Slick_Story_Carousel_Widget extends WP_Widget
{
    /**
     * based on https://www.wpexplorer.com/create-widget-plugin-wordpress/

     * The widget simply creates a container that the Slick script will populate
     * after the page loads with the Slick grid widget.
     */

    public function __construct()
    {
        parent::__construct(
            'slick_story_carousel_widget',
            __('Slick Story Carousel Widget', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'title' => '',
        );
        extract(wp_parse_args((array) $instance, $defaults));
        ?>
    <?php // Widget Title ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <?php
}

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = isset($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        extract($args);
        $title = isset($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        echo '<style>.slick-story-carousel {min-height: 324px;} @media (max-width: 600px) {.slick-story-carousel {min-height: 224px;}}</style>' . "\n";
        echo '<div class="slick-story-carousel"></div>' . "\n";
        echo $after_widget;
    }
}

class Slick_Story_Explorer_Widget extends WP_Widget
{
    /**
     * based on https://www.wpexplorer.com/create-widget-plugin-wordpress/

     * The widget simply creates a container that the Slick script will populate
     * after the page loads with the Slick grid widget.
     */

    public function __construct()
    {
        parent::__construct(
            'slick_story_explorer_widget',
            __('Slick Story Explorer Widget', 'text_domain'),
            array('customize_selective_refresh' => true)
        );
    }

    public function form($instance)
    {
        $defaults = array(
            'title' => '',
        );
        extract(wp_parse_args((array) $instance, $defaults));
        ?>
    <?php // Widget Title ?>
    <p>
      <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php _e('Widget Title', 'text_domain');?></label>
      <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
    </p>
    <?php
}

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = isset($new_instance['title']) ? wp_strip_all_tags($new_instance['title']) : '';
        return $instance;
    }

    public function widget($args, $instance)
    {
        extract($args);
        $title = isset($instance['title']) ? apply_filters('widget_title', $instance['title']) : '';
        echo $before_widget;
        if ($title) {
            echo $before_title . $title . $after_title;
        }
        echo '<div class="slick-story-explorer"></div>' . "\n";
        echo $after_widget;
    }
}

function register_slick_widgets()
{
    register_widget('Slick_FilmStrip_Widget');
    register_widget('Slick_Game_Widget');
    register_widget('Slick_Grid_Widget');
    register_widget('Slick_Story_Player_Widget');
    register_widget('Slick_Story_Carousel_Widget');
    register_widget('Slick_Story_Explorer_Widget');
}

add_action('widgets_init', 'register_slick_widgets');
