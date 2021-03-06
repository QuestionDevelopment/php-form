#php-form

A simple and powerful form building system.  


## Initializing the form
The first step in creating a form is to initialize the class and assign the form variables.  For more details and to view all possible variable settings see [Form Plugin Data](https://github.com/QuestionDevelopment/php-form/wiki/Form-Variables)

```
$form = new form(array("action" => "/admin/user/edit.php", "title" => "User", "submit" => "Save"));
```

```
$form = new form();
$form->attribute("action" => "/account/login/");
$form->attribute("title" => "Login");

$settings = array();
$settings["submit"] = "Login";
$settings["reset"] = "Reset";
$form->attributes($settings);
```

## Adding Form Items
Once the form has been initialized items can be added to it.  The items will be displayed in the order they were submitted.  For more details and to view all possible variable settings see [Form Plugin Item Data](https://github.com/QuestionDevelopment/php-form/wiki/Form-Item-Variables)

```
$items = array();
$items[] = new form_item(array("type" => "text", "label" => "Login", "validation" => array("required" => true)));
$items[] = new form_item(array("type" => "text", "label" => "Password", "validation" => array("required" => true)));
$form->items($items);
```

```
$item = new form_item(array("type" => "text", "label" => "Login", "validation" => array("required" => true)));
$form->item($item);
```

```
$item = new form_item();
$item->attribute("type" => "text");
$item->attributes(array("label" => "Login", "validation" => array("required" => true)));
$form->item($item);
```

## Rendering Form
Once the form and all of it's items are configurate properly you can output the form on the page.
```
$form->render();
```

## Credit
Thanks to Josh Cunningham <josh@joshcanhelp.com> author of php-form-builder for inspiring this project.

## Other
[Please see wiki for more information](https://github.com/QuestionDevelopment/php-form/wiki/)
