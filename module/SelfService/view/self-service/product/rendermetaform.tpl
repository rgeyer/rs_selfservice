<form action="{$this->url('product')}/provision/{$id}" method="POST" id="product_{$id}_form">
  {foreach $meta_inputs as $meta_input}  
  <fieldset>
    <label for="{$meta_input->input_name}">{$meta_input->display_name}:</label>
    {if preg_match('/CloudProductMetaInput(Proxy)?$/', get_class($meta_input))}
    <select name="{$meta_input->input_name}" id="{$meta_input->input_name}" class="cloud_meta">
      {foreach $clouds as $cloud_id => $cloud_name}
      <option value="{$cloud_name}">{$cloud_id}</option>
      {/foreach}
    </select>
    {/if}
    {if preg_match('/TextProductMetaInput(Proxy)?$/', get_class($meta_input)) || preg_match('/NumberProductMetaInput(Proxy)?$/', get_class($meta_input)) }
    <input type="text" name="{$meta_input->input_name}" id="{$meta_input->input_name}" value="{$meta_input->getVal()}"/>
    {/if}
    {if preg_match('/InstanceTypeProductMetaInput(Proxy)?$/', get_class($meta_input))}
    <select name="{$meta_input->input_name}" id="{$meta_input->input_name}" class="instance_type_meta">
    </select>
    <script>
      $(function() {
        instance_type_selects = $('#{$meta_input->cloud->input_name}').data('instance_type_selects');
        if(!instance_type_selects) {
          instance_type_selects = [];
        }
        instance_type_selects.push($('#{$meta_input->input_name}'));
        $('#{$meta_input->cloud->input_name}').data('instance_type_selects', instance_type_selects);
      });
    </script>
    {/if}
    <div style="font-size: -1; color: grey;"><img src="/images/info.png" /> {$meta_input->description}</div>
  </fieldset>
  {/foreach}
  <input type="submit" id="product_{$id}_submit"/>
</form>

<script>
$(function() {
	$('#product_{$id}_submit').click(function(evt) {
		$('#product-dialog').dialog('close');
		$("#dialog-modal").dialog('open');
		timeout_func();
    action = $('#product_{$id}_form').attr('action');
    serializedForm = $('#product_{$id}_form').serializeArray();
    data = {};
    $.each(serializedForm, function(i, field) {
      data[field.name] = field.value;
    });
    // Collect the inputs values
    $.post(action, data, function(data, status, jqXHR) {
      $("#dialog-modal").dialog('close');
      if(status != 'success') {
        $("#finished-dialog").html("<p>"+status+"</p>");
      } else if (data.result == 'error') {
        $("#finished-dialog").html("<p>" + data.error + "</p>");
      } else {
        if(data.servers) {
          hostname_list = 'The following servers were launched and are currently available </br>';
          for(server in data.servers) {
            hostname_list += data.servers[server]['dns-name'] + "</br>"
          }
          $("#finished-dialog").html(hostname_list);
        } else {
          $("#finished-dialog").html("<a href='" + data.url + "'>" + data.url + "</a>");
        }
      }
      $("#finished-dialog").dialog('open');
    });
		evt.preventDefault();
	});

  $('.cloud_meta').change(function(evt) {
    instance_type_selects = $(this).data('instance_type_selects');
    instance_type_select_ids = [];
    cloud_id = $(this).val();
    $(instance_type_selects).each(function(index, element) {
      $(element).empty();
      instance_type_select_ids.push($(element).attr('id'));
    });
    $.post('{$this->url('metainput')}/instancetypes/'+cloud_id, {literal}{'instance_type_ids':instance_type_select_ids}{/literal}, function(data, status, jqXHR) {
      $(data.instance_type_ids).each(function(index, instance_type_id) {
        $(data.instance_types).each(function(idx, instance_type) {
          $("#"+instance_type_id).append("<option value='"+instance_type.href+"'>"+instance_type.name+"</option>");
        });
      });
    });
  });

  $('.cloud_meta').trigger('change');
});
</script>