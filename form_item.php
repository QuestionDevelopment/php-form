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
class form_item {
    //item properties
    public $autofocus = false; //gives field users cursor on page load
    public $button = array(); //buttons to render for this item
    public $class = array(); //css classes to apply to item
    public $content = ""; //text to output available to all item (required by output)
    public $disabled = false; //can the item be interacted with by the user
    public $divider = ":"; //string between label + field
    public $help = ""; //text to display to provide instruction
    public $id = ""; //the id of the item
    public $label = ""; //the label to display to visitor for the item
    public $name = ""; //the html name of the item
    public $onchange = ""; //the javascript string to run if event is triggered
    public $onclick = ""; //the javascript string to run if event is triggered
    public $onfocus = ""; //the javascript string to run if event is triggered
    public $onkeyup = ""; //the javascript string to run if event is triggered
    public $onkeydown = ""; //the javascript string to run if event is triggered
    public $onmouseover = ""; //the javascript string to run if event is triggered
    public $onmouseout = ""; //the javascript string to run if event is triggered
    public $option = array(); //the option/child data required by some items
    public $placeholder = ""; //text to display before a user clicks the input
    public $readonly = false; //item is disabled but gets sent with the form
    public $required_symbol = "*"; //indication that the field is required
    public $tabindex = 0; //the order a user will transverse items if using tab key
    public $type = "text"; //the type (input/radio/h1 etc.) the item is.
    public $validation = array(); //checks for form item (list of options below)
    public $value = ""; //the value assigned to the item (can also be an array for checkbox + radio)

    //system properties
    public $auto_class = true; //do we automatically apply classes to form item elements
    public $autofocus_count = 0; // a count of the number of time autofocus is used by this script
    public $error = array(); //system variable that stores item errors
    public $id_list = array(); // a list of all html ids used in this item
    public $markup = "html"; //the markup version used to output form (ie. xhtml)
    public $name_list = array(); // a list of all html names used in this item (except for ones ending with [])
    public $prefix = "form_"; //the html class prefix (passed from form)
    public $render_method = "input"; //how the form render system will handle the item
    public $tabindex_list = array(); //a list of all tabindex values used in this item
    public $warning = array(); //system variable that stores item warnings


    //system data
    public $validations = array("equals","match","required","minlength","maxlength","email","phone","zip","alpha","numeric","alpha_numeric","alpha_numberic_space","date","date_time","time","url","price");
    public $render_attributes = array(
        "autofocus" => "boolean",
        "disabled" => "boolean",
        "id" => "value",
        "name" => "value",
        "onchange" => "value",
        "onclick" => "value",
        "onfocus" => "value",
        "onkeyup" => "value",
        "onkeydown" => "value",
        "onmouseover" => "value",
        "onmouseout" => "value",
        "placeholder" => "value",
        "readonly" => "boolean",
        "tabindex" => "value",
        "type" => "value",
        "value" => "value_empty"
    );
    public $render_method_data = array(
        "input" => array(
            "type" => array("text","password"),
            "required" => array("type","name"),
            "prohibited" => array("option"),
            "method" => "render_input"
        ),
        "hidden" => array(
            "type" => array("hidden"),
            "required" => array("type","name"),
            "prohibited" => array("autofocus","disabled","help","onchange","onclick","onfocus","onkeyup","onkeydown","onmouseover","onmouseout","option","placeholder","tabindex","text"),
            "method" => "render_input"
        ),
        "file" => array(
            "type" => array("file"),
            "required" => array("type","name"),
            "prohibited" => array("option","placeholder"),
            "method" => "render_input"
        ),
        "output" => array(
            "type" => array("div","h1","h2","h3","h4","h5","h6"),
            "required" => array("type","content"),
            "prohibited" => array("autofocus","disabled","help","label","name","onclick","onmouseover","onmouseout","option","readonly","tabindex","value"),
            "method" => "render_output"
        ),
        "open_close" => array(
            "type" => array("textarea"),
            "required" => array("type","name"),
            "prohibited" => array("option"),
            "method" => "render_open_close"
        ),
        "button" => array(
            "type" => array("button"),
            "required" => array("type"),
            "prohibited" => array("autofocus","disabled","help","label","onclick","onmouseover","onmouseout","option","placeholder"),
            "method" => "render_open_close"
        ),
        "option" => array(
            "type" => array("checkbox","radio"),
            "required" => array("type","option"),
            "prohibited" => array("autofocus","disabled","onchange","onclick","onfocus","onkeyup","onkeydown","onmouseover","onmouseout","placeholder","readonly","tabindex"),
            "prohibited_child" => array("placeholder","label","option"),
            "method" => "render_option"
        ),
        "select" => array(
            "type" => array("select"),
            "required" => array("type","name","option"),
            "prohibited" => array("placeholder"),
            "prohibited_child" => array("placeholder","label","option","class","name"),
            "method" => "render_select"
        ),
        "editor" => array(
            "type" => array("editor"),
            "required" => array("type","name","id"),
            "prohibited" => array("option","placeholder"),
            "method" => "render_editor"
        )
    );

