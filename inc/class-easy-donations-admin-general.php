<?php

/**
 * Plugin General Options
 * 
 * @class Easy_Donations_Settings
 * @version 1.0
 */
class Easy_Donations_Admin_General extends Easy_Donations_Settings {
    
    /**
     * Holds class version
     * @var string
     */
    const version = '1.0';
    
    public static $registered_fields = array();
    
    /**
     * Register settings
     * The fields_array contains a list of user fields for the gateway settings
     * The callback must prints the gateway html codes (optional)
     * 
     * @param string $section
     * @param string $title 
     * @param array $fields_array
     * @param callback $before_callback
     * @param callback $after_callback
     */
    public function register_opts( $section, $title, $fields_array, $before_callback = null, $after_callback = null ) {
        if( $title == '' )
            return;
        
        if( ! empty( $fields_array ) ) {
            self::$registered_fields[] = array(
                    'section'   => $section,
                    'title'     => $title,
                    'fields'    => $fields_array ,//$tmp,
                    'before_callback' => ( ! is_null( $before_callback ) ) ? $before_callback : null,
                    'after_callback' => ( ! is_null( $after_callback ) ) ? $after_callback : null
                );
        }
        elseif( ! is_null( $before_callback ) || ! is_null( $after_callback ) ) {
            self::$registered_fields[] = array(
                    'section'   => $section,
                    'title'     => $title,
                    'fields'    => null,
                    'before_callback' => ( ! is_null( $before_callback ) ) ? $before_callback : null,
                    'after_callback' => ( ! is_null( $after_callback ) ) ? $after_callback : null
                );
        }
    }
    
    public function __construct() {
        add_action( 'easy_donations_menu_items', array( $this, 'register_menus' ), 1 );
        $this->settings_name = 'edt_general_page_option';
        add_action( 'admin_init', array( $this, 'create_general_page' ) );
        $this->set_menu();
    }
    
    public function register_menus() {
        add_menu_page( 'Easy Donations', __( 'Easy Donations', EDT_TEXT_DOMAIN ), 'manage_options', 'edt_plugin_options', array( $this, 'edt_general_page_content' ), 'dashicons-smiley' );
        add_submenu_page( 'edt_plugin_options', 'General',  __( 'General Settings', EDT_TEXT_DOMAIN ), 'manage_options', 'edt_plugin_options', array( $this, 'edt_general_page_content' ) );
    }
    
