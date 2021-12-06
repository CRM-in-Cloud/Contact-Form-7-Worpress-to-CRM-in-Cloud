<?php

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

class QS_CF7_crm_in_cloud_admin{


  /**
   * Holds the plugin options
   */
  private $options;

  /**
   * Holds athe admin notices class
   */
  private $admin_notices;

  /**
   * Plugn is active or not
   */
  private $plugin_active;

  /**
   * API errors array
   */
  private $api_errors;

    /**
   * CRM endpoint
   */
  private $endpoint;
  
  public function __construct(){

    $this->textdomain = 'qs-cf7-crm-in-cloud';
    
    $this->endpoint = 'https://api.crmincloud.it/api/latest/lead';

    $this->admin_notices = new QS_Admin_notices();

    $this->api_errors = array();

    $this->register_hooks();

    if (isset($_POST['verify'])) {
      echo $wpcf7_crm_in_cloud_data["api_key"];
      die();
      }
  
  }
  /**
   * Check if Contact Form 7 is active
   */
  public function verify_dependencies(){
    if( ! is_plugin_active('contact-form-7/wp-contact-form-7.php') ){
      $notice = array(
        'id'                  => 'cf7-not-active',
        'type'                => 'warning',
        'notice'              => __( 'Contact Form 7 CRM in Cloud integrations requires CONTACT FORM 7 Plugin to be installed and active' ,$this->textdomain ),
        'dismissable_forever' => false
      );

      $this->admin_notices->wp_add_notice( $notice );
    }
  }
  /**
   * Registers the required admin hooks
   */
  public function register_hooks(){
    /**
     * Check if required plugins are active
     */
    add_action( 'admin_init', array( $this, 'verify_dependencies' ) );

    /*before sending email to user actions */
    add_action( 'wpcf7_before_send_mail', array( $this , 'qs_cf7_send_data_to_crm_in_cloud' ) );

    /* adds another tab to contact form 7 screen */
    add_filter( "wpcf7_editor_panels" ,array( $this , "add_integrations_tab" ) , 1 , 1 );

    /* actions to handle while saving the form */
    add_action( "wpcf7_save_contact_form" ,array( $this , "qs_save_contact_form_details") , 10 , 1 );

    add_filter( "wpcf7_contact_form_properties" ,array( $this , "add_sf_properties" ) , 10 , 2 );
  }

  /**
   * Sets the form additional properties
   */
  function add_sf_properties( $properties , $contact_form ){

    //add mail tags to allowed properties
    $properties["wpcf7_crm_in_cloud_data"]     = isset($properties["wpcf7_crm_in_cloud_data"]) ? $properties["wpcf7_crm_in_cloud_data"]         : array();
    $properties["wpcf7_crm_in_cloud_data_map"] = isset($properties["wpcf7_crm_in_cloud_data_map"]) ? $properties["wpcf7_crm_in_cloud_data_map"] : array();
    $properties["template"]           = isset($properties["template"]) ? $properties["template"] : '';
    $properties["json_template"]      = isset($properties["json_template"]) ? stripslashes($properties["json_template"]) : '';

    return $properties;
  }

