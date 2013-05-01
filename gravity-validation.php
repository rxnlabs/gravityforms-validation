<?php
/*
Plugin Name: Gravity Validation
Plugin URI: http://example.com/
Description: Gravity Validation is a WordPress plugin that works with Gravity Forms to provide inline form validation
Version: 0.2
Author: De'Yonte W.
Author URI: http://example.com/
*/

/**
 * Copyright (c) 2013 De'Yonte W. All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * **********************************************************************
 */

//add_action('wp_head','gv_load_script');
add_action("gform_enqueue_scripts", "gravity_validation", 10, 2);
add_action('wp_ajax_gravity_validation_ajax','gravity_validation_ajax');
add_action('wp_ajax_nopriv_gravity_validation_ajax','gravity_validation_ajax');

function gravity_validation($form,$is_ajax){
	if( !empty($form['gf_inline_validation']) ):
		wp_enqueue_script( 'jquery');
		$nonce = wp_create_nonce( 'gv' );
		$form_id = "gform_".$form['id'];
		add_action('wp_footer','gv_enqueue_scripts');
		?>
		<script type="text/javascript">
		//http://www.htmlgoodies.com/beyond/javascript/article.php/3724571/Using-Multiple-JavaScript-Onload-Functions.htm
		function gv_addLoadEvent(func) {
		  var oldonload = window.onload;
		  if (typeof window.onload != 'function') {
		    window.onload = func;
		  } else {
		    window.onload = function() {
		      if (oldonload) {
		        oldonload();
		      }
		      func();
		    }
		  }
		}

		function gv_<?php echo $form['id'];?>(){
			var special_field_types = ['file','radio','checkbox'];
			var field_container, field_type, found_field;
			field_container = field_type = found_field = null;

			jQuery('form#gform_<?php echo $form["id"];?>').bind('jqv.field.result', function(event, field, errorFound, prompText){
				field_container = jQuery(field).parents('li:first');
				field_type = jQuery(field).attr('type');
				found_field = jQuery.inArray(field_type,special_field_types);

				if( errorFound && found_field == -1 ){
					jQuery(field).addClass('gverror');
				}
				else if( errorFound && found_field != -1 ){
					if( field_type == "radio" ){
						//field_container = jQuery(field).parentsUntil('form','li');
						//field_container = jQuery(field).parents('li').eq(1);
						field_container = jQuery(field).closest('ul');
						jQuery(field_container).addClass('gverror');
					}
					else{
						field_container = jQuery(field).closest('.ginput_container');
						jQuery(field_container).addClass('gverror');
					}
				}
				else if( !errorFound && found_field == -1 ){
					jQuery(field).addClass('gvsuccess');
				}
				else if( !errorFound && found_field != -1 ){
					if( field_type == "radio" ){
						//field_container = jQuery(field).parentsUntil('form','li');
						//field_container = jQuery(field).parents('li').eq(1);
						field_container = jQuery(field).closest('ul');
						jQuery(field_container).addClass('gvsuccess');
					}
					else{
						field_container = jQuery(field).closest('.ginput_container');
						jQuery(field_container).addClass('gvsuccess');
					}
				}
			});
			<?php
			//boolean to determine if the form has any required fields
			$formvalidate = false;
			foreach( $form["fields"] as $key => $field ):
				if( $field['isRequired'] ):
					$formvalidate = true;//set the form for validation once we come across a required field

					$regular_types = array('text','textarea','multiselect','select','number','date','time','phone','website','post_title','post_content','post_excerpt','post_image', 'radio');
					if( in_array($field['type'], $regular_types) ):
						$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'"]';
						?>
						jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
						jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
						jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
						<?php
					elseif( $field['type'] == "name" ):
						if( $field['nameFormat'] != "simple" ):
							foreach( $field['inputs'] as $input ):
								$field_id = $field_id = 'form#'.$form_id.' [name="input_'.$input["id"].'"]';
								?>
								//name
								jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
								jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
								jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
								<?php
							endforeach;
						else:
							$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'"]';
							?>
							jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
							jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
							<?php
						endif;
					elseif( $field['type'] == "address" ):
						foreach( $field['inputs'] as $input ):
							$field_id = $field_id = 'form#'.$form_id.' [name="input_'.$input["id"].'"]';
							?>
							//name
							jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
							jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
							<?php
						endforeach;
					elseif( $field['type'] == "email" ):
						if( !empty($field['emailConfirmEnabled']) ):
							$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'"]';
							$field_id_2 = 'form#'.$form_id.' [name="input_'.$field["id"].'_2"]';
							?>
							jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
							jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
							jQuery('<?php echo $field_id_2;?>').attr('data-validation-engine','validate[required]');
							jQuery('<?php echo $field_id_2;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id_2;?>').attr('data-prompt-position','topRight:5');
							<?php
						else:
							$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'"]';
							?>
							jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
							jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
							<?php
						endif;
					elseif( $field['type'] == "checkbox" ):
						foreach( $field['inputs'] as $input ):
							$field_id = $field_id = 'form#'.$form_id.' [name="input_'.$input["id"].'"]';
							?>
							//checkbox
							jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required');
							jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
							<?php
						endforeach;
					elseif( $field['type'] == "list" ):
						$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'[]"]';
						?>
						jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
						jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
						jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
						<?php
					elseif( $field['type'] == "fileupload" ):
						$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'"]';
						?>
						jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
						jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
						jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
						<?php
					elseif( $field['type'] == "post_tags" OR $field['type'] == "post_category" ):
						if( $field['inputType'] != "multiselect" ):
							$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'"]';
							?>
							jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
							jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
							<?php
						elseif( $field['inputType'] == "multiselect" ):
							$field_id = 'form#'.$form_id.' [name="input_'.$field["id"].'[]"]';
							?>
							jQuery('<?php echo $field_id;?>').attr('data-validation-engine','validate[required]');
							jQuery('<?php echo $field_id;?>').attr('data-errormessage','<?php echo (isset($field["errorMessage"])?$field["errorMessage"]:"This field is required");?>');
							jQuery('<?php echo $field_id;?>').attr('data-prompt-position','topRight:5');
							<?php
						endif;
					endif;
				endif;
			endforeach;
			if($formvalidate):?>
				jQuery('form#gform_<?php echo $form["id"];?>').validationEngine();	
			<?php endif;?>

			jQuery('form#<?php echo $form_id;?> input[type="text"], form#<?php echo $form_id;?> textarea, form#<?php echo $form_id;?> select').on('focus',function(){
				//field_container = jQuery(this).parents('li.gfield');
				
				field_container = jQuery(this).parents('li:first');
				field_type = jQuery(this).attr('type');
				found_field = jQuery.inArray(field_type,special_field_types);

				//remove and add the class from the input element itself
				if( found_field == -1 ){
					jQuery(this).removeClass('gverror');
					jQuery(this).removeClass('gvsuccess');
				}
				else if( found_field != -1){
					//remove and add the class from the parent element
					if( field_type == "radio" ){
						//field_container = jQuery(field).parentsUntil('form','li');
						//field_container = jQuery(field).parents('li').eq(1);
						jQuery(field_container).removeClass('gverror');
						jQuery(field_container).removeClass('gvsuccess');
					}
					else{
						jQuery(field_container).removeClass('gverror');
						jQuery(field_container).removeClass('gvsuccess');
					}
				}
				
				//jQuery(this).children('p.gverror').remove();
			});
		}
		gv_addLoadEvent(gv_<?php echo $form['id'];?>);
		</script>
		
	<?php
	endif;
	echo var_dump($form['fields']);
}

