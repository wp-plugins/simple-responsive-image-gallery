<?php



if ( ! defined( 'ABSPATH' ) ) wp_die( __( 'Sorry hackers! This is not your place!', 'dp' ) );

if( ! defined( 'DUO_PLUGIN_DIR' ) ) define( 'DUO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );


if( ! defined( 'DUO_MENU_POSITION' ) ) define( 'DUO_MENU_POSITION', '38' );
if( ! defined( 'DUO_PANEL_SLUG' ) ) define( 'DUO_PANEL_SLUG', 'duogeek-panel' );
if( ! defined( 'DUO_HELP_SLUG' ) ) define( 'DUO_HELP_SLUG', 'duogeek-panel-help' );
if( ! defined( 'DUO_VERSION' ) ) define( 'DUO_VERSION', '1.1' );


if( ! class_exists( 'DuoGeekPlugins' ) ){

    /*
     * Framework Class
     */

    class DuoGeekPlugins{

        private $menuPos;

        protected $admin_enq = array();

        protected $front_enq = array();

        public $help = array();

        private $DuoOptions;

        protected $admin_pages = array();

        public function __construct() {

            $this->menuPos = DUO_MENU_POSITION;

            add_action( 'init', array( $this, 'DuoPlugin_init' ) );
            add_action( 'admin_menu', array( $this, 'register_duogeek_menu_page' ) );
            add_action( 'admin_menu', array( $this, 'register_duogeek_submenu_page' ) );
            add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles_scripts' ) );
            add_action( 'wp_enqueue_scripts', array( $this, 'front_styles_scripts' ) );

        }

        public function DuoPlugin_init() {
            $this->DuoOptions = get_option( 'DuoOptions' );
            $this->admin_pages = apply_filters( 'duogeek_panel_pages', array() );
            $this->admin_pages = array_merge( $this->admin_pages, array( DUO_PANEL_SLUG,DUO_HELP_SLUG  ) );
        }

        public function admin_styles_scripts() {

            $styles = array(
                array(
                    'name' => 'icheck-all',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/icheck/skins/square/_all.css',
                    'dep' => '',
                    'version' => DUO_VERSION,
                    'media' => 'all',
                    'condition' => isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $this->admin_pages )
                ),
                array(
                    'name' => 'icheck-css',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/icheck/skins/square/blue.css',
                    'dep' => '',
                    'version' => DUO_VERSION,
                    'media' => 'all',
                    'condition' => isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $this->admin_pages )
                ),
                array(
                    'name' => 'select-css',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/selectize.css',
                    'dep' => '',
                    'version' => DUO_VERSION,
                    'media' => 'all',
                    'condition' => isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $this->admin_pages )
                ),
                array(
                    'name' => 'duogeek-css',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/duogeek.css',
                    'dep' => '',
                    'version' => DUO_VERSION,
                    'media' => 'all',
                    'condition' => true
                ),
                array(
                    'name' => 'wp-color-picker',
                    'condition' => true
                )
            );

            $scripts = array(
                array(
                    'name' => 'icheck',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/icheck/icheck.min.js',
                    'dep' => array( 'jquery' ),
                    'version' => DUO_VERSION,
                    'footer' => true,
                    'condition' => isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $this->admin_pages )
                    /*'localize' => true,
                    'localize_data' => array(
                        'object' => 'obj_name',
                        'passed_data' => array( '100' )
                    )*/
                ),
                array(
                    'name' => 'select-js',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/selectize.js',
                    'dep' => array( 'jquery' ),
                    'version' => DUO_VERSION,
                    'footer' => true,
                    'condition' => isset( $_REQUEST['page'] ) && in_array( $_REQUEST['page'], $this->admin_pages )
                ),
                array(
                    'name' => 'duogeek-js',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/duogeek.js',
                    'dep' => array( 'jquery' ),
                    'version' => DUO_VERSION,
                    'footer' => true,
                    'condition' => true
                ),
                array(
                    'name' => 'wp-color-picker',
                    'condition' => true
                )
            );

            $this->admin_enq = apply_filters( 'admin_scripts_styles', array() );

            if( count( $this->admin_enq ) > 0 ){
                $this->admin_enq['scripts'] = array_merge( $scripts, $this->admin_enq['scripts'] );
                $this->admin_enq['styles'] = array_merge( $styles, $this->admin_enq['styles'] );
            }else{
                $this->admin_enq['scripts'] = $scripts;
                $this->admin_enq['styles'] = $styles;
            }


            foreach( $this->admin_enq['scripts'] as $script ){

                if( $script['name'] == 'media' ){
                    wp_enqueue_media();
                }

                if( $script['condition'] ){
                    if( isset( $script['src'] ) ) {
                        wp_register_script( $script['name'], $script['src'], $script['dep'], $script['version'], $script['footer'] );
                    }
                    wp_enqueue_script( $script['name'] );


                    if( isset( $script['localize'] ) ){
                        wp_localize_script( $script['name'], $script['localize_data']['object'], $script['localize_data']['passed_data'] );
                    }
                }

            }

            foreach( $this->admin_enq['styles'] as $style ){

                if( $style['condition'] ){
                    if( isset( $style['src'] ) ) {
                        wp_register_style( $style['name'], $style['src'], $style['dep'], $style['version'], $style['media'] );
                    }
                    wp_enqueue_style( $style['name'] );
                }

            }

        }


        public function front_styles_scripts() {

            $styles = array(
                array(
                    'name' => 'sn-fontAwesome-css',
                    'src' => '//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css',
                    'dep' => '',
                    'version' => DUO_VERSION,
                    'media' => 'all',
                    'condition' => $this->DuoOptions['fontAwesome'] != 1
                ),
                array(
                    'name' => 'sn-animate-css',
                    'src' => DUO_PLUGIN_URI . 'duogeek/inc/animate.css',
                    'dep' => '',
                    'version' => DUO_VERSION,
                    'media' => 'all',
                    'condition' => $this->DuoOptions['animate'] != 1
                )
            );

            $scripts = array();

            $this->front_enq = apply_filters( 'front_scripts_styles', array() );

            if( count( $this->front_enq ) > 0 ){
                $this->front_enq['scripts'] = array_merge( $scripts, $this->front_enq['scripts'] );
                $this->front_enq['styles'] = array_merge( $styles, $this->front_enq['styles'] );
            }
            else{
                $this->front_enq['scripts'] = $scripts;
                $this->front_enq['styles'] = $styles;
            }


            foreach( $this->front_enq['scripts'] as $script ){

                if( $script['name'] == 'media' ){
                    wp_enqueue_media();
                }

                if( $script['condition'] ){
                    if( isset( $script['src'] ) ) {
                        wp_register_script( $script['name'], $script['src'], $script['dep'], $script['version'], $script['footer'] );
                    }
                    wp_enqueue_script( $script['name'] );


                    if( isset( $script['localize'] ) ){
                        wp_localize_script( $script['name'], $script['localize_data']['object'], $script['localize_data']['passed_data'] );
                    }
                }

            }

            foreach( $this->front_enq['styles'] as $style ){

                if( $style['condition'] ){
                    if( isset( $style['src'] ) ) {
                        wp_register_style( $style['name'], $style['src'], $style['dep'], $style['version'], $style['media'] );
                    }
                    wp_enqueue_style( $style['name'] );
                }

            }

        }


        public function register_duogeek_menu_page()
        {
            if( empty( $GLOBALS['admin_page_hooks']['duogeek-panel'] ) ) {
                add_menu_page(__('DuoGeek', 'dp'), __('DuoGeek', 'dp'), 'manage_options', DUO_PANEL_SLUG, array($this, 'duogeek_panel_cb'), '', $this->menuPos);
            }
        }


        public function duogeek_panel_cb() {

            $duo = $this->DuoOptions;

            if( isset( $_POST['dp_save'] ) ){

                if ( ! check_admin_referer( 'dp_nonce_action', 'dp_nonce_field' )){
                    return;
                }

                if( isset( $_POST['duo'] ) ){
                    foreach( $_POST['duo'] as $key => $val ){
                        $duo_post[$key] = $_POST['duo'][$key];
                    }
                }

                $duo_post['fontAwesome'] = isset( $duo_post['fontAwesome'] ) ? $duo_post['fontAwesome'] : 0;
                $duo_post['animate'] = isset( $duo_post['animate'] ) ? $duo_post['animate'] : 0;
                $duo_post['cookie'] = isset( $duo_post['cookie'] ) ? $duo_post['cookie'] : 24;


                update_option( 'DuoOptions', $duo_post );

                wp_redirect( urldecode( $_REQUEST['redirect_url'] ) . '&msg=Settings+saved+successfully.' );

            }

            $promo_content = wp_remote_get( 'http://duogeek.com/duo-promo.html' );

            ?>
            <div class="wrap duo_prod_panel">

                <h2><?php _e( 'DuoGeek Settings', 'dp' ) ?></h2>

                <?php if( isset( $_REQUEST['msg'] ) ) { ?>
                    <div id="message" class="<?php echo isset( $_REQUEST['duoaction'] ) ? $_REQUEST['duoaction'] : 'updated' ?> below-h2"><p><?php echo str_replace( '+', ' ', $_REQUEST['msg'] ) ?></p></div>
                <?php } ?>
                <div id="poststuff">
                    <form action="<?php echo admin_url( 'admin.php?page=' . DUO_PANEL_SLUG . '&noheader=true&redirect_url=' . urlencode( admin_url(  'admin.php?page=' . DUO_PANEL_SLUG ) ) ) ?>" method="post">
                        <?php wp_nonce_field('dp_nonce_action','dp_nonce_field'); ?>
                        <div class="postbox">
                            <h3 class="hndle"><?php _e( 'General Settings', 'dp' ) ?></h3>
                            <div class="inside">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e( 'Disable FontAwesome', 'dp' ) ?></th>
                                        <td><input <?php echo isset( $duo['fontAwesome'] ) && $duo['fontAwesome'] == 1 ? 'checked="checked"' : '' ?> type="checkbox" name="duo[fontAwesome]" value="1" /> <span class="description"><?php _e( 'Check if your theme already provides it', 'dp' ) ?></span></td>
                                    </tr>
                                    <tr>
                                        <th><?php _e( 'Disable Animate', 'dp' ) ?></th>
                                        <td><input <?php echo isset( $duo['animate'] ) && $duo['animate'] == 1 ? 'checked="checked"' : '' ?> type="checkbox" name="duo[animate]" value="1" /> <span class="description"><?php _e( 'Check if your theme already provides it', 'dp' ) ?></span></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <p><input type="submit" name="dp_save" class="button button-primary" value="<?php _e( 'Save Settings', 'dp' ) ?>" /></p>
                    </form>
                </div>

                <?php echo $promo_content['body']; ?>

            </div>
        <?php
        }


        public function register_duogeek_submenu_page() {

            $submenus = apply_filters( 'duogeek_submenu_pages', array() );

            if( count( $submenus ) > 0 ) {
                foreach( $submenus as $submenu ){
                    if( isset( $submenu['object'] ) )
                        add_submenu_page( DUO_PANEL_SLUG, $submenu['title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], array( $submenu['object'], $submenu['function'] ) );
                    else
                        add_submenu_page( DUO_PANEL_SLUG, $submenu['title'], $submenu['menu_title'], $submenu['capability'], $submenu['slug'], $submenu['function'] );
                }
            }

            add_submenu_page( DUO_PANEL_SLUG, __( 'Help', 'dp' ), __( 'Help', 'dp' ), 'manage_options', DUO_HELP_SLUG, array( $this, 'duogeek_panel_help_cb' ) );
        }



        public function duogeek_panel_help_cb() {

            $this->help = array(
                'shortcodes'    => apply_filters( 'duo_panel_help_shortcodes', array( ) ),
                'filters'       => apply_filters( 'duo_panel_help_filters', array( ) ),
                'actions'       => apply_filters( 'duo_panel_help_actions', array( ) ),
                'tips'          => apply_filters( 'duo_panel_help_tips', array( ) ),
            );

            $this->help = apply_filters( 'duo_panel_help', array( ) );

            ?>
            <div class="wrap duo-kb">
                <h2><?php _e( 'Help', 'dp' ) ?></h2>
                <?php foreach( $this->help as $key => $helps ) { ?>
                    <div id="poststuff">
                        <div class="postbox">
                            <h3 class="hndle"><?php echo $helps['name'] ?> <span><?php _e( 'Click to expand/collapse', 'dp' ) ?></span></h3>
                            <div class="inside">
                                <div class="duo_help">
                                    <ul>
                                        <?php foreach( $helps as $key => $help ){ if( $key == 'name' ) continue; ?>
                                            <li>
                                                <h5><?php echo ucfirst( $key ) ?></h5>
                                                <div class="item_details">
                                                    <ul>
                                                        <?php foreach( $help as $details ){ ?>
                                                            <li>

                                                                <?php if( isset( $details['source'] ) ) { ?>
                                                                    <p>
                                                                        <b>
                                                                            <?php
                                                                            _e( 'Source: ', 'dp' );
                                                                            echo $details['source'];
                                                                            ?>
                                                                        </b>
                                                                    </p>
                                                                <?php } ?>

                                                                <?php if( isset( $details['code'] ) ) { ?>
                                                                    <p>
                                                                        <?php
                                                                        echo '<b>';
                                                                        _e( 'Code: ', 'dp' );
                                                                        echo '</b>';
                                                                        echo '<span class="code">' . $details['code'] . '</span>';
                                                                        ?>
                                                                    </p>
                                                                <?php } ?>

                                                                <?php if( isset( $details['example'] ) ) { ?>
                                                                    <p>
                                                                        <?php
                                                                        echo '<b>';
                                                                        _e( 'Example: ', 'dp' );
                                                                        echo '</b>';
                                                                        echo $details['example'];
                                                                        ?>
                                                                    </p>
                                                                <?php } ?>

                                                                <?php if( isset( $details['default'] ) ) { ?>
                                                                    <p>
                                                                        <?php
                                                                        echo '<b>';
                                                                        _e( 'Default: ', 'dp' );
                                                                        echo '</b>';
                                                                        echo $details['default'];
                                                                        ?>
                                                                    </p>
                                                                <?php } ?>

                                                                <?php if( isset( $details['desc'] ) ) { ?>
                                                                    <p>
                                                                        <?php
                                                                        echo '<b>';
                                                                        _e( 'Description: ', 'dp' );
                                                                        echo '</b>';
                                                                        echo $details['desc'];
                                                                        ?>
                                                                    </p>
                                                                <?php } ?>

                                                            </li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php
        }

    }

    new DuoGeekPlugins();

    require_once 'helper.php';
    require_once 'class.customPostType.php';

}