    public function create_general_page() {
        $this->create_setting( 'edt_general_page_options_group', 'edt_general_page_option', array( $this, 'edt_general_page_validator' ) );
        
        $this->add_section( 'descriptions', __( 'Descriptions', EDT_TEXT_DOMAIN ) , array( $this, 'edt_general_page_descriptions_callback' ) );
        $this->add_section( 'settings', __( 'Settings', EDT_TEXT_DOMAIN ), array( $this, 'edt_general_page_settings_callback' ) );
        $this->add_section( 'gateways', __( 'Gateways', EDT_TEXT_DOMAIN ), array( $this, 'edt_general_page_gateways_callback' ) );
        
        
        $this->register_opts( 'descriptions', __( 'Plugin Short Code', EDT_TEXT_DOMAIN ), array(), array( $this, 'plugin_short_code_callback' ) );
        
        $this->register_opts( 'descriptions', __( 'Donate Form Function', EDT_TEXT_DOMAIN ), array(), array( $this, 'donate_form_function_callback' ) );
        
        $this->register_opts( 'settings', __( 'Add a field', EDT_TEXT_DOMAIN ), array(), array( $this, 'add_field_callback' ) );
        
        $this->register_opts( 'settings', __( 'Amount field type', EDT_TEXT_DOMAIN ), array(), array( $this, 'amount_field_callback' ) );

        $temp = array(
            array( 
                'id'        => 'form_background_paralax',
                'name'      => $this->settings_name . '[form_background_type]', 
                'type'      => 'radio',
                'text'      => "<label for='form_background_paralax'>" . __( 'Parallax Background', EDT_TEXT_DOMAIN ) . "</label>", 
                'value'     => 'parallax',
                'checked'   => ( edt_ins()->options->get_option('form_background_type') == 'parallax' ) ? true : false
            ),
            array( 
                'id'        => 'form_background_fw',
                'name'      => $this->settings_name . '[form_background_type]',
                'type'      => 'radio',
                'text'      => "<label for='form_background_fw'>" . __( 'Covered Background', EDT_TEXT_DOMAIN ) . "</label>",
                'value'     => 'full-width',
                'checked'   => ( edt_ins()->options->get_option('form_background_type') == 'full-width' ) ? true : false
            )
         );

        $this->register_opts( 'settings', __( 'Form Background', EDT_TEXT_DOMAIN ), $temp, null, array( $this, 'background_file_select_field' ) );
        
        $this->register_opts( 'settings', __( 'Active Currency', EDT_TEXT_DOMAIN ), null, null, array( $this, 'select_active_currency' ) );
        
        $successfull_payment = edt_ins()->options->get_option('successfull_payment');
        $temp = array(
            array( 
                'id'        => 'show_form_after_successfull_payment',
                'name'      => $this->settings_name . '[successfull_payment][show_form_after_successfull_payment]',
                'type'      => 'checkbox',
                'text'      => "<label for='show_form_after_successfull_payment'>" . __( 'Check this to hide the form and show the below message after successfull payment.', EDT_TEXT_DOMAIN ) . "</label>",
                'checked'   => ( isset( $successfull_payment['show_form_after_successfull_payment'] ) ) ? true : false,
                'classes'   => 'show_form_after_successfull_payment'
            ),
            array( 
                'id'        => 'successfull_payment_message',
                'name'      => $this->settings_name . '[successfull_payment][successfull_payment_message]',
                'type'      => 'textarea',
                'content'   => ( isset( $successfull_payment['successfull_payment_message'] ) ) ? $successfull_payment['successfull_payment_message'] : '' ,
                'classes'   => 'successfull_payment_message'
            )
        );
        
        $this->register_opts( 'settings', __( 'Successfull Payment Message', EDT_TEXT_DOMAIN ), $temp, null, null );
        
        $temp = array( 
                'id'        => 'form_custome_styles',
                'name'      => $this->settings_name . '[form_custome_styles]',
                'type'      => 'textarea',
                'text'      => "<pre>" . __( 'I all load this styles after my own styles!', EDT_TEXT_DOMAIN ) . "</pre>",
                'content'   => edt_ins()->options->get_option('form_custome_styles'),
                'classes'   => 'custome-styles'
            );
        
        $this->register_opts( 'settings', __( 'Custome Styles', EDT_TEXT_DOMAIN ), array( $temp ), null, null );

        $this->add_registered_fields( self::$registered_fields );
        
        do_action('edt_general_page_content');
    }
    
    private function add_registered_fields( $registered_fields ) {
        if( empty( $registered_fields ) )
            return;
        
        $counter = 0;
        foreach( $registered_fields as $field ) {
            $this->add_field( 'easy_donations_general_field_' . $counter, $field['title'], array( $this, "dynamic_fields_callback" ), $field['section'], array( 'field_set' => $field ) );
            $counter++;
        }
    }
    
    public function dynamic_fields_callback( $field_set ) {
        $field_set = $field_set['field_set'];
        if( ( is_null( $field_set['fields'] ) && ! is_null( $field_set['before_callback'] ) ) || ! is_null( $field_set['before_callback'] ) )
            call_user_func( $field_set['before_callback'] );

        if( ! is_null( $field_set['fields'] ) ) {
            foreach( $field_set['fields'] as $field ) {
                echo $this->get_form_field( $field );
            }
        }
        
        if( ( is_null( $field_set['fields'] ) && ! is_null( $field_set['after_callback'] ) ) || ! is_null( $field_set['after_callback'] ) )
            call_user_func( $field_set['after_callback'] );
    }
    
    public function edt_general_page_content() {
        echo '<div class="edt-wrap">';
        echo $this->print_form( '' );
        echo '</div>';
    }
    
    public function edt_general_page_descriptions_callback() {
        echo __( 'In this section you can find informations about how to use this plugin.', EDT_TEXT_DOMAIN );
    }
    
    public function edt_general_page_settings_callback() {
        echo "<input type='hidden' name='".esc_html($this->settings_name)."[donate_form_active_fields][rubbish]' value='1' />";
        echo "<input type='hidden' name='".esc_html($this->settings_name)."[donate_form_required_fields][rubbish]' value='1' />";
    }
    