//add_action("gform_field_css_class", "gravity_validation_class", 10, 3);

function gravity_validation_class($classes, $field, $form){
	if( $field['isRequired'] )
		$classes .= " validate[required]";

	return $classes;
}

function gravity_validation_ajax(){
	check_ajax_referer( 'gv' );
	$required = $_POST['field_required'];
	if( $required == "true" ):
		$value = $_POST['field_value'];

		if( empty($value) ):
			$result = array('type'=>'error');
		elseif( !empty($value) ):
			$result = array('type'=>'success');
		endif;

	elseif( $required == "false" ):
		$result = array('type'=>'success');
	endif;

	header('Content-Type: application/json');
	header("Cache-Control: no-cache, must-revalidate");
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	echo json_encode($result);
	die();
}


function gv_enqueue_scripts(){
	wp_register_style('gravity.validation', plugins_url('css/gravity.validation.css',__FILE__), false, '0.1');
	wp_enqueue_style('gravity.validation');
	wp_register_style('jquery.validation.engine', plugins_url('js/jquery.validation.engine/css/validationEngine.jquery.css',__FILE__), false, '2.6.1');
	wp_enqueue_style('jquery.validation.engine');
	wp_register_script('jquery.validation.engine', plugins_url('js/jquery.validation.engine/js/jquery.validationEngine.js',__FILE__), array('jquery'), '2.6.1', true );
	wp_enqueue_script('jquery.validation.engine');
	wp_register_script('jquery.validation.engine.en', plugins_url('js/jquery.validation.engine/js/languages/jquery.validationEngine-en.js',__FILE__), array('jquery'), '2.6.1', true );
	wp_enqueue_script('jquery.validation.engine.en');
}


function gv_load_script(){
	
	?>

<?php
}



add_filter('gform_form_settings', 'gf_inline_validation', 10, 2);
function gf_inline_validation($settings, $form) {

	$gf_inline_validation = rgars($form, 'gf_inline_validation');
	if( !empty($gf_inline_validation) )
		$gf_inline_validate = 'checked="checked"';
	else
		$gf_inline_validate = null;
	
    $settings['Form Options']['gf_inline_validation'] = __('
        <tr>
            <th>Inline Validation <a href="javascript:void(0);" class="tooltip tooltip_form_field_placeholder" tooltip="&lt;h6&gt;Enable Inline Validation &lt;/h6&gt;Validate gravity forms without user submitting form">(?)</a></th>
            <td><input value="1" '.$gf_inline_validate.' id="gf_inline_validation" name="gf_inline_validation" type="checkbox"> <label for="gf_inline_validation">Enable inline validation</label></td>
        </tr>');

    return $settings;
}

// save your custom form setting
add_filter('gform_pre_form_settings_save', 'save_my_custom_form_setting');
function save_my_custom_form_setting($form) {

    $form['gf_inline_validation'] = rgpost('gf_inline_validation');
    return $form;
}