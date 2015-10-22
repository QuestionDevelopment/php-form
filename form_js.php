<?php
/**
 * Form Javascript Class
 *
 * @version 0.9
 * @package xesm
 * @subpackage plugin
 * @category class
 *
 * @license MIT http://opensource.org/licenses/MIT
 */
namespace plugin\form;
/*
 * Form Javascript Class
 *
 * Handles javascript for form system
 *
 */

class form_js {
    public $prefix = "";
    public $captcha = false;
    public $captcha_label = "";
    public $captcha_system = "";

    /*
    * Renders all the javascript required for the form
    */
    public function render($items, $prefix = "", $editor = false)
    {
        $this->prefix = $prefix;
        $editor_id = array();

        $js = "<script>";
        $js .= "var emailRegex = /(.+)@(.+){2,}\.(.+){2,}/;";
        $js .= "var phoneRegex = /(\W|^)[(]{0,1}\d{3}[)]{0,1}[\s-]{0,1}\d{3}[\s-]{0,1}\d{4}(\W|$)/;";
        $js .= "var zipRegex = /^\d{5}$|^\d{5}-\d{4}$/;";
        $js .= "var alphaRegex = /^[a-zA-Z]+$/;";
        $js .= "var numericRegex = /^[0-9]+$/;";
        $js .= "var alpha_numericRegex = /^[a-zA-Z0-9]+$/;";
        $js .= "var alpha_numericSpaceRegex = /^[a-zA-Z0-9 ]+$/;";
        $js .= "var dateRegex = /^\d{2}\/\d{2}\/\d{4}$/;";
        $js .= "var dateTimeRegex = /^[0,1]?\d\/(([0-2]?\d)|([3][01]))\/((199\d)|([2-9]\d{3}))\s[0-2]?[0-9]:[0-5][0-9] (AM|am|aM|Am|PM|pm|pM|Pm)?$/;";
        $js .= "var timeRegex = /^ *(1[0-2]|[1-9]):[0-5][0-9] *(a|p|A|P)(m|M) *$/;";
        $js .= "var urlRegex = /(http|ftp|https):\/\/[\w\-_]+(\.[\w\-_]+)+([\w\-\.,@?^=%&amp;:/~\+#]*[\w\-\@?^=%&amp;/~\+#])?/;";
        $js .= "var priceRegex = /^(\d*([.,](?=\d{3}))?\d+)+((?!\2)[.,]\d\d)?$/;";

        $js .= "function ".$this->prefix."Elem(id){var elem = false;if(document.getElementById){elem=document.getElementById(id);}else if(document.all){elem=document.all[id];}else if(document.layers){elem=document.layers[id];}return elem;};";
        $js .= "function ".$this->prefix."ApplyError(itemid){var tempElem=".$this->prefix."Elem(itemid);tempElem.className += ' ".$this->prefix."_error';tempElem.focus(); tempElem.scrollIntoView(true); };";
        $js .= "function ".$this->prefix."RemoveError(itemid){var tempElem=".$this->prefix."Elem(itemid);if(tempElem.className){tempElem.className.replace( /(?:^|\s)".$this->prefix."_error(?!\S)/ , '' );};};";
        $js .= "function ".$this->prefix."Validate(){";

        //Loop through items
        foreach($items as $item){
            if ($item->render_method != "output"){
                //store id if editor so it can be rendered
                if ($item->type == "editor"){
                    $editor_id[] = $item->id;
                }
                if ($item->type == "radio" || $item->type == "checkbox"){
                    foreach ($item->option as $option){
                        $js .= $this->validation($item, $option);
                    }
                } else {
                    $js .= $this->validation($item);
                }
            }
        }
        if ($this->captcha){
            $captchaReversed = $this->captcha_code("reverse");
            $js .= "var captchaInput = ".$this->prefix."Elem('".$this->prefix."Captcha').value;";
            $js .= "if (btoa(captchaInput.charAt(0)) != '".base64_encode($captchaReversed[0])."'){ alert('The first characters of your captcha is incorrect'); ".$this->prefix."ApplyError('".$this->prefix."CaptchaWrapper'); return false; } else { ".$this->prefix."RemoveError('".$this->prefix."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(1)) != '".base64_encode($captchaReversed[1])."'){ alert('The second characters of your captcha is incorrect'); ".$this->prefix."ApplyError('".$this->prefix."CaptchaWrapper'); return false; } else { ".$this->prefix."RemoveError('".$this->prefix."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(2)) != '".base64_encode($captchaReversed[2])."'){ alert('The third characters of your captcha is incorrect'); ".$this->prefix."ApplyError('".$this->prefix."CaptchaWrapper'); return false; } else { ".$this->prefix."RemoveError('".$this->prefix."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(3)) != '".base64_encode($captchaReversed[3])."'){ alert('The fourth characters of your captcha is incorrect'); ".$this->prefix."ApplyError('".$this->prefix."CaptchaWrapper'); return false; } else { ".$this->prefix."RemoveError('".$this->prefix."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(4)) != '".base64_encode($captchaReversed[4])."'){ alert('The fifth characters of your captcha is incorrect'); ".$this->prefix."ApplyError('".$this->prefix."CaptchaWrapper'); return false; } else { ".$this->prefix."RemoveError('".$this->prefix."Captcha'); };";
        }
        $js .= "return true;}";
        //$js .= 'window.addEventListener("load", function (){ '.$this->prefix.'Validate(); });';

        $js .= "</script>";
        
        //Editor JS
        if (count($editor_id) > 0){
            $js .= '<script src="'.$editor.'"></script>';
            $js .= "<script>";
            foreach ($editor_id as $editor_instance){
                $js .= 'window.addEventListener("load", function (){ CKEDITOR.replace("'.$editor_instance.'"); });';
            }
            $js .= "</script>";
        }
        return $js;
    }

