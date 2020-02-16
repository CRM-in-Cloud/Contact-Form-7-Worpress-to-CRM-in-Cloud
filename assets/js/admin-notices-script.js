jQuery(document).ready(function(){
    //dismiss admin notice forever
    qs_cf7_dismiss_admin_note();

    check_input_type();

    toggle_debug_log();

    toggle_request_type();
    
    verify_api_key();

    fill_options();
});

function verify_api_key() { 
    jQuery('#verify_api_key').click(function() {
        var key = jQuery('#wpcf7-sf-api_key').val();
        var label = jQuery('#verify_api_key_result');
        if (!key)
        label.text("Missing apikey");
        else
        jQuery.ajax({
            url: "https://api.crmincloud.it/api/latest/auth/me",
            type: 'GET',
            dataType: 'json',
            headers: { "ApiKey": key },
            contentType: 'application/json; charset=utf-8',
            success: function (result) {
                label.text("Welcome " + result.name);
                fill_options();
            },
            error: function (error) {
                label.text("ApiKey is not valid!");                
            }
        });
       
    });
}

function fill_options() {
    var key = jQuery('#wpcf7-sf-api_key').val();
    if (key)
        jQuery.ajax({
            url: "https://api.crmincloud.it/api/latest/lead/GetPolymorphicSchema?TypeNameHandling=none",
            type: 'GET',
            dataType: 'json',
            headers: { "ApiKey": key },
            contentType: 'application/json; charset=utf-8',
            success: function(result) {

                jQuery(".cf7_crm_in_cloud_mapper").show();
                var o = result["properties"];
                for (var i in o) {
                    if (!!o[i] && typeof (o[i]) == "object") {
                        if (i[0] != '$') {
                        jQuery(".cf7_crm_in_cloud_option").append(new Option(i + " (" + o[i]["friendlyName"] + ")", i));
                            //          if(o[i]["foreginKey"])
                        }
                    }
                }
                jQuery('#gererate_json').click(function() {
                    generate_json_schema();
                });

                set_model_select();
            },
            error: function(error) {
                var label = jQuery('#verify_api_key_result');
                label.text("ApiKey is not valid!").css("color", "red");
            }
        });
}

function set_model_select() { 
    var currentJSON = swap(JSON.parse(jQuery("#json_template").text()));
    jQuery("#cf7_crm_in_cloud_json_schema_builder .code").each(function(i, o) {
        var name = currentJSON[jQuery(o).text()];
        if (name)
        jQuery(o).next().find("select").val(name);
    });

}

function swap(json) {
    var ret = {};
    for(var key in json){
      ret[json[key]] = key;
    }
    return ret;
}
  
function generate_json_schema() { 
    var json = {};
    jQuery("#cf7_crm_in_cloud_json_schema_builder .code").each(function(i, o) {        
        var option = jQuery(o).next().find("select").val();
        if (option != "none")
            json[option] = jQuery(o).text();
    });
    jQuery("#json_template").text(JSON.stringify(json, null, 2));
}

function call_php() { 
     /*
        var ajaxurl = '#',
        data =  {'verify': true};
        jQuery.post(ajaxurl, data, function (response) {
            alert(response);
        });
        */
}

function toggle_debug_log(){
    jQuery( document ).on( 'click' , '.debug-log-trigger' , function(){
        jQuery( '.debug-log-wrap' ).slideToggle();
    });
}
function toggle_request_type(){
    jQuery( document ).on( 'change' , '#wpcf7-sf-input_type', function(){
        check_input_type();
    });
}
// Check input type on API Integration TAB
function check_input_type(){
    if( jQuery( '#wpcf7-sf-input_type' ).length ){
        var input_type = jQuery( '#wpcf7-sf-input_type' ).val();

        jQuery( '[data-qsindex]').fadeOut();

        jQuery( '[data-qsindex*="'+input_type+'"]' ).fadeIn();
    }
}

function qs_cf7_dismiss_admin_note(){
    jQuery(".qs-cf7-crm-in-cloud-dismiss-notice-forever").click(function(){

        var id = jQuery( this ).attr( 'id' );

        jQuery.ajax({
            type: "post",
            url: ajaxurl,
            data: {
                action: 'qs_cf7_crm_in_cloud_admin_dismiss_notices',
                id : id
            },

        });
    });
}
