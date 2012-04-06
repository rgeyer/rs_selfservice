<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
</head>
<body>
<p>Y Hello thar.. Seems you've logged in for the first time!</p>
<form action="{$form_action}" method="post">
  <fieldset>
    <label>Name:</label>
    <input type="text" name="name" />
    <label>Email:</label>
    <input type="text" name="email" />
    <input type="submit" value="Submit" />
  </fieldset>
</form>
<p>{$user}</p>
</body>
</html>