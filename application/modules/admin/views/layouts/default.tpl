<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>RightScale IT Vending Machine Admin</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  <link type="text/css" href="/css/default.css" rel="stylesheet" />
  <link type="text/css" href="/jquery/css/start/jquery-ui-1.8.16.custom.css" rel="stylesheet" /> 
  <script type="text/javascript" src="/jquery/js/jquery-1.6.2.min.js"></script>
  <script type="text/javascript" src="/jquery/js/jquery-ui-1.8.16.custom.min.js"></script>
  <script type="text/javascript" src="/js/admin.js"></script>
  {block name=style}{/block}
  
  {block name=script}{/block}
  
</head>

<body>
<div>
  <h1>Some cool actions</h1>
  <ul>
    <li><strong>Self-Serve Products</strong>
     <ul>
       <li><a href="/admin/product">list</a></li>
       <li><a href="/admin/product/add">add</a></li>
     </ul>
    </li>
    <li><strong>Provisioned Products</strong>
      <ul>
        <li><a href="/admin/provisionedproduct">list</a></li>
      </ul>
    </li>
  </ul>  
</div>
<div id="messages">
{foreach $messages as $message}
<p class="{$message.class}">{$message.text}</p>
{/foreach}
</div>
{block name=body}{/block}
</body>
</html>