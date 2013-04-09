<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
  <title>RightScale IT Vending Machine</title>
  <meta http-equiv="content-type" content="text/html;charset=utf-8" />
  
  {block name=style}{/block}
  
  {block name=script}{/block}
<link type="text/css" href="{$this->basePath()}/jquery/css/start/jquery-ui-1.8.16.custom.css" rel="stylesheet" />
<link type="text/css" href="{$this->basePath()}/css/style.css" rel="stylesheet" />
<link type="text/css" href="{$this->basePath()}/css/default.css" rel="stylesheet" />
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js"></script>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.16/jquery-ui.min.js"></script>
<script type="text/javascript" src="{$this->basePath()}/js/functions.js"></script>
<script>
$(function() {
  $( "#progressbar" ).progressbar({
    value: 0
  }); 
  
  $( "#dialog-modal" ).dialog({
    height: 180,
    width: 500,
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
	    height: 600,
	    width: 575,
	    modal: true,
	    autoOpen: false
  });
  
  $('.product').click(function() {
	  id = $('input', this).val();
    $.ajax({
      url: '{$this->url("productrendermetaform")}/'+id,
      dataType: 'html',
      success: function(data, status, jqXHR) {
        $('#product-dialog').html(data);
		    $('#product-dialog').dialog('open');
      },
      error: function(jqXHR, status, error) {
        content = $('<div>').append(jqXHR.responseText).find("#content");
        open_message_dialog(
          $(window).height() - 100,
          $(window).width() - 100,
          "Error",
          content
        );
      }
    });
  });
});
</script>
<style>
#idxcontent {
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
  width: 870px;
  margin: 0 59px;
}

#products .product
{
	display: inline-block;
  margin: auto 2em;
  height: 196px;
  width: 142px;
  vertical-align: top;
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

.productform label {
  width: auto;
}

.productform fieldset {
  width: 85%;
}

.productform input {
  width: auto;
  float: none;
}

</style>
</head>
<body bgcolor="#012b5d" leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">
<div id="idxcontent">
  <div id="products">
    <div id="leftlink"><img src="{$this->basePath()}images/left.png" /></div>
    <div id="rightlink"><img src="{$this->basePath()}images/right.png" /></div>
    {foreach $products as $product}    
    <div class="product" id="product_{$product->id}">
      <img src="{$this->basePath()}{$product->img_url}"/>
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
<div id="product-dialog" title="Environment Options"></div>
<div id="message-dialog" title="Message"></div>
</body>
</html>