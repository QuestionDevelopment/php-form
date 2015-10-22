#php-form

A simple and powerful form building system.  


## Initializing the form
The first step in creating a form is to initialize the class and assign the form variables.  For more details and to view all possible variable settings see [Form Plugin Data](https://github.com/QuestionDevelopment/php-form/wiki/Form-Plugin-Data)

```
$c->form->init(array("action" => "/account/login/", "title" => "Login", "submit" => "Login"));
```

```
$c->form->init();
$c->form->attribute("action" => "/account/login/");
$c->form->attribute("title" => "Login");

$settings = array();
$settings ["submit"] = "Login";
$settings ["reset"] = "Reset";
$c->form->attributes($settings);
```

## Adding Form Items
Once the form has been initialized items can be added to it.  The items will be displayed in the order they were submitted.  For more details and to view all possible variable settings see [Form Plugin Item Data](https://github.com/QuestionDevelopment/php-form/wiki/Form-Plugin-Item-Data)

```
$items = array();
$items[] = array("type" => "text", "label" => "Email","validation" => array("required" => true,"email" => true));
$items[] = array("type" => "password", "label" => "Password","validation" => array("required" => true));
$c->form->items($items);
```

```
$c->form->item(array(("type" => "text", "label" => "Email","validation" => array("required" => true,"email" => true));
```

## Credit
Thanks to Josh Cunningham <josh@joshcanhelp.com> author of php-form-builder for inspiring this project.

## Other
[Please see wiki for more information](https://github.com/QuestionDevelopment/php-form/wiki/)
