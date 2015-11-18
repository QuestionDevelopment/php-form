<?php
/**
 * Form Creation Class
 * https://github.com/QuestionDevelopment/php-form
 *
 * @version 0.9
 * @package php-form
 * @category class
 *
 * @license MIT http://opensource.org/licenses/MIT
 */

/*
 * Form Creation Class
 * 
 * This class dynamically creates forms
 * 
 */
class form {
    //form properties
    public $action = ""; //the location script will submit to
    public $auto_class = true; //autogenerate classes
    public $auto_id = true; //autogenerage ids (based off name)
    public $auto_name = true; //autogenerate name (based off label)
    public $auto_option_value = true; //autogenerate option value (based off name)
    public $auto_tab_index = true; //autogenerate tab index
    public $button = array(); //used to display extra buttons on the bottom of the form
    public $cache_directory = "xesm/plugin/form/cache/"; //cache directory where cache file lives
    public $cache = ""; //the local location of the cache file
    public $captcha = false; //generate captcha
    public $captcha_label = "Security Question:"; //text to display for captcha
    public $captcha_system = "simple"; //which type of captcha to use
    public $css_file = ""; //load css file = file location
    public $container = true; //when rendering output a container div
    public $debug = false; //display debug information
    public $editor = "/template/shelter/ckeditor/ckeditor.js"; //location of editor file
    public $enctype = "application/x-www-form-urlencoded"; //encoding type of form (multipart/form-data)
    public $honeypot = false; //a hidden field to catch bots
    public $id = ""; //the html id for the form element
    public $js = true; //will the form use javascript
    public $js_file = ""; //load js file = file location
    public $markup = "html"; //assigns the markup language for the format (xhtml)
    public $method = "post"; //form submission method (post/get)
    public $prefix = "form_"; //text that gets appended to various html elements
    public $prefix_js = "form"; //text that gets append to js variables
    public $reset = ""; //controls rendering and text for reset button
    public $submit = "Submit"; //controls rendering and text for submit button
    public $title = ""; //controls rendering and text for title
    public $validate = true; //validate form settings in backend system (adds overhead)

    //system properties
    private $autofocus_count = 0; //system count for how many autofocus are declared
    private $cache_status = "off"; //system indicator to determine status of cache system (off/primed/complete/clear)
    private $error = array(); //system errors
    private $item = array(); //system form item objects
    private $item_count = 0; //system count for $items
    private $warning = array(); //system warnings

    public function __construct($user_settings = "")
    {
        $this->init($user_settings);        
    }
    
    /*
     * Load form configuration settings post initialization
     *
     * @param var $user_settings User defined settings, can be a string or array
     */
    public function init($user_settings = "")
    {
        if (is_array($user_settings)){
            //add array of user settings
            $this->attributes($user_settings);
        } else if (!empty($user_settings)){
            //user is allowed to send a string if they just want to set the action
            $this->attribute("action", $user_settings);
        }
        return $this;
    }

    /*
     * Detects if form is using cache and if
     * it is then loads it
     */
    private function cache()
    {
        if (!empty($this->cache) AND !empty($this->cache_directory)) {
            if (stream_resolve_include_path($this->cache_directory.$this->cache)) {
                if ($this->cache_status == "clear"){
                    unlink($this->cache_directory.$this->cache);
                    $this->cache_status = "off";
                } else {
                    $this->cache_status = "complete";
                }
            } else {
                $this->cache_status = "primed";
            }
        }
    }

    /*
     * Load form attributes post initialization
     *
     * @param var $user_settings User defined settings, can be a string or array
     */
    public function attributes($user_settings)
    {
        if (is_array($user_settings) AND count($user_settings) > 0) {
            foreach ($user_settings as $user_setting => $user_setting_value) {
                $this->attribute($user_setting, $user_setting_value);
            }
        }
    }

    /*
     * Set attributes for the form and special fields
     * 
     * @param string $key The name of the form attribute to edit
     * @param string $value The value of the form attribute to edit
     */
    public function attribute($key, $val)
    {
        if (property_exists($this, $key)){ $this->$key = $val; }
    }

    /*
     * Add multiple items to form
     *
     * @param array items The items to add to the form
     */
    public function items($items = array())
    {
        if ($this->cache_status != "complete" AND is_array($items) AND count($items) > 0){
            foreach ($items as $item){
                $this->item($item);
            }
        }
    }

