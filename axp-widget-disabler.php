<?php /*

**************************************************************************

Plugin Name:  AXP Widget Disabler
Plugin URI:   https://github.com/axp-dev/axp-widget-disabler
Description:  The plugin allows you to disable certain widgets
Version:      1.0.0
Author:       Alexander Pushkarev <axp-dev@yandex.com>
Author URI:   https://github.com/axp-dev
Text Domain:  axp-widget-disabler
License:      GPLv2 or later


This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**************************************************************************/


class AXP_Widget_Disabler {
    public $menu_slug;
    public $fields;
    public $textdomain;

    function __construct() {
        $this->menu_slug        = 'axp-widget-disabler';
        $this->fields           = 'axp-widget-disabler-fields';
        $this->widget_error     = array();

        add_action( 'plugins_loaded',       array( &$this, 'init_textdomain' ));
        add_action( 'admin_menu',           array( &$this, 'register_menu' ) );
        add_action( 'admin_init',           array( &$this, 'register_settings' )  );
        add_action( 'widgets_init',         array( &$this, 'remove_widgets' ), 12 );
    }

    public function init_textdomain() {
        load_plugin_textdomain( 'axp-widget-disabler', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    public function remove_widgets() {
        if ( $widgets = get_option( $this->fields ) ) {
            foreach ( $widgets as $key => $state ) {
                unregister_widget($key);
            }
        }
    }

    public function register_menu() {
        add_options_page(
            __('Widget Disabler Settings', 'axp-widget-disabler'),
            __('Widget Disabler', 'axp-widget-disabler'),
            'manage_options',
            $this->menu_slug,
            array(&$this, 'render_page_settings')
        );
    }

    public function register_settings() {
        register_setting($this->fields, $this->fields);

        add_settings_section(
            'widget_list',
            __('Widgets', 'axp-widget-disabler'),
            null,
            $this->menu_slug
        );

        foreach ( $this->get_widget_list() as $widget ) {
            add_settings_field(
                $widget['id'],
                $widget['title'],
                array( $this, 'render_settings_fields' ),
                $this->menu_slug, 'widget_list',
                array(
                    'type'      => 'checkbox',
                    'id'        => $widget['id'],
                    'desc'      => $widget['description'],
                )
            );
        }
    }

    public function get_widget_list() {
        $result = array();
        $widgets = $this->get_extends_number('WP_Widget');

        if ( empty ( $widgets ) )
            return $result;

        foreach ($widgets as $key => $value) {
            $class = get_class($widgets[$key]);
            $id = explode('\\', $class);

            if ( count($id) == 1 ) {
                $result[] = array(
                    'id'            => end($id),
                    'class'         => $class,
                    'title'         => $value->name,
                    'description'   => $value->widget_options['description'],
                );
            } else {
                $this->widget_error[] = end($id);
            }
        }

        return $result;
    }

    public function render_settings_fields( $arguments ) {
        extract( $arguments );

        $option_name = $this->fields;
        $o = get_option( $option_name );

        switch ( $type ) {
            case 'text':
                $o[$id] = esc_attr( stripslashes($o[$id]) );
                echo "<input class='regular-text' type='text' id='$id' name='" . $option_name . "[$id]' value='$o[$id]' />";
                echo ($desc != '') ? "<p class='description'>$desc</p>" : "";
                break;

            case 'checkbox':
                $checked = @($o[$id] == 'on') ? " checked='checked'" :  '';
                echo "<label><input type='checkbox' id='$id' name='" . $option_name . "[$id]' $checked /> ";
                echo __('Disable', 'axp-widget-disabler');
                echo "</label>";
                echo ($desc != '') ? "<p class='description'>$desc</p>" : "";
                break;

            case 'select':
                echo "<select id='$id' name='" . $option_name . "[$id]'>";
                foreach($vals as $v=>$l){
                    $selected = ($o[$id] == $v) ? "selected='selected'" : '';
                    echo "<option value='$v' $selected>$l</option>";
                }
                echo "</select>";
                echo ($desc != '') ? "<p class='description'>$desc</p>" : "";
                break;
        }
    }

    public function get_extends_number($base){
        $rt= array();

        foreach(get_declared_classes() as $class)
            if(is_subclass_of($class,$base)) $rt[] = new $class;
        return $rt;
    }

    public function render_page_settings() {
        ?>
        <div class="wrap">
            <h2><?php _e('Widget Disabler Settings', 'axp-widget-disabler'); ?></h2>

            <div class="card pressthis">
                <form method="POST" enctype="multipart/form-data" action="options.php">
                    <?php settings_fields( $this->fields ); ?>
                    <?php do_settings_sections( $this->menu_slug ); ?>
                    <?php submit_button(); ?>
                </form>
            </div>

            <?php if ($errors = $this->widget_error): ?>
                <div class="card pressthis">
                    <h2><?php _e('Failed to display', 'axp-widget-disabler'); ?></h2>
                    <ol>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <p><?php _e('The widgets uses a non-standard connection', 'axp-widget-disabler'); ?></p>
                </div>
            <?php endif; ?>


            <div class="card pressthis">
                <p style="display: flex; justify-content: space-between">
                    <a class="button" href="https://paypal.me/axpdev" target="_blank"><?php _e('Donate', 'axp-widget-disabler'); ?></a>
                    <a class="button" href="mailto:axp-dev@yandex.com"><?php _e('Contact the author', 'axp-widget-disabler'); ?></a>
                    <a class="button" href="<?php echo get_home_url( null, 'wp-admin/plugin-install.php?s=axpdev&tab=search&type=term' ); ?>" target="_blank"><?php _e('Other plugins by author', 'axp-widget-disabler'); ?></a>
                </p>
            </div>
        </div>
        <?php
    }
}

$AXP_Widget_Disabler = new AXP_Widget_Disabler();