<?php
/*
  Plugin Name: Simple Image Gallery
  Plugin URI: http://duogeek.com
  Description: Most Beautiful and Most Simple WordPress Image Gallery Plugin
  Version: 1.0.2
  Author: DuoGeek
  Author URI: http://duogeek.com
  License: GPL v2 or later
 */

if ( !defined( 'ABSPATH' ) )
    wp_die( __( 'Sorry cowboy! This is not your place!', 'sig' ) );

if ( !defined( 'DUO_PLUGIN_URI' ) )
    define( 'DUO_PLUGIN_URI', plugin_dir_url( __FILE__ ) );

define( 'DG_GALLERY_VERSION', '1.0' );

require 'duogeek/duogeek-panel.php';

if ( !defined( 'DG_BRAND' ) )
    define( 'DG_BRAND', 'Image Gallery Settings' );
if ( !defined( 'DG_VERSION' ) )
    define( 'DG_VERSION', '1.0' );
if ( !defined( 'DG_PLUGIN_DIR' ) )
    define( 'DG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
if ( !defined( 'DG_FILES_DIR' ) )
    define( 'DG_FILES_DIR', DG_PLUGIN_DIR . 'gallery-files' );
if ( !defined( 'DG_FILES_URI' ) )
    define( 'DG_FILES_URI', plugin_dir_url( __FILE__ ) . 'gallery-files' );
if ( !defined( 'DG_CLASSES_DIR' ) )
    define( 'DG_CLASSES_DIR', DG_FILES_DIR . '/classes' );
if ( !defined( 'DG_ADDONS_DIR' ) )
    define( 'DG_ADDONS_DIR', DG_FILES_DIR . '/addons' );
if ( !defined( 'DG_INCLUDES_DIR' ) )
    define( 'DG_INCLUDES_DIR', DG_FILES_DIR . '/includes' );

add_action( 'init', 'sig_localization' );

function sig_localization() {
    load_plugin_textdomain( 'sig', FALSE, DG_PLUGIN_DIR . '/lang/' );
}

if ( !class_exists( 'DGImageGallery' ) ) {

    class DGImageGallery extends customPostType {

        private $post_type = array();

        public function __construct() {

            $this->post_type = array(
                'post_type' => 'gallery',
                'name' => _x( 'Gallery', 'Post Type General Name', 'sig' ),
                'singular_name' => _x( 'Gallery', 'Post Type Singular Name', 'sig' ),
                'menu_name' => __( 'Gallery', 'sig' ),
                'parent_item_colon' => __( 'Parent Gallery:', 'sig' ),
                'all_items' => __( 'All Galleries', 'sig' ),
                'view_item' => __( 'View Gallery', 'sig' ),
                'add_new_item' => __( 'Add New Gallery', 'sig' ),
                'add_new' => __( 'Add New Gallery', 'sig' ),
                'edit_item' => __( 'Edit Gallery', 'sig' ),
                'update_item' => __( 'Update Gallery', 'sig' ),
                'search_items' => __( 'Search Gallery', 'sig' ),
                'not_found' => __( 'Not found', 'sig' ),
                'not_found_in_trash' => __( 'Not found in Trash', 'sig' ),
                'name_admin_bar' => __( 'Gallery', 'sig' ),
                'rewrite' => __( 'gallery', 'sig' ),
                'supports' => array('title', 'thumbnail')
            );

            parent::__construct( $this->post_type );

            add_action( 'init', array($this, 'register_gallery_post_type') );
            add_shortcode( 'dg_gallery', array($this, 'show_gallery_data') );
            add_action( 'save_post', array($this, 'gallery_meta_save') );
            add_filter( 'admin_scripts_styles', array($this, 'gallery_admin_scripts') );
            add_filter( 'front_scripts_styles', array($this, 'gallery_frontend_enqueue') );
            add_filter( 'duogeek_submenu_pages', array($this, 'duogallery_menu') );
            add_action( 'add_meta_boxes', array($this, 'gallery_add_post_type_metabox') );
            add_action( 'do_meta_boxes', array($this, 'remove_image_box') );

            add_filter( 'manage_gallery_posts_columns', array($this, 'revealid_add_id_column'), 5 );
            add_action( 'manage_gallery_posts_custom_column', array($this, 'revealid_id_column_content'), 5, 2 );



            add_filter( 'duogeek_panel_pages', array($this, 'duogeek_panel_pages_gallery') );
            add_filter( 'duo_panel_help', array($this, 'duo_panel_help_cb') );
            add_action( 'wp_head', array($this, 'hook_front_css') );
            add_image_size( 'dg-gallery-img', 400, 250, true );
        }

        public function remove_image_box() {
            remove_meta_box( 'postimagediv', 'gallery', 'side' );
        }

        public function revealid_add_id_column( $columns ) {
            $columns['revealid_id'] = 'ID';
            $columns['shortcode_id'] = 'ShortCode';
            return $columns;
        }

        public function revealid_id_column_content( $column, $id ) {
            if ( 'revealid_id' == $column ) {
                echo $id;
            }
            elseif ( 'shortcode_id' == $column ) {
                echo '[dg_gallery gallery_id="'. $id .'"]';
            }
        }

        public function register_gallery_post_type() {
            $this->register_custom_post_type();
        }

        public function gallery_add_post_type_metabox() { // add the meta box
            add_meta_box( 'gallery_metabox', __( 'Add New Images for the Gallery', 'sig' ), array($this, 'render_meta_box_content'), $this->post_type['post_type'], 'normal' );
        }

        public function duogeek_panel_pages_gallery( $arr ) {
            $arr[] = 'duogallery-settings';
            return $arr;
        }

        public function gallery_meta_save( $post_id ) {
            $data = array();
            if ( !isset( $_POST['gallery_metabox_nonce'] ) )
                return $post_id;

            $nonce = $_POST['gallery_metabox_nonce'];

            if ( !wp_verify_nonce( $nonce, 'gallery_metabox' ) )
                return $post_id;

            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
                return $post_id;

            if ( 'page' == $_POST['post_type'] ) {
                if ( !current_user_can( 'edit_page', $post_id ) )
                    return $post_id;
            } else {
                if ( !current_user_can( 'edit_post', $post_id ) )
                    return $post_id;
            }

            if ( isset( $_POST['gallery_meta_url'] ) ) {
                $i = 0;
                foreach ( $_POST['gallery_meta_url'] as $url ) {
                    if ( $url == "" ) {
                        unset( $_POST['gallery_meta_url'][$i] );
                        unset( $_POST['gallery_meta_caption'][$i] );
                    }
                    $i++;
                }
                $data = array(
                    'url' => $_POST['gallery_meta_url'],
                    'caption' => $_POST['gallery_meta_caption']
                );
            }

            update_post_meta( $post_id, '_gallery_meta_value_key', $data );
        }

        public function render_meta_box_content( $post ) {
            wp_nonce_field( 'gallery_metabox', 'gallery_metabox_nonce' );
            $galleries = get_post_meta( $post->ID, '_gallery_meta_value_key', true );
            //var_dump( $galleries );
            ?>
            <div class="width_full p_box dg_form">
                <table border="0" width="100%" cellpadding="0" cellspacing="0" class="gallery_meta_table">
                    <tr style="visibility: hidden;">
                        <th style="width: 200px;">Image</th>
                        <th>Image Title</th>
                        <th>Action</th>
                    </tr>
                    <?php
                    if ( isset( $galleries['url'] ) && is_array( $galleries['url'] ) ) {
                        $i = 0;
                        foreach ( $galleries['url'] as $gallery ) {
                            $image_attributes = wp_get_attachment_image_src( $galleries['url'][$i], 'dg-gallery-img' );
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo $image_attributes[0]; ?>" class="gallery_meta_img" style="display: block; padding: 10px 10px 10px 0; border: none;" width="200" >
                                </td>
                                <td>
                                    <input type="text" name="gallery_meta_url[]" class="gallery_meta_url wide" value="<?php echo $galleries['url'][$i]; ?>" style="display: none;">
                                    <label>Enter Image Title</label>
                                    <input type="text" name="gallery_meta_caption[]" class="gallery_meta_caption wide" value="<?php echo $galleries['caption'][$i]; ?>">
                                </td>
                                <td>
                                    <input type="button" name="gallery_meta_upload" class="gallery_meta_upload wide" value="Change">
                                    <input type="button" name="gallery_meta_remove" class="gallery_meta_remove" value="Remove">
                                </td>
                            </tr>
                            <?php
                            $i++;
                        }
                    } else {
                        ?>
                        <tr>
                            <td><img src="" class="gallery_meta_img" style="display: none; padding: 10px 10px 10px 0; border: none;" width="200"></td>
                            <td>
                                <input type="text" name="gallery_meta_url[]" class="gallery_meta_url wide" value="" style="display: none;">
                                <label>Enter Image Title</label>
                                <input type="text" name="gallery_meta_caption[]" class="gallery_meta_caption wide" value="">
                            </td>
                            <td>
                                <input type="button" name="gallery_meta_upload" class="gallery_meta_upload wide" value="Upload">
                                <input type="button" name="gallery_meta_remove" class="gallery_meta_remove" value="Remove" style="display: none;">
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                <input type="button" class="add_new_image" value="Add Image">
            </div>
            <?php
        }

        public function gallery_admin_scripts( $enq ) {

            $scripts = array(
                array(
                    'name' => 'media',
                    'condition' => true
                ),
                array(
                    'name' => 'gallery-admin-js',
                    'src' => DG_FILES_URI . '/js/gallery-admin.js',
                    'dep' => array('jquery'),
                    'version' => DG_GALLERY_VERSION,
                    'footer' => true,
                    'condition' => true
                )
            );

            $styles = array(
                array(
                    'name' => 'gallery-admin-css',
                    'src' => DG_FILES_URI . '/css/dggallery-admin.css',
                    'dep' => '',
                    'version' => DUO_VERSION,
                    'media' => 'all',
                    'condition' => true
                )
            );

            if ( !isset( $enq['scripts'] ) || !is_array( $enq['scripts'] ) )
                $enq['scripts'] = array();
            if ( !isset( $enq['styles'] ) || !is_array( $enq['styles'] ) )
                $enq['styles'] = array();
            $enq['scripts'] = array_merge( $enq['scripts'], $scripts );
            $enq['styles'] = array_merge( $enq['styles'], $styles );

            return $enq;
        }

        public function gallery_frontend_enqueue( $enq ) {
            $default = array(
                'imagenumber' => 0,
                'closebutton' => false,
                'bardelay' => 3000,
                'loopatend' => false
            );
            $options = get_option( 'dg_gallery_options', true );
            $args = wp_parse_args( $options['options'], $default );
            $scripts = array(
                array(
                    'name' => 'dg_gallery_sript',
                    'src' => DG_FILES_URI . '/swipebox/js/jquery.swipebox.js',
                    'dep' => array('jquery'),
                    'version' => DG_GALLERY_VERSION,
                    'footer' => true,
                    'condition' => true
                ),
                array(
                    'name' => 'dg_gallery_front_sript',
                    'src' => DG_FILES_URI . '/js/gallery-front.js',
                    'dep' => array('jquery'),
                    'version' => DG_GALLERY_VERSION,
                    'footer' => true,
                    'condition' => true,
                    'localize' => true,
                    'localize_data' => array(
                        'object' => 'obj',
                        'passed_data' => array(
                            'imagenumber' => $args['imagenumber'],
                            'closebutton' => $args['closebutton'],
                            'bardelay' => $args['bardelay'],
                            'loopatend' => $args['loopatend']
                        )
                    )
                )
            );

            $styles = array(
                array(
                    'name' => 'dg_gallery_style',
                    'src' => DG_FILES_URI . '/swipebox/css/swipebox.css',
                    'dep' => '',
                    'version' => DG_GALLERY_VERSION,
                    'media' => 'all',
                    'condition' => true
                ),
                array(
                    'name' => 'dg_front_gallery_style',
                    'src' => DG_FILES_URI . '/css/dggallery.css',
                    'dep' => '',
                    'version' => DG_GALLERY_VERSION,
                    'media' => 'all',
                    'condition' => true
                )
            );

            if ( !isset( $enq['scripts'] ) || !is_array( $enq['scripts'] ) )
                $enq['scripts'] = array();
            if ( !isset( $enq['styles'] ) || !is_array( $enq['styles'] ) )
                $enq['styles'] = array();
            $enq['scripts'] = array_merge( $enq['scripts'], $scripts );
            $enq['styles'] = array_merge( $enq['styles'], $styles );

            return $enq;
        }

        public function show_gallery_data( $atts ) {
            $atts = shortcode_atts(
                    array(
                'gallery_id' => '',
                'title' => 'Image Gallery',
                    ), $atts, 'dg_gallery' );

            $galleries = get_post_meta( $atts['gallery_id'], '_gallery_meta_value_key', true );
            $content = '';
            if ( isset( $galleries['url'] ) && is_array( $galleries['url'] ) ) {
                $i = 0;
                foreach ( $galleries['url'] as $gallery ) {
                    $image_attributes = wp_get_attachment_image_src( $galleries['url'][$i], 'dg-gallery-img' );
                    $image_attributes_full = wp_get_attachment_image_src( $galleries['url'][$i], 'full' );

                    $content .= '<a rel="gallery" href="' . $image_attributes_full[0] . '" class="swipebox dgsig" title="' . $galleries['caption'][$i] . '"><img src="' . $image_attributes[0] . '" alt="' . $galleries['caption'][$i] . '"></a>';
                    $i++;
                }
            }

            return $content;
        }

        public function get_attachment_id_from_src( $image_src ) {

            global $wpdb;
            $query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$image_src'";
            $id = $wpdb->get_var( $query );
            return $id;
        }

        public function duogallery_menu( $submenus ) {
            $submenus[] = array(
                'title' => 'Gallery Settings',
                'menu_title' => 'Gallery Settings',
                'capability' => 'manage_options',
                'slug' => 'duogallery-settings',
                'object' => $this,
                'function' => 'duogallery_settings_page'
            );

            return $submenus;
        }

        public function hook_front_css() {
            $options = get_option( 'dg_gallery_options', true );
            $output = "<style>";
            if ( isset( $options['options']['imageborder'] ) && $options['options']['imageborder'] != "" ) {
                $output .= '.dgsig > img {border: ' . $options['options']['imageborder'] . 'px solid}';
            }
            if ( isset( $options['options']['bordercolor'] ) && $options['options']['bordercolor'] != "" ) {
                $output .= '.dgsig > img {border-color: ' . $options['options']['bordercolor'] . '}';
            }
            if ( isset( $options['options']['imagewidth'] ) && $options['options']['imagewidth'] != "" ) {
                $output .= '.dgsig > img {width: ' . $options['options']['imagewidth'] . 'px}';
            }
            $output .= "</style>";

            echo $output;
        }

        public function duogallery_settings_page() {
            if ( !current_user_can( 'manage_options' ) ) {
                wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
            }

            if ( isset( $_POST['gallery_option_save'] ) ) {
                $options = $_POST;

                if ( !isset( $_POST['dg_gallery_nonce_value'] ) ) {
                    $msg = "You are not allowed to make this change&res=error";
                } elseif ( !wp_verify_nonce( $_POST['dg_gallery_nonce_value'], 'dg_gallery_nonce' ) ) {
                    $msg = "You are not allowed to make this change&res=error";
                } else {
                    update_option( 'dg_gallery_options', $options );
                    $msg = 'Data Saved';
                }

                wp_redirect( admin_url( 'admin.php?page=duogallery-settings&msg=' . str_replace( ' ', '+', $msg ) ) );
            }

            $options = get_option( 'dg_gallery_options', true );
            //var_dump($options);
            ?>
            <form action="<?php echo admin_url( 'admin.php?page=duogallery-settings&noheader=true' ) ?>" method="post">
                <div class="wrap duo_prod_panel">
                    <h2><?php _e( 'Gallery Settings' ) ?></h2>
                    <?php if ( isset( $_REQUEST['msg'] ) ) { ?>
                        <div id="message" class="<?php echo isset( $_REQUEST['duoaction'] ) ? $_REQUEST['duoaction'] : 'updated' ?> below-h2"><p><?php echo str_replace( '+', ' ', $_REQUEST['msg'] ) ?></p></div>
                    <?php } ?>
                    <div id="poststuff">
                        <div class="postbox">
                            <h3 class="hndle">Save your Option Value</h3>
                            <div class="inside">

                                <?php wp_nonce_field( 'dg_gallery_nonce', 'dg_gallery_nonce_value' ); ?>
                                <table class="form-table">
                                    <tr>
                                        <th>Initial Image Number</th>
                                        <td>
                                            <input type="text" name="options[imagenumber]" value="<?php echo isset( $options['options']['imagenumber'] ) && $options['options']['imagenumber'] != '' ? $options['options']['imagenumber'] : '' ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Close Button on Mobile</th>
                                        <td>
                                            <select name="options[closebutton]">
                                                <option value="true" <?php echo isset( $options['options']['closebutton'] ) && $options['options']['closebutton'] == 'true' ? 'selected' : '' ?>>True</option>
                                                <option value="false" <?php echo isset( $options['options']['closebutton'] ) && $options['options']['closebutton'] == 'false' ? 'selected' : '' ?>>False</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Hide Bar Delay</th>
                                        <td>
                                            <input type="text" name="options[bardelay]" value="<?php echo isset( $options['options']['bardelay'] ) && $options['options']['bardelay'] != '' ? $options['options']['bardelay'] : '' ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Loop at End</th>
                                        <td>
                                            <select name="options[loopatend]">
                                                <option value="1" <?php echo isset( $options['options']['loopatend'] ) && $options['options']['loopatend'] == '1' ? 'selected' : '' ?>>True</option>
                                                <option value="0" <?php echo isset( $options['options']['loopatend'] ) && $options['options']['loopatend'] == '0' ? 'selected' : '' ?>>False</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Image Border</th>
                                        <td><input type="text" name="options[imageborder]" value="<?php echo isset( $options['options']['imageborder'] ) && $options['options']['imageborder'] != '' ? $options['options']['imageborder'] : '' ?>"> px</td>
                                    </tr>
                                    <tr>
                                        <th>Border Color</th>
                                        <td><input type="text" name="options[bordercolor]" class="dg-color-field" value="<?php echo isset( $options['options']['bordercolor'] ) && $options['options']['bordercolor'] != '' ? $options['options']['bordercolor'] : '' ?>"></td>
                                    </tr>
                                    <tr>
                                        <th>Frontend Image Width</th>
                                        <td><input type="text" name="options[imagewidth]" value="<?php echo isset( $options['options']['imagewidth'] ) && $options['options']['imagewidth'] != '' ? $options['options']['imagewidth'] : '' ?>"> px</td>
                                    </tr>
                                </table>
                                <p><input type="submit" class="button button-primary" name="gallery_option_save" value="Save Settings" style="width: 100px; text-aldgn: center;"></p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <?php
        }

        public function duo_panel_help_cb( $arr ) {
            $arr[] = array(
                'name' => __( 'Simple Image Gallery' ),
                'shortcodes' => array(
                    array(
                        'source' => __( 'Simple Image Gallery PLugin', 'sig' ),
                        'code' => '[dg_gallery gallery_id="GALLERY ID"]',
                        'example' => '[dg_gallery gallery_id="GALLERY ID" title="ANY TITLE"]',
                        'default' => 'title = "Image Gallery"',
                        'desc' => __( 'This shortcode will show the Image Gallery. You need to give the gallery ID, either it can\'t show you any gallery.', 'sig' )
                    ),
                )
            );

            return $arr;
        }

    }

    new DGImageGallery();
}