    public function edt_general_page_gateways_callback() {
        echo __( 'Here you can see a list of available gateways.', EDT_TEXT_DOMAIN );
        echo "<input type='hidden' name='".esc_html($this->settings_name)."[active_gateways][rubbish]' value='1' />";
    }
    
    public function plugin_short_code_callback() {
        echo "[EasyDonations]";
    }
    
    public function donate_form_function_callback() {
        echo __( 'You can use this code snippet to print the donate form in your theme: ', EDT_TEXT_DOMAIN ) . '<br/><code style="display:block;direction:ltr;">' . htmlspecialchars('<?php the_easy_donations_form(); ?>') . '</code>';
    }
    
    public function add_field_callback() {
        $active_fields = edt_ins()->options->get_option( 'donate_form_active_fields' );
        $required_fields = edt_ins()->options->get_option( 'donate_form_required_fields' );
        $last_field_number = edt_ins()->options->get_option( 'last_field_number' );
        ?>
<div class="add-field-block">
    <input class="settings-name" type="hidden" value="<?php echo esc_html($this->settings_name); ?>" />
    <input class="last-field" name="<?php echo esc_html($this->settings_name); ?>[last_field_number]" type="hidden" value="<?php echo ( ( is_numeric( $last_field_number ) ) ? $last_field_number : 100 ); ?>" />
     <?php
        if( ( $fields = edt_ins()->options->get_option('donate_form_fields') ) != '' ) {
            ?>
            
            <?php
            foreach( $fields as $cus_field => $val ) { 
                if( ! isset( $val['type'] ) ) $val['type'] = 'text';
                
                ?>
            <div class="custome-field">
                <input class="field-id" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][<?php echo esc_html($val['name']); ?>][id]" style="width:150px" value="<?php echo esc_html($val['id']); ?>" />
                <input class="field-name" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][<?php echo esc_html($val['name']); ?>][name]" style="width:150px" value="<?php echo esc_html($val['name']); ?>" />
                <label><?php _e( 'Field Title ', EDT_TEXT_DOMAIN ); ?></label>
                <input class="field-title" type="text" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][<?php echo esc_html($val['name']); ?>][title]" style="width:150px" value="<?php echo esc_html($val['title']); ?>" />
                <label><?php _e( 'Field Type ', EDT_TEXT_DOMAIN ); ?></label>
                <select class="field-type" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][<?php echo esc_html($val['name']); ?>][type]" style="width:150px" >
                    <option value="text"      <?php echo ( ( $val['type'] == 'text' ) ?  'selected="selected"' : ''); ?> ><?php _e( 'Text Field ', EDT_TEXT_DOMAIN ); ?></option>
                    <option value="textarea"  <?php echo ( ( $val['type'] == 'textarea' ) ?  'selected="selected"' : ''); ?> ><?php _e( 'Text Area Field ', EDT_TEXT_DOMAIN ); ?></option>
                    <option value="password"  <?php echo ( ( $val['type'] == 'password' ) ?  'selected="selected"' : ''); ?> ><?php _e( 'Password Field ', EDT_TEXT_DOMAIN ); ?></option>   
                </select>
                
                <input id="<?php echo esc_html($val['id']); ?>-active" class="field-active" type="checkbox" name="<?php echo esc_html($this->settings_name); ?>[donate_form_active_fields][<?php echo esc_html($val['name']); ?>]" <?php echo ( ( array_key_exists( $val['name'], $active_fields ) )? 'checked="checked"' : '' ) ?> />
                <label for="<?php echo esc_html($val['id']); ?>-active"><?php _e( 'Active', EDT_TEXT_DOMAIN ); ?></label>
                
                <input id="<?php echo esc_html($val['id']); ?>-req" class="field-required" type="checkbox" name="<?php echo esc_html($this->settings_name); ?>[donate_form_required_fields][<?php echo esc_html($val['name']); ?>]" <?php echo ( ( array_key_exists( $val['name'], $required_fields ) )? 'checked="checked"' : '' ) ?> />
                <label for="<?php echo esc_html($val['id']); ?>-req" ><?php _e( 'Required', EDT_TEXT_DOMAIN ); ?></label>

                <input type="button" class="button remove-field" name="submit" value="" title="<?php _e( 'Remove field', EDT_TEXT_DOMAIN ); ?>" style="font-family:dashicons;font-size:22px;" />
            </div>
            <?php 
            }
        }
