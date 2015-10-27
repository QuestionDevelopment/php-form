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

    /*
     * Load form configuration settings post initialization
     *
     * @param var $user_settings User defined settings, can be a string or array
     */
    public function init($user_settings = "")
    {
        $this->cache();
        if (is_array($user_settings)){
            //add array of user settings
            $this->attributes($user_settings);
        } else if (!empty($user_settings)){
            //user is allowed to send a string if they just want to set the action
            $this->attribute("action", $user_settings);
        }
        return this;
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
                    $temp_option_name = strtolower(preg_replace("/[^a-zA-Z0-9\s]/", "", $item->label))."[]";
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
    function render($form_js, $output = true)
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

                if ($this->js AND $this->captcha AND is_object($form_js)) {
                    $html .= $form_js->captcha($this->captcha_system, $this->captcha_label);
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
                $html .= '</form>';
                if ($this->container) {
                    $html .= '</div>';
                }

                if ($this->js AND is_object($form_js)) {
                    $html .= $form_js->render($this->item, $this->prefix_js, $this->editor);
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
}