    /*
     * Adds item(s) to the form
     * 
     * @param object $item_settings User definied items settings, can be a string or array
     */
    public function item($item_object)
    {
        if ($this->cache_status != "complete" AND is_object($item_object)){          
            //increment form item count
            $this->item[$this->item_count] = $item_object;
            $this->item_count++;
        }
    }

    /*
     * Validates all form data
     * 
     * Validation should be done before rendering form data.  The render method assumes
     * that the $this->data is properly formatted and only contains valid data so it is 
     * important to sanitize everything perfectly here.
     */
    private function validate()
    {
        if ($this->validate) {
            $this->validate_form();
            $this->validate_cache();
            $this->validate_item();
        }
    }

    /*
     * Validates form specific data
     */
    private function validate_form()
    {
        $form_attributes = get_object_vars($this);
        foreach($form_attributes as $form_attribute => $val){
            switch ($form_attribute) :
                case 'action':
                case 'prefix':
                    if (!isset($val) || empty($val) || $val == null){
                        $this->error[] = "Form attribute [". $form_attribute ."] is required to have a value.";
                    }
                    break;
                case 'auto_class':
                case 'auto_id':
                case 'auto_tab_index':
                case 'captcha':
                case 'debug':
                case 'honeypot':
                case 'validate':
                    //boolean checks
                    if ($val !== true && $val !== false){
                        if (!isset($val) || $val == "" || $val == null){
                            $this->$form_attribute = true;
                            $this->warning[] = "Form attribute [". $form_attribute ."] is a boolean value and should be true or false.  System assigned : true.";
                        } else {
                            $this->$form_attribute = false;
                            $this->warning[] = "Form attribute [". $form_attribute ."] is a boolean value and should be true or false.  System assigned : false.";
                        }
                    }
                    break;
                case 'id':
                    if (preg_match('/\s/',$val)){
                        $this->error[] = "Form id has spacing in it";
                    } else if (ctype_digit(substr($val, 0, 1))){
                        $this->error[] = "Form id starts with a number";
                    }
                    break;
                case 'method':
                    if (strtolower($val) != "get" && strtolower($val) != "post"){
                        $this->warning[] = "Form attribute [". $form_attribute ."] must be set to 'get' or 'post'.  System assigned default value : post.";
                        $this->$form_attribute = "post";
                    }
                    break;
                case 'markup':
                    if (strtolower($val) != "xhtml" && strtolower($val) != "html"){
                        $this->warning[] = "Form attribute [". $form_attribute ."] must be set to 'html' or 'xhtml'.  System assigned default value : html.";
                        $this->$form_attribute = "html";
                    }
                    break;
                case 'enctype':
                    if (strtolower($val) != "multipart/form-data" && strtolower($val) != "application/x-www-form-urlencoded"){
                        $this->warning[] = "Form attribute [". $form_attribute ."] must be set to 'multipart/form-data' or 'application/x-www-form-urlencoded'.  System assigned default value : application/x-www-form-urlencoded.";
                        $this->$form_attribute = "application/x-www-form-urlencoded";
                    }
                    break;
            endswitch;
        }
    }