    /*
     * Renders all the javascript required for the form
     */
    private function validation($item, $option = array())
    {
        $js = "";
        if (!empty($item->id) AND count($item->validation)){
            //for option based items we want to use the id + label from the primary item
            if (isset($item->id) AND $item->id != ""){
                $error_id = $item->id."_container";
            }
            if (isset($item->label) AND $item->label != ""){
                $item_label = $item->label;
            }
            if (count($option)){
                $item = $option;
                if (isset($item->label) AND $item->label != ""){
                    $item_label = $item->label;
                }
            }
            //determine best name to use
            if (isset($item_label) and $item_label != ""){
                $name = $item_label;
            } else if (isset($item->name) and $item->name != ""){
                $name = $item->name;
            } else {
                $name = $item->id;
            }
            $name = rtrim(trim($name),":");

            $js .= $this->validation_js($item, $name, $error_id);
        }
        return $js;
    }

    public function validation_js($item, $name, $error_id)
    {
        $js = "";
        foreach ($item->validation as $validation => $validation_value){
            //maxlength
            if ($validation == "maxlength"){
                $js .= "if(".$this->prefix."Elem('".$item->id."').value.length > ".$validation_value."){alert('".$name." exceeds the the maximium length of ".$validation_value."');";
                if (isset($error_id)) { $js .= $this->prefix."ApplyError('".$error_id."'); return false; } else { ".$this->prefix."RemoveError('".$error_id."'); };"; }
                else { $js .= "};"; }
            } else if ($validation == "minlength"){
                $js .= "if(".$this->prefix."Elem('".$item->id."').value.length < ".$validation_value."){alert('".$name." does not reach the minimium length of ".$validation_value."');";
                if (isset($error_id)) { $js .= $this->prefix."ApplyError('".$error_id."'); return false; } else { ".$this->prefix."RemoveError('".$error_id."'); };"; }
                else { $js .= "};"; }
            } else if ($validation == "required"){
                if ($item->render_method == "option"){
                    $js .= "if(".$this->prefix."Elem('".$item->id."').checked == false){alert('".$name." is a required field and must have a value');";
                    if (isset($error_id)) { $js .= $this->prefix."ApplyError('".$error_id."'); return false; } else { ".$this->prefix."RemoveError('".$error_id."'); };"; }
                    else { $js .= "};"; }
                } else {
                    $js .= "if(".$this->prefix."Elem('".$item->id."').value.length == 0){alert('".$name." is a required field and must have a value');";
                    if (isset($error_id)) { $js .= $this->prefix."ApplyError('".$error_id."'); return false; } else { ".$this->prefix."RemoveError('".$error_id."'); };"; }
                    else { $js .= "};"; }
                }
            } else if ($validation == "equals"){
                if (empty($validation_value)){
                    $equal_error = $name . " is required to have the empty value";
                } else {
                    $equal_error = $name . " is required to have the value of : ".$validation_value;
                }
                $js .= "if(".$this->prefix."Elem('".$item->id."').value != '".$validation_value."'){alert('".$equal_error."');";
                if (isset($error_id)) { $js .= $this->prefix."ApplyError('".$error_id."'); return false; } else { ".$this->prefix."RemoveError('".$error_id."'); };"; }
                else { $js .= "};"; }
            } else if ($validation == "match"){
                $match_elem_value = $this->prefix."Elem('".$validation_value."').value";
                $match_error = $name . " must match the value of form field ".$validation_value. " with a current value of ";
                $js .= "if(".$this->prefix."Elem('".$item->id."').value != ".$match_elem_value."){var errorOutput = '".$match_error."'+".$match_elem_value.";alert(errorOutput);";
                if (isset($error_id)) { $js .= $this->prefix."ApplyError('".$error_id."'); return false; } else { ".$this->prefix."RemoveError('".$error_id."'); };"; }
                else { $js .= "};"; }
            }  else {
                if ($validation == "email") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && emailRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be a valid email address');";
                } else if ($validation == "phone") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && phoneRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be a valid phone number [7, 10, 11 digits with or without hypthens]');";
                } else if ($validation == "zip") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && zipRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be a valid zip code [5 or 5-4 digits]');";
                } else if ($validation == "alpha") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && alphaRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " is only allowed to have alphabetic characters');";
                } else if ($validation == "numeric") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && numericRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " is only allowed to have numeric characters');";
                } else if ($validation == "alpha_numeric") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && alpha_numericRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " is only allowed to have alphanumberic characters');";
                } else if ($validation == "alpha_numberic_space") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && alpha_numericSpaceRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " is only allowed to have alphanumberic characters and spaces');";
                } else if ($validation == "date") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && dateRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be a valid date [XX/XX/XXXX]');";
                } else if ($validation == "dateTime") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && dateTimeRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be a valid date [DD/MM/YY HH:MM AM]');";
                } else if ($validation == "time") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && timeRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be in a valid time format [HH:MM AM]');";
                } else if ($validation == "url") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && urlRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be in a valid url [http://www.example.com]');";
                } else if ($validation == "price") {
                    $js .= "if(" . $this->prefix . "Elem('" . $item->id . "').value.length > 0 && priceRegex.test(" . $this->prefix . "Elem('" . $item->id . "').value) == false){alert('" . $name . " must be in a valid price [XXX.XX]');";
                }
                if (isset($error_id)) {
                    $js .= $this->prefix . "ApplyError('" . $error_id . "'); return false; } else { " . $this->prefix . "RemoveError('" . $error_id . "'); };";
                } else {
                    $js .= "};";
                }
            }
        }
        return $js;
    }

    /*
     * Renders all the data for the captcha system
     */
    public function captcha($captcha_system, $captcha_label)
    {
        $this->captcha = true;
        $this->captcha_system = $captcha_system;
        $this->captcha_label = $captcha_label;

        $css = "";
        $html = "<div id='".$this->prefix."_captcha_container'>";
        $html .= "<label for='".$this->prefix."captcha'>".$this->data["form"]['captcha']."</label>";
        $html .= "<div id='".$this->prefix."captcha_text'>";
        $html .= "Please enter the following text in reverse : ";
        $hide_class_array = array();
        $characters = "0123456789abcdefghijklmnopqrstuvwxyz";
        $code_array = str_split($this->captcha_code());
        foreach ($code_array as $code_character){
            //Insert fake spans to try and prevent code scraping
            $faux_spans = rand(1,5);
            $i = 0;
            while ($i < $faux_spans){
                $html .= "<span class='";
                $faux_class = "char";
                for ($p = 0; $p < 6; $p++) {
                    $faux_class .= $characters[mt_rand(0, 35)];
                }
                $hide_class_array[] = $faux_class;
                $html .= $faux_class;
                $html .= "'>".$characters[mt_rand(0, 35)]."</span>";
                $i++;
            }
            //Insert real character
            $html .= "<span class='char";
            for ($p = 0; $p < 6; $p++) {
                $html .= $characters[mt_rand(0, 35)];
            }
            $html .= "'>".$code_character."</span>";
        }
        $html .= "</div>";
        $html .= "<input type='text' id='".$this->prefix."Captcha' name='".$this->prefix."Captcha'>";
        $html .= "</div>";
        $css = "<style>";
        foreach ($hide_class_array as $hide_class_item){
            $css .= ".".$hide_class_item."{display:none;}";
        }
        $css .= "</style>";

        return $css.$html;
    }

    /*
     * Generates the captcha code for the client
     */
    public function captcha_code($mode = "default")
    {
        $user_generated_string = $_SERVER['HTTP_USER_AGENT'].$_SERVER['SERVER_NAME'].$_SERVER['SERVER_ADDR'].$_SERVER['REMOTE_ADDR'];
        $hash = hash_hmac('crc32', $user_generated_string, 'xxeeTT');
        $code = substr($hash, 0, 5);
        if ($mode == "reversed"){
            return strrev($code);
        } else {
            return $code;
        }
    }


}