?>
            <input type="button" class="button add-field"  name="submit" value="<?php _e( 'Add field', EDT_TEXT_DOMAIN ); ?>" />

            <div class="sample-field">
                <input class="field-id" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][custome-field-1][id]" style="width:150px" value="custome-field-1" />
                <input class="field-name" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][custome-field-1][name]" style="width:150px" value="custome-field-1" />
                <label><?php _e( 'Field Title ', EDT_TEXT_DOMAIN ); ?></label>
                <input class="field-title" type="text" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][custome-field-1][title]" style="width:150px"  />
                <label><?php _e( 'Field Type ', EDT_TEXT_DOMAIN ); ?></label>
                <select class="field-type" name="<?php echo esc_html($this->settings_name); ?>[donate_form_fields][custome-field-1][type]" style="width:150px" >
                    <option value="text"       ><?php _e( 'Text Field ', EDT_TEXT_DOMAIN ); ?></option>
                    <option value="textarea"   ><?php _e( 'Text Area Field ', EDT_TEXT_DOMAIN ); ?></option>
                    <option value="password"   ><?php _e( 'Password Field ', EDT_TEXT_DOMAIN ); ?></option>   
                </select>

                <input id="custome-field-1-active" class="field-active" type="checkbox" name="<?php echo esc_html($this->settings_name); ?>[donate_form_active_fields][custome-field-1]" />
                <label for="custome-field-1-active" class="field-active-label"><?php _e( 'Active', EDT_TEXT_DOMAIN ); ?></label>
                
                <input id="custome-field-1-req" class="field-required" type="checkbox" name="<?php echo esc_html($this->settings_name); ?>[donate_form_required_fields][custome-field-1]" />
                <label for="custome-field-1-req" class="field-required-label" ><?php _e( 'Required', EDT_TEXT_DOMAIN ); ?></label>

                <input type="button" class="button remove-field" name="submit" value="" title="<?php _e( 'Remove field', EDT_TEXT_DOMAIN ); ?>" style="font-family:dashicons;font-size:20px;" />
            </div>

</div>
<?php
    }
    
    public function amount_field_callback() {
        $field = ( edt_ins()->options->get_option('donate_form_amount_field') != '' ) ? edt_ins()->options->get_option('donate_form_amount_field') : array();
        $fixed_prices = ( isset( $field['fixed'] ) && isset( $field['fixed'] ) ) ? $field['fixed']  : array() ;
        $last_price_number = edt_ins()->options->get_option( 'last_price_number' );
        ?>
<div>
    <input class="last-price" name="<?php echo esc_html($this->settings_name); ?>[last_price_number]" type="hidden" value="<?php echo ( ( is_numeric( $last_price_number ) ) ? $last_price_number : 100 ); ?>" />
    <div>
        <label for="amount-field-fixed">
        <input id="amount-field-fixed" class="amount-type fixed" type="radio" name="<?php echo esc_html($this->settings_name); ?>[donate_form_amount_field][type]" value="fixed" <?php echo ( ( $field['type'] == 'fixed' ) ? 'checked="checked"' : '' ); ?> /><?php _e( 'Fixed value(s)', EDT_TEXT_DOMAIN ); ?>
        </label>
        <div class="amount-price-block">
        <?php foreach( $fixed_prices as $fp => $val ) {
                   ?>
            <div class="custome-amount-field">
                <input class="amount-field-id" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_amount_field][fixed][<?php echo esc_html($val['name']); ?>][id]" value="<?php echo esc_html($val['id']); ?>">
                <input class="amount-field-name" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_amount_field][fixed][<?php echo esc_html($val['name']); ?>][name]" value="<?php echo esc_html($val['name']); ?>">
                <input class="amount-field-type" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_amount_field][fixed][<?php echo esc_html($val['name']); ?>][type]" value="radio">

                <label for="<?php echo esc_html($val['id']); ?>" class="amount-field-value-label"><?php _e( 'Amount', EDT_TEXT_DOMAIN ); ?></label>
                <input id="<?php echo esc_html($val['id']); ?>" class="amount-field-value" type="text" name="<?php echo esc_html($this->settings_name); ?>[donate_form_amount_field][fixed][<?php echo esc_html($val['name']); ?>][value]" style="width:50px;" value="<?php echo esc_html($val['value']); ?>">
                
                <input type="button" class="button remove-field" name="submit" value="" title="<?php _e( 'Remove price', EDT_TEXT_DOMAIN ); ?>" style="font-family:dashicons;font-size:20px;" />
            </div>
        <?php } ?>

            <input type="button" class="button add-amount"  name="submit" value="<?php _e( 'Add Amount', EDT_TEXT_DOMAIN ); ?>" />
        </div>
        
        <label for="amount-field-user-input">
        <input id="amount-field-user-input" class="amount-type user-input" type="radio" name="<?php echo esc_html($this->settings_name); ?>[donate_form_amount_field][type]" value="user-input" <?php echo ( ( $field['type'] == 'user-input' ) ? 'checked="checked"' : '' ); ?> /><?php _e( 'Typed by user', EDT_TEXT_DOMAIN ); ?>
        </label>
    </div>
    <div class="sample-amount-field">
        <input class="amount-field-id" type="hidden" name="" value="">
        <input class="amount-field-name" type="hidden" name="" value="">
        <input class="amount-field-type" type="hidden" name="" value="radio">
        
        <label for="" class="amount-field-value-label"><?php _e( 'Amount', EDT_TEXT_DOMAIN ); ?></label>
        <input id="" class="amount-field-value" type="text" name="" style="width:50px;" value="">
        
        <input type="button" class="button remove-field" name="submit" value="" title="<?php _e( 'Remove price', EDT_TEXT_DOMAIN ); ?>" style="font-family:dashicons;font-size:20px;" />
    </div>