    /*
     *  Validate cache system
     */
    private function validate_cache()
    {
        if (!empty($this->cache)){
            if (!is_writable($this->cache_directory)){
                $this->warning[] = "Cache folder [".$this->cache_directory."] is not writeable. Cache system has been disabled.";
                $this->cache_status = "off";
            }
        }
    }
    /*
     * Validates item specific data
     */
    private function validate_item()
    {
        $autofocus_count = 0;
        $item_count = 1;
        $item_name_list = array();
        $item_id_list = array();
        $item_match_list = array();
        $item_tabindex_list = array();
        if (isset($this->item) AND is_array($this->item) AND count($this->item) > 0){
            foreach ($this->item as $item){
                $item->validate($item_count);
                $autofocus_count = $autofocus_count  + $item->autofocus_count;
                $item_id_list = array_merge($item_id_list, $item->id_list);
                $item_name_list = array_merge($item_name_list, $item->name_list);
                $item_tabindex_list = array_merge($item_tabindex_list, $item->tabindex_list);
                $this->error = array_merge($this->error, $item->error);
                $this->warning = array_merge($this->warning, $item->warning);
                if (isset($item->validation["match"]) AND !empty($item->validation["match"])){
                    $item_match_list[$item_count] = $item->validation["match"];
                }
                $item_count++;
            }
        } else {
            $this->error[] = "Form is required to have at least one item assigned to it";
        }
        //check all system data (lists) + autofocus + item_id list
        if ($autofocus_count > 1){
            $this->warning[] = "Form has multiple autofocus items assigned";
        }
        if(count(array_unique($item_id_list))<count($item_id_list)) {
            $temp_array_count = array_count_values($item_id_list);
            foreach ($temp_array_count as $key => $value){
                if ($value > 1){
                    $this->error[] = "Item ID [ ".$key." ] was entered [ ".$value." ] times";
                }
            }
        }
        if(count($item_match_list)) {
            foreach ($item_match_list as $item_count => $value){
                if (!in_array($value, $item_id_list)){
                    $this->warning[] = "Item [ ".$item_count." ] was provided with an match validation id that does not exist [ ".$value." ]";
                }
            }
        }
        
        if(count(array_unique($item_name_list))<count($item_name_list)) {
            $temp_array_count = array_count_values($item_name_list);
            foreach ($temp_array_count as $key => $value){
                if ($value > 1){
                    $this->error[] = "Item name [ ".$key." ] was entered [ ".$value." ] times";
                }
            }
        }
        if(count(array_unique($item_tabindex_list))<count($item_tabindex_list)) {
            $temp_array_count = array_count_values($item_tabindex_list);
            foreach ($temp_array_count as $key => $value){
                if ($value > 1){
                    $this->warning[] = "Item tabindex [ ".$key." ] was entered [ ".$value." ] times";
                }
            }
        }
    }
    
    /*
     * Renders all the javascript required for the form
     */
    private function validation_js($item, $option = array())
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