  /**
   * Adds a new tab on conract form 7 screen
   */
  function add_integrations_tab($panels){

    $integration_panel = array(
      'title'    => __( 'CRM in Cloud Integration' , $this->textdomain ),
      'callback' => array( $this, 'wpcf7_integrations' )
    );

    $panels["qs-cf7-crm-in-cloud-integration"] = $integration_panel;

    return $panels;

  }
  /**
   * Collect the mail tags from the form
   */
  function get_mail_tags( $post ){
    $tags = apply_filters( 'qs_cf7_collect_mail_tags' , $post->scan_form_tags() );

    foreach ( (array) $tags as $tag ) {
      $type = trim( $tag['type'], ' *' );
      if ( empty( $type ) || empty( $tag['name'] ) ) {
        continue;
      } elseif ( ! empty( $args['include'] ) ) {
        if ( ! in_array( $type, $args['include'] ) ) {
          continue;
        }
      } elseif ( ! empty( $args['exclude'] ) ) {
        if ( in_array( $type, $args['exclude'] ) ) {
          continue;
        }
      }
      $mailtags[] = $tag;
    }

    return $mailtags;
  }
  /**
   * The admin tab display, settings and instructions to the admin user
   */
  function wpcf7_integrations( $post ) {

    $wpcf7_crm_in_cloud_data                = $post->prop( 'wpcf7_crm_in_cloud_data' );
    $wpcf7_crm_in_cloud_data_map            = $post->prop( 'wpcf7_crm_in_cloud_data_map' );
    $wpcf7_crm_in_cloud_data_template     = $post->prop( 'template' );
    $wpcf7_crm_in_cloud_json_data_template  = $post->prop( 'json_template' );
    $mail_tags                     = $this->get_mail_tags( $post );

    $wpcf7_crm_in_cloud_data["base_url"]     = isset( $wpcf7_crm_in_cloud_data["base_url"] ) ? $wpcf7_crm_in_cloud_data["base_url"] : $this->endpoint;
    $wpcf7_crm_in_cloud_data["api_key"]   = isset( $wpcf7_crm_in_cloud_data["api_key"] ) ? $wpcf7_crm_in_cloud_data["api_key"] : '';
    $wpcf7_crm_in_cloud_data["send_to_crm_in_cloud"]  = isset( $wpcf7_crm_in_cloud_data["send_to_crm_in_cloud"] ) ? $wpcf7_crm_in_cloud_data["send_to_crm_in_cloud"]   : '';
    $wpcf7_crm_in_cloud_data["debug_log"]    = true;

    $debug_url                     = get_post_meta( $post->id() , 'qs_cf7_crm_in_cloud_debug_url' , true );
    $debug_result                  = get_post_meta( $post->id() , 'qs_cf7_crm_in_cloud_debug_result' , true );
    $debug_params                  = get_post_meta( $post->id() , 'qs_cf7_crm_in_cloud_debug_params' , true );

    $error_logs            = get_post_meta( $post->id() , 'api_errors' , true );
    
    $json_placeholder = __('*** THIS IS AN EXAMPLE JSON MODEL ***
    {
      "name": "[text-name]",
      "surname": "[text-surname]",
      "companyName": "[text-compay]",
      "address": "[text-address]",
      "city": "[text-city]",
      "state": "[text-state]",
      "emails": "[text-email]",
      "phone": "[text-phonenumber]",
      "note": "[text-note]",
      "privacySettings": [{
        "checked": true,
        "checkedDate": "' . gmdate("Y-m-d\TH:i:s.00\Z") . '",
        "privacyTypeManagementId": "code"
      }],
      "source": "website"
    }' , '');

    
    //if($wpcf7_crm_in_cloud_data["api_key"] !== '')
    //  $json_schema = $this->get_schema($wpcf7_crm_in_cloud_data["api_key"]);
?>

        <h2><?php echo esc_html( __( 'CRM in Cloud Integration', $this->textdomain ) ); ?></h2>

        <fieldset>
          <?php do_action( 'before_base_fields' , $post ); ?>

          <div class="cf7_row">

              <label for="wpcf7-sf-send_to_crm_in_cloud">
                  <input type="checkbox" id="wpcf7-sf-send_to_crm_in_cloud" name="wpcf7-sf[send_to_crm_in_cloud]" <?php checked( $wpcf7_crm_in_cloud_data["send_to_crm_in_cloud"] , "on" );?>/>
                  <?php _e( 'Send to CRM in Cloud?' , $this->textdomain );?>
              </label>

              </div>
              <div class="cf7_row">

              <label for="wpcf7-sf-skip_mail">
                  <input type="checkbox" id="wpcf7-sf-skip_mail" name="wpcf7-sf[skip_mail]" <?php checked( $wpcf7_crm_in_cloud_data["skip_mail"] , "on" );?>/>
                  <?php _e( 'Skip send mail?' , $this->textdomain );?>
              </label>

          </div>
         <!--
          <div class="cf7_row">
              <label for="wpcf7-sf-base_url">
                  <?php _e( 'Base url' , $this->textdomain );?>
                  <input type="text" id="wpcf7-sf-base_url" name="wpcf7-sf[base_url]" class="large-text" value="<?php echo $wpcf7_crm_in_cloud_data["base_url"];?>" />
              </label>
          </div>
          -->
          <div class="cf7_row">
              <label for="wpcf7-sf-api_key">
                  <?php _e( 'CRM Api Key' , $this->textdomain );?>
                  <input type="text" id="wpcf7-sf-api_key" name="wpcf7-sf[api_key]" class="large-text" value="<?php echo $wpcf7_crm_in_cloud_data["api_key"];?>"
                   placeholder="es. 53BuGE4JBLRmFEvIq6e7G8Owm3GzM4pIxPPEYA3s7wBiJsDaIw" />
              </label><input type="button" class="button" id="verify_api_key" name="verify_api_key" value="<?php esc_html_e( 'Verify key', $this->textdomain ); ?>" /><span id="verify_api_key_result"></span>
          </div>
          
          <hr>

            <?php do_action( 'after_base_fields' , $post ); ?>
          
        </fieldset>   

    <fieldset>
      <div class="cf7_row">
        <h2><?php echo esc_html( __( 'JSON Template', $this->textdomain ) ); ?></h2>
    
        <!--
        <?php foreach( $json_schema["properties"] as $json_node) : ?>          
          <?php if(is_array($json_node) && isset($json_node['friendlyName'])):?>
            <?php echo $json_node[0];?>
            <?php echo $json_node["friendlyName"];?>
          <?php endif;?>
        <?php endforeach;?>
          -->  
        <legend id="cf7_crm_in_cloud_json_schema_builder">
          <?php foreach( $mail_tags as $mail_tag) : ?>
            <?php if( $mail_tag->type == 'checkbox'):?>
              <?php foreach( $mail_tag->values as $checkbox_row ):?>
                <input type="hidden" id="sf-<?php echo $name;?>" name="qs_wpcf7_crm_in_cloud_map[<?php echo $mail_tag->name;?>][<?php echo $checkbox_row;?>]" value="<?php echo isset($wpcf7_crm_in_cloud_data[$mail_tag->name][$checkbox_row]) ? $wpcf7_crm_in_cloud_data[$mail_tag->name][$checkbox_row] : "";?>" /></<input>
                <span class="json_mailtag mailtag code">[<?php echo $mail_tag->name;?>-<?php echo $checkbox_row;?>]</span>
                <span class="cf7_crm_in_cloud_mapper"><select class="cf7_crm_in_cloud_option"><option value="none">Ignore</option></select><br/></span>
              <?php endforeach;?>
            <?php else:?>
              <input type="hidden" id="sf-<?php echo $mail_tag->name;?>" name="qs_wpcf7_crm_in_cloud_map[<?php echo $mail_tag->name;?>]" value="<?php echo isset($wpcf7_crm_in_cloud_data[$mail_tag->name]) ? $wpcf7_crm_in_cloud_data[$mail_tag->name] : "";?>" />
              <span class="json_mailtag mailtag code">[<?php echo $mail_tag->name;?>]</span>
              <span class="cf7_crm_in_cloud_mapper"><select class="cf7_crm_in_cloud_option"><option value="none">Ignore</option></select><br/></span>
            <?php endif;?>
          <?php endforeach; ?>
          <span class="cf7_crm_in_cloud_mapper"><input type="button" class="button" id="gererate_json" name="gererate_json" value="<?php esc_html_e( 'generate JSON', $this->textdomain ); ?>" /></span>
        </legend>

        <textarea id="json_template" name="json_template" rows="12" dir="ltr" placeholder="<?php echo esc_attr( $json_placeholder );?>"><?php echo isset( $wpcf7_crm_in_cloud_json_data_template ) ? $wpcf7_crm_in_cloud_json_data_template : ""; ?></textarea>
      </div>
    </fieldset>

    <?php if( $wpcf7_crm_in_cloud_data['debug_log'] ):?>
    <fieldset>
      <div class="cf7_row">
        <label class="debug-log-trigger">
          + <?php _e( 'DEBUG LOG ( View last transmission attempt )' , $this->textdomain); ?>
        </label>
        <div class="debug-log-wrap">
          <h3 class="debug_log_title"><?php _e( 'LAST API CALL' , $this->textdomain );?></h3>
          <div class="debug_log">
            <h4><?php _e( 'Called url' , $this->textdomain );?>:</h4>
            <textarea rows="1"><?php echo trim(esc_attr( $debug_url ));?></textarea>
            <h4><?php _e( 'Params' , $this->textdomain );?>:</h4>
            <textarea rows="10"><?php print_r( $debug_params );?></textarea>
            <h4><?php _e( 'Remote server result' , $this->textdomain );?>:</h4>
            <textarea rows="10"><?php print_r( $debug_result );?></textarea>
            <h4><?php _e( 'Error logs' , $this->textdomain );?>:</h4>
            <textarea rows="10"><?php print_r( $error_logs );?></textarea>
          </div>
        </div>
      </div>
    </fieldset>
<?php
endif;
  }

  /**
   * Saves the API settings
   */
  public function qs_save_contact_form_details( $contact_form ){

    $properties = $contact_form->get_properties();

    $properties['wpcf7_crm_in_cloud_data']     = isset( $_POST["wpcf7-sf"] ) ? $_POST["wpcf7-sf"] : '';
    $properties['wpcf7_crm_in_cloud_data_map'] = isset( $_POST["qs_wpcf7_crm_in_cloud_map"] ) ? $_POST["qs_wpcf7_crm_in_cloud_map"] : '';
    $properties['template']           = isset( $_POST["template"] ) ? $_POST["template"] : '';
    $properties['json_template']  = isset( $_POST["json_template"] ) ? $_POST["json_template"] : '';

    //$record_type = isset( $qs_cf7_data['input_type'] ) ? $qs_cf7_data['input_type'] : 'params';
    $record_type = isset( $properties['wpcf7_crm_in_cloud_data']['input_type'] ) ? $properties['wpcf7_crm_in_cloud_data']['input_type'] : 'params';
    if( $record_type == 'json' || $record_type == 'xml' ){
      $template = $record_type == 'json' ? $properties['json_template'] : $properties['template'];
      preg_match_all("/\[(\w+(-\d+)?)\]/", $template, $matches, PREG_PATTERN_ORDER); 
      $properties['wpcf7_crm_in_cloud_data_map'] = array_merge(array_fill_keys($matches[1], ''), $properties['wpcf7_crm_in_cloud_data_map']);
    }
    
    $contact_form->set_properties( $properties );

  }

  /**
   * The handler that will send the data to the api
   */
  public function qs_cf7_send_data_to_crm_in_cloud( $WPCF7_ContactForm ) {

    $this->clear_error_log( $WPCF7_ContactForm->id() );

    $submission = WPCF7_Submission::get_instance();

    $url                       = $submission->get_meta( 'url' );
    $this->post            = $WPCF7_ContactForm;
    $qs_cf7_data               = $WPCF7_ContactForm->prop( 'wpcf7_crm_in_cloud_data' );
    $qs_cf7_data_map           = $WPCF7_ContactForm->prop( 'wpcf7_crm_in_cloud_data_map' );
    $qs_cf7_data_template      = $WPCF7_ContactForm->prop( 'template' );
    $qs_cf7_data_json_template = $WPCF7_ContactForm->prop( 'json_template' );
    $qs_cf7_data['debug_log']  = true; //always save last call results for debugging

    /* check if the form is marked to skip send mail */
    if (isset($qs_cf7_data["skip_mail"]) && $qs_cf7_data["skip_mail"] == "on") {
        add_filter('wpcf7_skip_mail','__return_true');
    }

    /* check if the form is marked to be sent via API */
    if( isset( $qs_cf7_data["send_to_crm_in_cloud"] ) && $qs_cf7_data["send_to_crm_in_cloud"] == "on" ){

        $qs_cf7_data_template = stripslashes($qs_cf7_data_json_template);

        $record = $this->get_record( $submission , $qs_cf7_data_map , "json", $template = $qs_cf7_data_template );

      $record["url"] = isset( $qs_cf7_data["base_url"] ) ? $qs_cf7_data["base_url"] : $this->endpoint;

      if( isset( $record["url"] ) && $record["url"] ){

        do_action( 'qs_cf7_crm_in_cloud_before_sent_to_crm_in_cloud' , $record );

        $response = $this->send_lead( $record , $qs_cf7_data['debug_log'] , $qs_cf7_data["api_key"] );

        if( is_wp_error( $response ) ){
          $this->log_error( $response , $WPCF7_ContactForm->id() );
        }else{
          do_action( 'qs_cf7_crm_in_cloud_after_sent_to_crm_in_cloud' , $record , $response );
        }
      }
    }

  }
  /**
   * CREATE ERROR LOG FOR RECENT API TRANSMISSION ATTEMPT
   */
  function log_error( $wp_error , $post_id ){
    //error log
    $this->api_errors[] = $wp_error;

    update_post_meta( $post_id , 'api_errors' , $this->api_errors );
  }

  function clear_error_log( $post_id ){
    delete_post_meta( $post_id , 'api_errors' );
  }
  /**
   * Convert the form keys to the API keys according to the mapping instructions
   */
  function get_record( $submission , $qs_cf7_data_map , $type = "json", $template = "" ){
    $submited_data = $submission->get_posted_data();
   //TODO: implement upload file -> https://github.com/kennym/cf7-to-api/commit/1e47b9179ec1d6878e64efdedcb800dc83ab7ebb    
    $record = array();


      foreach( $qs_cf7_data_map as $form_key => $qs_cf7_form_key ){
        if( is_array( $qs_cf7_form_key ) ){
          //arrange checkbox arrays
          foreach( $submited_data[$form_key] as $value ){
            if( $value ){
              $value = apply_filters( 'set_record_value' , $value , $qs_cf7_form_key );

              $template = str_replace( "[{$form_key}-{$value}]", $value, $template );
            }
          }
        }else{

          $value = isset($submited_data[$form_key]) ? $submited_data[$form_key] : "";
          
          $value = preg_replace('/\r|\n/', '\\n', $value);
          $value = str_replace('\\n\\n', '\n', $value);

          //flatten radio
          if( is_array( $value ) ){
            if(count($value)  == 1)
              $value = reset( $value );
            else 
              $value = implode(";",$value);
          }
           // handle boolean acceptance fields
           if( $this->isAcceptanceField($form_key) ) {
            $value = $value == "" ? "false" : "true";
          }

          //$template = str_replace( "[{$form_key}]", $value, $template );

          // replace "[$form_key]" with json-encoded value
          $template = preg_replace( "/(\")?\[{$form_key}\](\")?/", json_encode($value), $template );

        }
      }

      //clean unchanged tags
      foreach( $qs_cf7_data_map as $form_key => $qs_cf7_form_key ){
        if( is_array( $qs_cf7_form_key ) ){
          foreach( $qs_cf7_form_key as $field_suffix=> $api_name ){
            $template = str_replace( "[{$form_key}-{$field_suffix}]", '', $template );
          }
        }

      }

      $record["fields"] = $template;
    $record = apply_filters( 'cf7api_create_record', $record , $submited_data , $qs_cf7_data_map , $type , $template );
    
  
    return $record;
  }


  private function get_schema( $api_key = null ){
    global $wp_version;

    $args = array(
      'timeout'     => 5,
      'redirection' => 5,
      'httpversion' => '1.0',
      'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
      'blocking'    => true,
      'headers'     => array(),
      'cookies'     => array(),
      'body'        => null,
      'compress'    => false,
      'decompress'  => true,
      'sslverify'   => true,
      'stream'      => false,
      'filename'    => null
  ); 
    $args['headers']['Content-Type'] = 'application/json';
    if ( isset($api_key) && $api_key !== '' ) {
      $args['headers']['ApiKey'] = $api_key;
    }
    $remote_url = 'https://api.crmincloud.it/api/latest/lead/GetPolymorphicSchema?TypeNameHandling=none';
    $result = wp_remote_get( $remote_url, $args );
    $body = wp_remote_retrieve_body($result);
    if(wp_remote_retrieve_response_code($result) !== 401){
      //var_dump(json_decode($body));
      return json_decode($body, true);
    }else{
      echo json_decode($body)->message; 
      return NULL;
    }
    //do_action('after_qs_cf7_crm_in_cloud_get_schema' , $result );
  } 

  /**
   * Send the lead using wp_remote
   */

  private function send_lead( $record , $debug = false , $api_key = null ){
    global $wp_version;

    $lead = $record["fields"];
    $url  = $record["url"];

    $args = array(
        'timeout'     => 5,
        'redirection' => 5,
        'httpversion' => '1.0',
        'user-agent'  => 'WordPress/' . $wp_version . '; ' . home_url(),
        'blocking'    => true,
        'headers'     => array(),
        'cookies'     => array(),
        'body'        => $lead,
        'compress'    => false,
        'decompress'  => true,
        'sslverify'   => true,
        'stream'      => false,
        'filename'    => null
      );

      if ( isset($api_key) && $api_key !== '' ) {
        $args['headers']['ApiKey'] = $api_key;
      }
        //$args['headers']['Crm-WebApiRules'] = 'LeadMustBeUniqueByEmail=true'; 
        $args['headers']['Content-Type'] = 'application/json'; 
		
		// https://app.crmincloud.it/api/v1/Docs/en/Home#Options
    $args['headers']['Crm-TagsStyle'] = 'AdaptiveStringOnlyName';		
		$args['headers']['Crm-EmailStyle'] = 'ValueOnly';
		$args['headers']['Crm-PhoneStyle'] = 'ValueOnly';
		$args['headers']['Crm-LeadCategoriesStyle'] = 'AdaptiveStringOnlyName';
		$args['headers']['Crm-FreeFieldsStyle'] = 'AsValueOnlyParentProperties';
		$args['headers']['Crm-LeadPriorityIdStyle'] = 'AdaptiveStringOnlyDescription';
		$args['headers']['Crm-LeadProductInterestIdStyle'] = 'AdaptiveStringOnlyDescription';
		$args['headers']['Crm-LeadRatingIdStyle'] = 'AdaptiveStringOnlyDescription';
		$args['headers']['Crm-LeadStatusIdStyle'] = 'AdaptiveStringOnlyDescription';
		$args['headers']['Crm-GlobalEnumStyle'] = 'AdaptiveString';
		$args['headers']['Crm-AnagraphicIndustryIdStyle'] = 'AdaptiveStringOnlyDescription';
		$args['headers']['Crm-CommercialZoneStyle'] = 'AdaptiveStringOnlyCode';
		$args['headers']['Crm-BusinessRolesStyle'] = 'AdaptiveStringOnlyName';
		$args['headers']['Crm-AnagraphicSourceIdStyle'] = 'AdaptiveStringOnlyDescription';
		$args['headers']['Crm-AnagraphicCompanyTypeIdStyle'] = 'AdaptiveStringOnlyDescription';
		$args['headers']['Crm-CompanyCategoriesStyle'] = 'AdaptiveStringOnlyName';
		$args['headers']['Crm-ContactCategoriesStyle'] = 'AdaptiveStringOnlyName';
		$args['headers']['Crm-SalesPersonsStyle'] = 'AdaptiveStringOnlyUserAccount';
		$args['headers']['PrivacyTypeManagementIdStyle'] = 'AdaptiveStringOnlyCode';		

        $json = $this->parse_json( $lead );
        if( is_wp_error( $json ) ){
          return $json;
        }else{
          $args['body'] = $json;
        }


      $args   = apply_filters( 'qs_cf7_crm_in_cloud_get_args' , $args );

      $url    = apply_filters( 'qs_cf7_crm_in_cloud_post_url' , $url );
      
      $result = wp_remote_post( $url , $args );

    if( $debug ){
      update_post_meta( $this->post->id() , 'qs_cf7_crm_in_cloud_debug_url' , $record["url"] );
      update_post_meta( $this->post->id() , 'qs_cf7_crm_in_cloud_debug_params' , $lead );
      update_post_meta( $this->post->id() , 'qs_cf7_crm_in_cloud_debug_result' , $result );
    }

    return do_action('after_qs_cf7_crm_in_cloud_send_lead' , $result , $record );
  }

  private function parse_json( $string ){

    $json = json_decode( $string );

    if ( json_last_error() === JSON_ERROR_NONE) {
      return json_encode( $json );
    }

    if ( json_last_error() === 0) {
      return json_encode( $json );
    }

    return new WP_Error( 'json-error' , json_last_error() );

  }

  /**
   * @param string $field_name
   * @return bool
   */
  private function isAcceptanceField($field_name) {
    $field = $this->post->scan_form_tags(
      array(
        'type' => 'acceptance',
        'name' => $field_name
      )
    );

    return count($field) == 1;
  }

}