</div>
    <?php
    }
    
    public function background_file_select_field() {
        $hidden_field = $back_url = edt_ins()->options->get_option( 'form-background-image' );
        
        if( $back_url == '' || $back_url == '0'  ) {
            $back_url = EASYDONATIONS_PLUGIN_URL . "assets/img/default.png";
            $hidden_field = '0';
        }
        
        ?>
        <span class="form-back-img-wrap">
            <input type="hidden" class="back-default-img" value="<?php echo esc_url(EASYDONATIONS_PLUGIN_URL); ?>assets/img/default.png" />
            <input id="background-select-id" name="<?php echo esc_html($this->settings_name) . '[form-background-image]'; ?>" type="hidden" value="<?php echo esc_html($hidden_field); ?>" />
            <span class="form-back-img">
                <img id="background-select-field" src="<?php echo esc_url($back_url); ?>" style="width:300px;height:220px;" />
            </span>
            <span class="form-back-img">
                <input id="background-select" class="button" type="button" value="<?php _e( 'Select Background', EDT_TEXT_DOMAIN ); ?>" />
                <input id="background-reset" class="button" type="button" value="<?php _e( 'Reset Background', EDT_TEXT_DOMAIN ); ?>" />
            </span>
        </span>


        <?php
    }
    
    /**
     * Prints settings form
     * 
     * @param mixed $form_classes 
     * @param mixed $submit_btn 
     * @return mixed
     */
    public function print_form( $form_classes = null ) {
        if( ! is_null( $form_classes ) )
            $form_classes = 'class="' . $form_classes . '"';
        else
            $form_classes = '';
        
        settings_errors( 'afc_externalfontsettings' );
        
        ?>
        <form action='options.php' method='post'>
            <input class="settings_active_tab" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[settings_active_tab]" value="<?php echo esc_html(edt_ins()->options->get_option('settings_active_tab')); ?>" />
        <?php 
            settings_fields( 'edt_general_page_options_group' );
            $this->do_settings_sections_tabs( 'edt_general_page_options_group' );
            submit_button( __( 'Submit', EDT_TEXT_DOMAIN ) ); 
        ?>
        </form>
        <?php
    }
    
    /**
     * Hacked wp settings api sections output format , to let us show sections in tabs !!!
     * 
     * @param mixed $page 
     */
    private function do_settings_sections_tabs( $page ) {
        global $wp_settings_sections, $wp_settings_fields;

        if( ! isset( $wp_settings_sections[ $page ] ) ) {
            return;
        }

        ?>
        <style type="text/css">
            .settings-section {
                display: none;
            }

                .settings-section.visible {
                    display: block;
                }
        </style>
        <script type="text/javascript">
            jj = jQuery.noConflict();
            jj(document).ready(function () {
                jj('.nav-tab').click(function () {
                    jj('.nav-tab').each(function () {
                        jj(this).removeClass('nav-tab-active');
                    });
                    jj('.settings-section').each(function () {
                        jj(this).removeClass('visible');
                    });

                    jj(this).addClass('nav-tab-active');

                    var itemid = jj(this).attr('id');
                    jj('.settings-section.' + itemid).addClass('visible');

                    jj('.settings_active_tab').val(jj(this).attr('id'));
                });
            });
        </script>
        <?php
        echo '<h2 class="nav-tab-wrapper">';
        $counter = 0;
        
        $active_tab = edt_ins()->options->get_option( 'settings_active_tab' );
        
        foreach( (array)$wp_settings_sections[ $page ] as $section ) {
            if( ! isset( $section['title'] ) )
                continue;
            if( ( $counter == 0 && $active_tab == false ) || $active_tab == $section['id'] )
                echo "<a id='".esc_html($section['id'])." class='nav-tab nav-tab-active'>".esc_html($section['title'])."</a>";
            else
                echo "<a id='".esc_html($section['id'])." class='nav-tab '>".esc_html($section['title'])."</a>";
            $counter++;
        }

        echo '</h2>';

        $counter = 0;
        foreach( (array)$wp_settings_sections[$page] as $section ) {
            if( ( $counter == 0 && $active_tab == false ) || $active_tab == $section['id'] )
                echo "<div class='settings-section {$section['id']} visible'>";
            else
                echo "<div class='settings-section {$section['id']}'>";

            if( ! isset($section['title'] ) )
                continue;

            if( $section['callback'] )
                call_user_func( $section['callback'], $section );

            if( ! isset( $wp_settings_fields ) || !isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) )
                continue;

            echo '<table class="form-table">';
            do_settings_fields( $page, $section['id'] );
            echo '</table>';
            echo '</div>';
            
            $counter++;
        }
    }
    
    
    public function edt_general_page_validator( $input ) {
        
        if( ! is_null( $input ) ) {
            foreach( $input as $inp => $val ) {
                edt_ins()->options->add_option( $inp, $val );
            }
            $this->messages[] = __( 'Changes Saved.', EDT_TEXT_DOMAIN );
            $this->msg_type = 'updated';
        }
        else {
            $this->messages[] = __( 'You have made no changes.', EDT_TEXT_DOMAIN );
            $this->msg_type = 'error';
        }
        
        add_settings_error( $this->settings_name, 'edt', implode( '<br />', $this->messages ), $this->msg_type );
        if( $this->msg_type == 'updated' )
            edt_ins()->options->update_options();
    }
    
    public function select_active_currency() {
        $currencies = $this->get_currencies_list();
        $act_curr = edt_ins()->options->get_option( 'donate_form_active_currency' );
        ?>
        <input class="active-curr-title" type="hidden" name="<?php echo esc_html($this->settings_name); ?>[donate_form_active_currency][Title]" value="<?php echo ( ( isset( $act_curr['Title'] ) ) ? $act_curr['Title'] : $currencies[0]['Title'] ); ?>" />
        <select id="selected-currency" name="<?php echo esc_html($this->settings_name); ?>[donate_form_active_currency][Code]" >
            <?php foreach( $currencies as $curr ) : ?>
            <option class="curr-option" value="<?php echo $curr['Code']; ?>" <?php echo ( ( isset( $act_curr['Code'] ) && ( $act_curr['Code'] == $curr['Code'] ) ) ? 'selected="selected"' : '' ); ?> ><?php echo $curr['Title']; ?></option>
            <?php endforeach; ?>
        </select>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#selected-currency').on('change', function () {
                    var active_text = $('#selected-currency option:selected').text();
                    $('.active-curr-title').val(active_text);
                });
            });
        </script>
        <?php
    }
    
    public function get_currencies_list() {
        $currencies = array(
                array(
                    'Code' => 'IRR',
                    'Title' => __( 'Iranian Rial', EDT_TEXT_DOMAIN )
                ),
                array(
                    'Code' => 'IRT',
                    'Title' => __( 'Iranian Toman', EDT_TEXT_DOMAIN )
                )
            );

        return apply_filters( 'edt_currencies_list', $currencies );
    }
}