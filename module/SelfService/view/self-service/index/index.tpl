<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>RightScale IT Vending Machine</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  
  {block name=style}{/block}
  
  {block name=script}{/block}
<link type="text/css" href="jquery/css/start/jquery-ui-1.8.16.custom.css" rel="stylesheet" /> 
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
<script>
// 988 x 568
// 2.8 x 1.972
function timeout_func()
{
  var percent = $( "#progressbar" ).progressbar('value');
  percent = (percent + 10) % 100;
  $( "#progressbar" ).progressbar('value', percent);
  
  if ($( "#dialog-modal" ).dialog('isOpen')) {
    setTimeout(timeout_func,1000);
  }
}

$(function() {
  $( "#progressbar" ).progressbar({
    value: 0
  }); 
  
  $( "#dialog-modal" ).dialog({
    height: 180,
    width: 350,
    modal: true,
    autoOpen: false
  });
  
  $("#wp_link").click(function() {
    $( "#dialog-modal" ).dialog('open');
    
    timeout_func();
  });
  
  // Left and Right
  $('#leftlink').click(function () {
	  $('.product:visible:first').animate({
		  'margin-left': -190		  
	  }, 300, function() { $('.product:visible:first').hide(); showHideControls(); });
  });
  
  $('#rightlink').click(function () {
	  element = $('.product:hidden:last');
	  element.show();
    element.animate({
      'margin-left': '2em'
    }, 300, function() { showHideControls(); });   
  });
  
  function showHideControls() {
	  hiddenLeft = $('.product:hidden').length;
	  if(hiddenLeft > 0) {
		  $('#rightlink').show();
	  } else {
		  $('#rightlink').hide();
	  }
	  
	  totalCount = $('.product:visible').length;
	  if(totalCount > 4) {
		  $('#leftlink').show();
	  } else {
		  $('#leftlink').hide();
	  }
  }
  
  showHideControls();
  
  $('#product-dialog').dialog({
	    height: 180,
	    width: 500,
	    modal: true,
	    autoOpen: false
  });
  
  $('.product').click(function() {
	  id = $('input', this).val();
	  $.get('{$this->url("productrendermetaform")}/' + id, function(data) {
		  $('#product-dialog').html(data);
		  $('#product-dialog').dialog('open');
	  });	  
  });
  
  $('#finished-dialog').dialog({
      height: 180,
      width: 500,
      modal: true,
      autoOpen: false
  });
});
</script>
<style>
#content {
  width: 988px;
  height: 568px;
  background: #012b5d url('images/it-services-v1.png') no-repeat right top;
  padding: 11em 3em 0 3em;
  position: relative;
  text-align: center;
  margin: 0 auto;
}

#products {
	overflow: hidden;
  height: 190px;
  width: 870px;
  margin: 0 59px;
}

#products .product
{
	display: inline-block;
  margin: auto 2em;
  height: 196px;
  width: 142px;	
}

.product img {
	height: 142px;
  width: 142px;
}

.product p {
	color: white;
}

#leftlink {
	margin: 82px 0;
	height: 196px;
	position: absolute;
  left: 0;
}

#rightlink {
  margin: 82px 0;
	height: 196px;
	position: absolute;
  right: 0;
}

</style>
</head>
<body bgcolor="#012b5d" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<center>
<div id="content">
  <div id="products">
    <div id="leftlink"><img src="images/left.png" /></div>
    <div id="rightlink"><img src="images/right.png" /></div>
    {foreach $products as $product}    
    <div class="product" id="product_{$product->id}">
      <img src="{$product->img_url}"/>
      <p>{$product->name}</p>
      <input type="hidden" value="{$product->id}" />
    </div>
    {/foreach}
 </div>
</div>
<div id="dialog-modal" title="Provisioning your environment">
  <p>Please wait while your servers are provisioned!<p>
  <div id="progressbar"></div>
</div>
<div id="product-dialog" title="Environment Options">
</div>
<div id="finished-dialog" title="Product Provisioned">
</div>
</center>
</body>
</html>