    public function __construct($user_settings = "")
    {
        $this->init($user_settings);        
    }
    
    /*
     * Load form configuration settings post initialization
     *
     * @param var $user_settings User defined settings, can be a string or array
     */
    public function init($user_settings)
    {
        if (!is_array($user_settings)){
            //user is allowed to send a string if they just want to set the label
            $this->attribute("label", $user_settings);
        } else {
            //add array of user settings
            $this->attributes($user_settings);
        }
        $this->render_method();
        return $this;
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
     * Determines the render method for a form item
     */
    private function render_method()
    {
        foreach($this->render_method_data as $render_method => $render_method_array){
            if (in_array($this->type, $render_method_array["type"])){
                $this->render_method = $render_method;
            }
        }
    }

    /*
     * Determines the validation data for the item
     */
    public function validate($item_count)
    {
        $this->validate_requirements($item_count);
        $this->validate_attributes($item_count);
    }

    /*
     * Determines the required fields for the item
     */
    private function validate_requirements($item_count)
    {
        //Make sure required item data has at least an empty value to trigger validation
        foreach ($this->render_method_data[$this->render_method]["required"] as $required_item_attribute){
            if (is_array($this->$required_item_attribute) AND count($this->$required_item_attribute) == 0) {
                //checks for array based attributes
                $this->error[] = "Required item attribute [". $required_item_attribute ."] with type [ array ] on item [" . $item_count . "] has 0 children.";
            } else if (empty($this->$required_item_attribute) || $this->$required_item_attribute == false){
                //checks for string based
                $this->error[] = "Required item attribute [". $required_item_attribute ."] on item [" . $item_count . "] left blank.";
            }
        }

        //Make sure no banned item attributes are present
        foreach ($this->render_method_data[$this->render_method]["prohibited"] as $prohibited_item_attribute){
            if (isset($this->$prohibited_item_attribute)){
                if (is_array($this->$prohibited_item_attribute) AND count($this->$prohibited_item_attribute)) {
                    $this->$prohibited_item_attribute = array();
                    $this->warning[] = "Prohibited item attribute [" . $prohibited_item_attribute . "] with type [" . $this->type . "] on item [" . $item_count . "] : Attribute removed.";
                } else if ($this->$prohibited_item_attribute != true AND !empty($this->$prohibited_item_attribute)) {
                    $this->$prohibited_item_attribute = false;
                    $this->warning[] = "Prohibited item attribute [" . $prohibited_item_attribute . "] with type [" . $this->type . "] on item [" . $item_count . "] : Attribute removed.";
                }
            }
        }
    }

    private function validate_attributes($item_count)
    {
        $form_item_attributes = get_object_vars($this);
        foreach ($form_item_attributes as $item_attribute => $val){
            switch ($item_attribute) :
                case 'autofocus':
                case 'disabled':
                case 'readonly':
                    if ($val !== true && $val !== false){
                        if (!isset($val) || $val == "" || $val == null){
                            $this->$item_attribute = true;
                            $this->warning[] = "Item attribute [". $item_attribute ."] on item [" . $item_count . "] is a boolean value and should be true or false.  System assigned : true.";
                        } else {
                            $this->$item_attribute = false;
                            $this->warning[] = "Item attribute [". $item_attribute ."] on item [" . $item_count . "] is a boolean value and should be true or false.  System assigned : false.";
                        }
                    }
                    if ($item_attribute == "autofocus" && $val == true){ $this->autofocus_count++; }
                    break;
                case 'tabindex':
                    if (!empty($val) AND $val != false AND $val != 0){
                        if (!is_int($val)){
                            $this->$item_attribute = 0;
                            $this->warning[] = "Item attribute [". $item_attribute ."] on item [" . $item_count . "] must be an integer.  System assigned : ". $val . ". System removed";
                        } else if ($val <= 0){
                            $this->$item_attribute = 0;
                            $this->warning[] = "Item attribute [". $item_attribute ."] on item [" . $item_count . "] must be an integer greater than 0.  System removed.";
                        } else if ($item_attribute == "tabindex") {
                            if (in_array($val, $this->tabindex_list)) {
                                $this->warning[] = "Item attribute [tabindex] on item [" . $item_count . "] was duplicated on a previous item.";
                            } else {
                                $this->tabindex_list[] = $val;
                            }
                        }
                    }
                    break;
                case 'render_method':
                case 'type':
                    if (empty($val) || $val == false || $val == null){
                        $this->error[] = "Item attribute [". $item_attribute ."] on item [" . $item_count . "] requires a value.";
                    }
                    break;
                case 'name':
                    if (in_array($val, $this->name_list)){
                        $this->error[] = "Item attribute [". $item_attribute ."] on item [" . $item_count . "] has already been used.";
                    } else if (!empty($val) AND substr($val, -2) != "[]"){
                        $this->name_list[] = $val;
                    }
                    break;
                case 'id':
                    if (preg_match('/\s/',$val)){
                        $this->error[] = "Item [" . $item_count . "] id has spacing in it.";
                    } else if (in_array($val, $this->id_list)){
                        $this->error[] = "Item [" . $item_count . "] id has already been used.";
                    } else if (!empty($val)){
                        $this->id_list[] = $val;
                    }
                    break;
                case 'validation':
                    if (count($this->validation)){
                        foreach ($this->validation as $temp_validation => $temp_validation_value){
                            if (!in_array($temp_validation, $this->validations)){
                                $this->$item_attribute = "";
                                $this->warning[] = "Validation [".$temp_validation."] assigned to item [" . $item_count . "] does not exist.";
                            }
                        }
                    }
                    break;
                case 'class':
                    if (!is_array($val)){
                        $this->$item_attribute = array();
                        $this->data["warning"][] = "Item [" . $item_count . "] was provided with class attribute but it was not an array.";
                    }
                    break;
                case 'option':
                    if ($this->render_method == "option" || $this->render_method == "select"){
                        if (count($val)){
                            $option_title = "Item attribute [". $item_attribute ."] on item [" . $item_count . "]";
                            $this->$item_attribute = $this->validate_option($val, $option_title);
                        } else if (is_array($val)) {
                            $this->error[] = "Item [" . $item_count . "] is required to have option values assigned.";
                        } else if (!is_array($val)) {
                            $this->error[] = "Item [" . $item_count . "] was provided with option attribute but it was not an array.";
                        }
                    } else if (count($val)) {
                        $this->$item_attribute = array();
                        $this->warning[] = "Item [" . $item_count . "] was provided with option array but it does not use options.";
                    }
                    break;
            endswitch;
        }
    }

    private function validate_option($options = array(), $item_name)
    {
        if (is_array($options) AND count($options)){
            $option_count = 1;
            foreach ($options as &$option_data){
                foreach ($option_data as $option_data_key => $option_data_value){
                    switch ($option_data_key) :
                        case 'name':
                            if (empty($option_data_value) || $option_data_value == null){
                                $this->error[] = $item_name. " on option [" . $option_count . "] requires a value.";
                            }
                            break;
                        case 'id':
                            if (preg_match('/\s/',$option_data_value)){
                                $this->error[] = $item_name. " on option [" . $option_count . "] has spacing in it.";
                            } else if (in_array($option_data_value, $this->id_tracker)){
                                $this->error[] = $item_name. " on option [" . $option_count . "] id has already been used.";
                            } else {
                                if ($this->type == "select"){
                                    unset($option_data[$option_data_key]);
                                } else {
                                    $this->id_tracker[] = $option_data_value;
                                }
                            }
                            break;
                        case 'tabindex':
                            if ($this->type == "select"){
                                //select
                                unset($option_data[$option_data_key]);
                            } else {
                                //checkbox + radio
                                if (!empty($option_data_value) AND $option_data_value != false AND $option_data_value != 0) {
                                    if (!is_int($option_data_value)) {
                                        $option_data[$option_data_key] = 0;
                                        $this->warning[] = $item_name . " on option [" . $option_count . "] tabindex must be an integer.  System removed.";
                                    } else if ($option_data_value <= 0) {
                                        $option_data[$option_data_key] = 0;
                                        $this->warning[] = $item_name . " on option [" . $option_count . "] tabindex  must be an integer greater than 0.  System removed.";
                                    }
                                }
                                if (in_array($option_data_value, $this->tabindex_list)) {
                                    $this->warning[] = $item_name . " on option [" . $option_count . "] tabindex was duplicated on a previous item.";
                                } else {
                                    $this->tabindex_list[] = $option_data_value;
                                }
                            }
                            break;
                        case 'class':
                            if ($this->type == "select"){
                                //select
                                unset($option_data[$option_data_key]);
                                $this->warning[] = $item_name. " on option [" . $option_count . "] was provided with class attribute : system removed.";
                            } else {
                                //checkbox + radio
                                if (!is_array($option_data_value)){
                                    $option_data["class"] = array();
                                    $this->warning[] = $item_name. " on option [" . $option_count . "] was provided with class attribute but it was not an array.";
                                }
                            }
                            break;
                        case 'autofocus':
                        case 'disabled':
                        case 'readonly':
                            if ($this->type == "select"){
                                //select
                                unset($option_data[$option_data_key]);
                            } else {
                                //checkbox + radio
                                if (isset($option_data[$option_data_value]) && $option_data[$option_data_value] !== true && $option_data[$option_data_value] !== false){
                                    if (isset($option_data[$option_data_value])){
                                        $option_data[$option_data_value] = true;
                                        $this->warning[] = $item_name. " on option [" . $option_count . "] is a boolean value and should be true or false.  System assigned : true.";
                                    } else {
                                        $option_data[$option_data_value] = false;
                                        $this->warning[] = $item_name. " on option [" . $option_count . "] is a boolean value and should be true or false.  System assigned : false.";
                                    }
                                }
                                if ($option_data_key == "autofocus" && $option_data_value == true){ $this->autofocus_count++; }
                            }
                            break;
                        case 'label':
                        case 'onkeydown':
                        case 'onchange':
                        case 'onfocus':
                        case 'onmouseover':
                        case 'onmouseout':
                        case 'onclick':
                            if ($this->type == "select"){
                                unset($option_data[$option_data_key]);
                            }
                            break;
                    endswitch;
                }
                $option_count++;
            }
        }
        return $options;
    }

    public function render($markup = "html", $prefix = "")
    {
        $html = "";
        $this->markup = $markup;
        $this->prefix = $prefix;

        //Wrapper Item Div
        $html .= '<div';
        if ($this->auto_class AND !empty($this->id)) {
            $html .= ' id="' . $this->id . '_container"';
        }
        if (count($this->class)){
            $html .= ' class="'.implode("_container ", $this->class).'_container"';
        }
        $html .= '>';
        //Label
        if (!empty($this->label)) {
            $html .= '<label';
            if (!empty($this->id)) { $html .= ' for="' . $this->id . '"'; }
            if ($this->auto_class) { $html .= ' class="' . $this->prefix . 'label"'; }
            $html .= '>' . $this->label . $this->divider;
            if (isset($this->validation["required"]) AND $this->validation["required"] == true){ $html .= '<div class="' . $this->prefix . 'required">'.$this->required_symbol.'</div>'; }
            $html .= '</label>';
        }

        //Wrapper Input Item Div
        $html .= '<div';
        if ($this->auto_class AND !empty($this->id)) {
            $html .= ' id="' . $this->prefix . 'item_input_container_' . $this->id . '"';
        }
        if (count($this->class)){
            $html .= ' class="' . implode("_input_container ", $this->class).'_input_container"';
        }
        $html .= '>';

        $render_type = $this->render_method_data[$this->render_method]["method"];
        $html .= $this->$render_type();
        
        $html .= '</div>'; // wrapper input item div

        if ($this->render_method != "output") {
            if (count($this->button)) {
                $html .= '<div class="'.$this->prefix.'button">';
                foreach ($this->button as $button){
                    if (isset($button["title"])){
                        $html .= '<button type="button"';
                        if (isset($button["id"])){
                            $html .= ' id="'.$button["id"].'"';
                        }
                        if (isset($button["onclick"])){
                            $html .= ' onclick="'.$button["onclick"].'"';
                        }
                        $html .=">";
                        $html .= $button["title"];
                        $html .= '</button>';
                    }
                }                
                $html .= '</div>';
            }
            if (!empty($this->content)) {
                $html .= '<div class="'.$this->prefix.'content"';
                if (!empty($this->id)) { $html .= ' id="' . $this->id . '_content"'; }
                $html .= '>' . $this->content . '</div>';
            }
            if (!empty($this->help)) {
                $html .= '<div class="'.$this->prefix.'help"';
                if (!empty($this->id)) { $html .= ' id="' . $this->id . '_help"'; }
                $html .= '>' . $this->help . '</div>';
            }
        }

        $html .= '<div class="' . $this->prefix . 'clear"></div></div>';
        return $html;
    }

    public function render_attributes($option = array(), $mode = "standard")
    {
        if ($mode != "standard") {
            $prohibited = $this->render_method_data[$mode]["prohibited_child"];
        } else {
            $prohibited = $this->render_method_data[$this->render_method]["prohibited"];
        }
        $html = "";
        foreach ($this->render_attributes as $attribute => $render_type){
            if (in_array($attribute, $prohibited) == false) {
                $attribute_value = "";
                if ($mode != "standard"){
                    if (isset($option[$attribute])) {
                        $attribute_value = $option[$attribute];
                    }
                } else if (isset($this->$attribute)){
                    $attribute_value = $this->$attribute;
                }
                if (is_array($attribute_value)){
                    if (isset($attribute_value[0])){
                        $attribute_value = $attribute_value[0];
                    }
                }
                if ($this->type) {
                    if ($render_type == "value" AND !empty($attribute_value) AND $attribute_value != false) {
                        $html .= ' ' . $attribute . '="' . $attribute_value . '"';
                    } else if ($render_type == "boolean" AND !empty($attribute_value) AND $attribute_value != false){
                        $html .= ' ' . $attribute . '="' . $attribute . '"';
                    } else if ($render_type == "value_empty" AND isset($attribute_value)){
                        $html .= ' ' . $attribute . '="' . $attribute_value . '"';
                    }
                }
            }
        }
        if (count($this->class) AND $mode != "select"){
            $html .= ' class="'.implode(" ", $this->class).'"';
        }
        return $html;
    }

    public function render_output()
    {
        $html = '<'.$this->type.$this->render_attributes().'>';
        $html .= $this->content;
        $html .= '</'.$this->type.'>';
        return $html;
    }

    public function render_input()
    {
        $html = '<input type="'.$this->type.'"' . $this->render_attributes();
        if ($this->markup == "xhtml"){ $html .= '/>'; }
        else { $html .= '>'; }
        return $html;
    }

    public function render_open_close()
    {
        $html = '<'.$this->type . $this->render_attributes() . '>';
        if (!empty($this->value) AND $this->value != false){ $html .= $this->value; }
        $html .= '</'.$this->type.'>';
        return $html;
    }

    public function render_option()
    {
        $html = "";
        foreach ($this->option as $option) {
            $option["type"] = $this->type;
            $html .= '<div class="'.$this->prefix.'option_label">'.$option["label"].'</div>';
            $html .= '<input' . $this->render_attributes($option, "option");
            if (isset($this->value)){
                if (is_array($this->value) AND in_array($option["value"], $this->value)){
                    $html .= ' checked="checked"';
                } else if ($this->value == $option["value"]){
                    $html .= ' checked="checked"';
                }
            }
            if ($this->markup == "xhtml") {
                $html .= '/>&nbsp;';
            } else {
                $html .= '>&nbsp;';
            }
        }
        return $html;
    }

    public function render_select()
    {
        $html = '<select';
        $html .= $this->render_attributes();
        $html .= '>';
        $option_group = "";
        foreach ($this->option as $option) {
            if (isset($option["group"]) AND !empty($option["group"])){
                if ($option_group != $option["group"]){
                    if (!empty($option_group)){ $html .= '</optgroup>'; }
                    $html .= '<optgroup label="'.$option["group"].'">';    
                    $option_group = $option["group"];
                }
            } else if (!empty($option_group)){
                $html .= '</optgroup>';
                $option_group = "";
            }
            $html .= '<option';
            $html .= $this->render_attributes($option, "select");
            if (!empty($this->value) AND $this->value == $option["value"]){
                $html .= ' selected="selected"';
            }
            $html .= '>';
            $html .= $option["name"];
            $html .= '</option>';
        }
        if (!empty($option_group)){ $html .= '</optgroup>'; }
        $html .= "</select>";
        return $html;
    }

    public function render_editor()
    {
        $editor = '<div class="clear '.$this->prefix.'clear"></div>';
        $editor .= '<textarea id="'.$this->id.'"';
        if (count($this->class)){
            $editor .= ' class="'.implode(" ", $this->class).'"';
        }
        if (isset($this->tabindex)){ $editor .= ' tabindex="'.$this->tabindex.'"'; }
        if (isset($this->autofocus)){
            if ($this->markup == "xhtml"){
                $editor .= ' autofocus="autofocus"';
            } else {
                $editor .= ' autofocus';
            }
        }
        $editor .= ' name="'.$this->name.'">';
        if (isset($this->value) AND $this->value != ""){
            $this->value=stripslashes($this->value);
            $this->value=str_replace("&","&amp;",$this->value);
            $this->value=str_replace("<","&lt;",$this->value);
            $this->value=str_replace(">","&gt;",$this->value);
            $editor .= $this->value;
        }
        $editor .= '</textarea>';
        return $editor;
    }
}

