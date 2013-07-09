<script>
$(function() {
  $( "#progressbar" ).progressbar({
    value: 0
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
  
  $('.product').click(function() {
	  id = $('input', this).val();
    $.ajax({
      url: '{$this->url("product", ['action' => 'provision'])}/'+id,
      dataType: 'html',
      success: function(data, status, jqXHR) {
        content = $('<div>').append(jqXHR.responseText).find("#content");
        content.attr('id','');
        content.removeClass();
        open_message_dialog(
          "Provision Product",
          content,
          {ldelim}
            value: "Submit",
            href: "{$this->url('api-product')}/"+id+"/provision",
            'data-method': "POST"
          {rdelim}
        );
      },
      error: function(jqXHR, status, error) {
        content = $('<div>').append(jqXHR.responseText).find("#content");
        open_message_dialog(
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

body {
  background-color: #012b5d !important;
}

</style>

<div id="idxcontent">
  <div id="products">
    <div id="leftlink"><img src="{$this->basePath()}/images/left.png" /></div>
    <div id="rightlink"><img src="{$this->basePath()}/images/right.png" /></div>
    {foreach $products as $product}    
    <div class="product" id="product_{$product->id}">
      <img src="{$this->basePath()}/{$product->img_url}"/>
      <p>{$product->name}</p>
      <input type="hidden" value="{$product->id}" />
    </div>
    {/foreach}
 </div>
</div>