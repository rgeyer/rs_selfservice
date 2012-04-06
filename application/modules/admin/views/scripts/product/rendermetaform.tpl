<form action="/admin/provisionedproduct/provision?id={$id}" method="POST" id="product_{$id}_form">
  {foreach $meta_inputs as $meta_input}  
  <fieldset>
    <label for="{$meta_input->input_name}">{$meta_input->display_name}:</label>
    {if preg_match('/CloudProductMetaInput(Proxy)?$/', get_class($meta_input))}
    <select name="{$meta_input->input_name}" id="{$meta_input->input_name}">
      {foreach $clouds as $cloud_id => $cloud_name}
      <option value="{$cloud_name}">{$cloud_id}</option>
      {/foreach}
    </select>
    {/if}
    {if preg_match('/TextProductMetaInput(Proxy)?$/', get_class($meta_input))}
    <input type="text" name="{$meta_input->input_name}" id="{$meta_input->input_name}" value="{$meta_input->default_value}"/>
    {/if}
    <div style="font-size: -1; color: grey;"><img src="/images/info.png" /> {$meta_input->description}</div>
  </fieldset>
  {/foreach}
  <input type="submit" id="product_{$id}_submit"/>
</form>

<script>
$(function() {
	$('#product_{$id}_submit').click(function() {
		$('#product-dialog').dialog('close');
		$("#dialog-modal").dialog('open');
		timeout_func();
		// Get a jobber id
		$.get('/jobber/create', function(data) {
			action = $('#product_{$id}_form').attr('action');
			fulluri = action + '&' + $('#product_{$id}_form').serialize(); 
			// Collect the inputs values			
			$.get(fulluri, function(data) {
				$("#dialog-modal").dialog('close');
				if(data.result == 'error') {
					$("#finished-dialog").html("<p>" + data.error + "</p>");
				} else {
					  $("#finished-dialog").html("<a href='" + data.url + "' target='_blank'>" + data.url + "</a>");
				}
				$("#finished-dialog").dialog('open');
			});
		});
		event.preventDefault();
	});
});
</script>