            $js .= $this->validation_js_generate($item, $name, $error_id);
        }
        return $js;
    }

    public function validation_js_generate($item, $name, $error_id)
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
     * Implements the honeypot system
     */
    private function honeypot()
    {
        //Add Honeypot
        if ($this->honeypot == true){
            $item = array();
            $item["type"] = "text";
            $item["name"] = $this->prefix."honeypot";
            $item["id"] = $this->prefix."honeypot";
            $item["class"] = array($this->prefix."honeypot");
            $item["label"] = "Leave blank to send form";
            $item["validation"] = array("equals" => "");
            $this->item($item);
        }
    }

    /*
     * Implements the auto_tab system
     */
    private function auto_attribute()
    {
        $tab_count = 1;
        foreach($this->item as $item){
            if ($this->auto_class == true) {
                $item->class[] = $this->prefix."item";
                $item->class[] = $this->prefix."item_".$item->type;
            } else {
                $item->auto_class = false;
            }
            if ($this->auto_name == true) {
                if (empty($item->name) AND !empty($item->label) AND $item->render_method != "option"){
                    $auto_name = preg_replace("/[^a-zA-Z\s]/", "", $item->label);
                    $item->name = strtolower(str_replace(" ","_",$auto_name));
                } else if (!empty($item->label) AND $item->render_method == "option"){
                    $temp_option_name = strtolower(preg_replace("/[^a-zA-Z0-9\s]/", "", $item->label));
                    if ($item->type == "checkbox"){
                        $temp_option_name .= "[]";
                    }                    
                    foreach ($item->option as &$option) {
                        if (!isset($option["name"])){
                            $option["name"] = $temp_option_name;
                        }
                    }
                }
            }
            if (($item->render_method == "option" || $item->render_method == "select") AND $this->auto_option_value == true){
                foreach ($item->option as &$option) {
                    if (!isset($option["value"]) AND isset($option["name"])){
                        $option["value"] = preg_replace("/[^a-zA-Z0-9\s]/", "", $option["name"]);
                    }
                }
            }
            if ($this->auto_id == true) {
                if (empty($item->id) AND !empty($item->name)){
                    $item->id = strtolower(preg_replace("/[^a-zA-Z\s]/", "", str_replace(" ", "_", $item->name)));
                }
            }
            if ($this->auto_tab_index == true) {
                if ($item->render_method != "text" AND $item->render_method != "hidden") {
                    if ($item->render_method == "option") {
                        foreach ($item->option as &$option) {
                            $option["tabindex"] = $tab_count;
                            $tab_count++;
                        }
                    } else {
                        $item->tabindex = $tab_count;
                        $tab_count++;
                    }
                }
            }
        }
    }

    /*
     * Handles debug output for form
     */
    private function message()
    {
        $html = "";
        if (count($this->error) > 0) {
            $html .= '<div id="'.$this->prefix.'error">Your form has the following errors:<ul>';
            foreach ($this->error as $error) {
                $html .= "<li>" . $error . "</li>";
            }
            $html .= "</ul></div>";
        }
        if ($this->debug) {
            if (count($this->warning) > 0) {
                $html .= '<div id="'.$this->prefix.'warning">Your form has the following warnings:<ul>';
                foreach ($this->warning as $warning) {
                    $html .= "<li>" . $warning . "</li>";
                }
                $html .= "</ul></div>";
            }
            echo "//<br/>// Form Data<br/>//<br/>";
            $form_data = get_object_vars($this);
            foreach ($form_data as $form_data_key => $form_data_value){
                if ($form_data_key != "c"){
                    echo $form_data_key." : ".$form_data_value."<br/>";
                }
            }
            foreach($this->item as $item){
                echo "//<br/>// Item Data<br/>//<br/>";
                $form_item_data = get_object_vars($item);
                foreach ($form_item_data as $form_data_key => $form_data_value){
                    if ($form_data_key != "c" AND $form_data_key != "validations" AND $form_data_key != "render_attributes" AND $form_data_key != "render_method_data"){
                        echo $form_data_key." : ";
                        if (is_array($form_data_value)){
                            echo print_r($form_data_value, true)."<br/>";
                        } else {
                            echo $form_data_value."<br/>";
                        }
                    }
                }
            }


        }
        return $html;
    }

    /*
    * Creates the form html
    *
    * @param booleon $html Output the form to the screen or return it
    */
    function render($output = true)
    {
        if ($this->cache_status == "complete") {
            $html = file_get_contents($this->cache_directory.$this->cache);
        } else {
            $this->auto_attribute();
            $this->validate();
            $html = $this->message();
            if (count($this->error) == 0) {
                $this->honeypot();
                //css_file
                if (!empty($this->css_file)) {
                    $html .= '<link rel="stylesheet" href="' . $this->css_file . '">';
                }
                //jsfile
                if (!empty($this->js_file)) {
                    $html .= '<script src="' . $this->js_file . '"></script>';
                }
                //Container Div
                if ($this->container) {
                    $html .= '<div class="' . $this->prefix . 'container">';
                }
                //Title
                if (!empty($this->title)) {
                    $html .= '<div class="' . $this->prefix . 'title">' . $this->title . '</div>';
                }
                //Form tag
                $html .= '<form method="' . $this->method . '"';
                $html .= ' enctype="' . $this->enctype . '"';
                $html .= ' action="' . $this->action . '"';
                if (!empty($this->id)) {
                    $html .= ' id="' . $this->id . '"';
                }
                if ($this->js) {
                    $html .= ' onsubmit="return(' . $this->prefix_js . 'Validate());"';
                }
                $html .= '>';
                foreach ($this->item as $item) {
                    $html .= $item->render($this->markup, $this->prefix);
                } // end foreach item

                if ($this->js AND $this->captcha) {
                    $html .= $this->captcha($this->captcha_system, $this->captcha_label);
                }

                if (!empty($this->reset)) {
                    $reset_name = preg_replace("/[^a-zA-Z\s]/", "", $this->reset);
                    $reset_name = str_replace(" ", "_", strtolower($reset_name));
                    $html .= "<div class='" . $this->prefix . "reset'><input class='" . $this->prefix . "reset_input' type='reset' name='" . $reset_name . "' value='" . $this->reset . "'></div>";
                }
                if (!empty($this->submit)) {
                    $submit_name = preg_replace("/[^a-zA-Z\s]/", "", $this->submit);
                    $submit_name = str_replace(" ", "_", strtolower($submit_name));
                    $html .= "<div class='" . $this->prefix . "submit'><input class='" . $this->prefix . "submit_input' type='submit' name='" . $submit_name . "' value='" . $this->submit . "'></div>";
                }
                
                if (count($this->button)) {
                    foreach ($this->button as $button){
                        if (isset($button["title"])){
                            if (!isset($button["name"])){
                                $button["name"] = preg_replace("/[^a-zA-Z\s]/", "", $button["title"]);
                                $button["name"] = str_replace(" ", "_", strtolower($button["name"]));                                
                            }
                            $class = $this->prefix . "button";
                            if (isset($button["id"])){ $class .= " ".$button["id"]."_container"; }
                            $class_button = $this->prefix . "button_input";
                            if (isset($button["class"])){ $class_button .= " ".$button["class"]; }
                            
                            $html .= "<div class='" . $class . "'><button type='button'";
                            if (isset($button["id"])){ $html .= "id='".$button["id"]."' "; }
                            if (isset($button["onclick"])){ $html .= 'onclick="'.$button["onclick"].'" '; }
                            $html .= "class='" . $class_button . "' name='".$button["name"]."'>";
                            $html .= $button["title"];                       
                            $html .= "</button></div>";
                        }
                    }
                }

                $html .= '</form>';
                if ($this->container) {
                    $html .= '</div>';
                }

                if ($this->js) {
                    $html .= $this->render_js($this->item, $this->prefix_js, $this->editor);
                }

                if ($this->cache_status == "primed") {
                    file_put_contents($this->cache_directory . $this->cache, $html);
                    $this->cache_status == "complete";
                }
            }
        }
        if ($output){ echo $html; }
        else { return $html; }
    }
    
     /*
    * Renders all the javascript required for the form
    */
    public function render_js($items, $prefix_js = "", $editor = false)
    {
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

        $js .= "function ".$this->prefix_js."Elem(id){var elem = false;if(document.getElementById){elem=document.getElementById(id);}else if(document.all){elem=document.all[id];}else if(document.layers){elem=document.layers[id];}return elem;};";
        $js .= "function ".$this->prefix_js."ApplyError(itemid){var tempElem=".$this->prefix_js."Elem(itemid);tempElem.className += ' ".$this->prefix_js."_error';tempElem.focus(); tempElem.scrollIntoView(true); };";
        $js .= "function ".$this->prefix_js."RemoveError(itemid){var tempElem=".$this->prefix_js."Elem(itemid);if(tempElem.className){tempElem.className.replace( /(?:^|\s)".$this->prefix_js."_error(?!\S)/ , '' );};};";
        $js .= "function ".$this->prefix_js."Validate(){";

        //Loop through items
        foreach($items as $item){
            if ($item->render_method != "output"){
                //store id if editor so it can be rendered
                if ($item->type == "editor"){
                    $editor_id[] = $item->id;
                }
                if ($item->type == "radio" || $item->type == "checkbox"){
                    foreach ($item->option as $option){
                        $js .= $this->validation_js($item, $option);
                    }
                } else {
                    $js .= $this->validation_js($item);
                }
            }
        }
        if ($this->captcha){
            $captchaReversed = $this->captcha_code("reverse");
            $js .= "var captchaInput = ".$this->prefix_js."Elem('".$this->prefix_js."Captcha').value;";
            $js .= "if (btoa(captchaInput.charAt(0)) != '".base64_encode($captchaReversed[0])."'){ alert('The first characters of your captcha is incorrect'); ".$this->prefix_js."ApplyError('".$this->prefix_js."CaptchaWrapper'); return false; } else { ".$this->prefix_js."RemoveError('".$this->prefix_js."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(1)) != '".base64_encode($captchaReversed[1])."'){ alert('The second characters of your captcha is incorrect'); ".$this->prefix_js."ApplyError('".$this->prefix_js."CaptchaWrapper'); return false; } else { ".$this->prefix_js."RemoveError('".$this->prefix_js."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(2)) != '".base64_encode($captchaReversed[2])."'){ alert('The third characters of your captcha is incorrect'); ".$this->prefix_js."ApplyError('".$this->prefix_js."CaptchaWrapper'); return false; } else { ".$this->prefix_js."RemoveError('".$this->prefix_js."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(3)) != '".base64_encode($captchaReversed[3])."'){ alert('The fourth characters of your captcha is incorrect'); ".$this->prefix_js."ApplyError('".$this->prefix_js."CaptchaWrapper'); return false; } else { ".$this->prefix_js."RemoveError('".$this->prefix_js."Captcha'); };";
            $js .= "if (btoa(captchaInput.charAt(4)) != '".base64_encode($captchaReversed[4])."'){ alert('The fifth characters of your captcha is incorrect'); ".$this->prefix_js."ApplyError('".$this->prefix_js."CaptchaWrapper'); return false; } else { ".$this->prefix_js."RemoveError('".$this->prefix_js."Captcha'); };";
        }
        $js .= "return true;}";
        //$js .= 'window.addEventListener("load", function (){ '.$this->prefix_js.'Validate(); });';

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

