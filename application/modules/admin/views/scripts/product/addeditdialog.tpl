<form method="post" enctype="multipart/form-data">
  <fieldset>
    <label>Name:</label>
    <input type="text" name="name" value="{$product->name}"/>
    <label>Icon:</label>
    <input type="file" name="icon" />
    <label>Security Groups:</label>
    <img src="/images/plus.png" style="display: block; clear: both;" id="add_secgrp"/>
    <ul id="secgrp_list" style="clear: both;"></ul>
    <button type="submit">Submit</button>
  </fieldset>
</form>

<div id="secgrp_dialog" title="Add Security Group">
</div>

<script type="text/javascript">
function addSecGrp(name, id) {
  liDom = $('<li>' + name + '</li>');
  delDom = $('<img src="/images/delete.png" class="delete" />');
  editDom = $('<img src="/images/pencil.png" class="edit" />');
  hiddenInputDom = $('<input type="hidden" name="secgrp[]" value="' + id + '" />');
  liDom.append(delDom);
  liDom.append(editDom);
  liDom.append(hiddenInputDom);
  liDom.data('id', id);
  delDom.data('li', liDom);
  editDom.data('li', liDom);
  $('#secgrp_list').append(liDom);
}

// Bootstrap the security group list items when used as an edit control
{foreach $product->security_groups as $security_group}
addSecGrp('{$security_group->name}', '{$security_group->id}');
{/foreach}

function handleAddEditSecGrp(posturi) {
	rules = new Array();
  $('#secgrp_rules_list li').each(function(index, element) {
    rule = $(element).data('rule');
    if(rule) {
      rules.push(rule);
    }
  });
  
  postData = { name: $('#secgrp_name').val(), description: $('#secgrp_desc').val(), rules: rules };
  $.post(posturi, postData, function(data) {
    if(data.error) {
      appendErrorMessage(data.error);
      return;
    } else {
      $('#messages.inner').replaceWith('');
    }          
    addSecGrp(data.name, data.id);
  });
  $('#secgrp_dialog *').remove();
}

// Fire up the security group form in a modal dialog
$('#add_secgrp').click(function() {
  $.get('{$secgrp_uri}', function(data) {
    $('#secgrp_dialog').append(data);
    $('#secgrp_dialog').dialog({
      modal: true,
      height: 500,
      width: 700,
      close: function(event, ui) {
    	  handleAddEditSecGrp('{$secgrp_add_uri}');
        /*rules = new Array();
        $('#secgrp_rules_list li').each(function(index, element) {
          rule = $(element).data('rule');
          if(rule) {
            rules.push(rule);
          }
        });
        
        postData = { name: $('#secgrp_name').val(), description: $('#secgrp_desc').val(), rules: rules };
        $.post('{$secgrp_add_uri}', postData, function(data) {
          if(data.error) {
        	  appendErrorMessage(data.error);
          } else {
            $('#messages.inner').replaceWith('');
          }          
          addSecGrp(data.name, data.id);
        });
        $('#secgrp_dialog *').remove();*/
      }
    });
  });    
}); 

$('#secgrp_list img.delete').live('click', function(event) {
  liDom = $(this).data('li');
  id = $(liDom).data('id');
  $.post('{$secgrp_del_uri}', { id: id }, function(data) {
    if(data.status == 'error') {
      appendErrorMessage(data.error);
      return;
    } else {
      $('#messages.inner').replaceWith('');
    }
    liDom.remove();
  });
});

$('#secgrp_list img.edit').live('click', function(event) {
  liDom = $(this).data('li');
  id = $(liDom).data('id');
  $.post('{$secgrp_uri}', { id: id }, function(data) {
    $('#secgrp_dialog').append(data);
    $('#secgrp_dialog').dialog({
      modal: true,
      height: 500,
      width: 700,
      close: function(event, ui) {
    	  handleAddEditSecGrp('{$secgrp_add_uri}');
      }
    });
  });
});
</script>