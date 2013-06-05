<form action="{$this->url('product', ['action' => 'provision', 'id' => $id])}" method="POST" id="product_{$id}_form" class="productform">
  {foreach $meta_inputs as $meta_input}  
  <fieldset>
    <label for="{$meta_input->input_name}">{$meta_input->display_name}:</label>
    {if preg_match('/CloudProductInput$/', get_class($meta_input))}
    <select name="{$meta_input->input_name}" id="{$meta_input->input_name}" class="cloud_meta">
      {foreach $clouds as $cloud_id => $cloud_name}
      <option value="{$cloud_name}">{$cloud_id}</option>
      {/foreach}
    </select>
    {elseif preg_match('/InstanceTypeProductInput$/', get_class($meta_input))}
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
    {elseif preg_match('/DatacenterProductInput$/', get_class($meta_input)) }
    {else}
    <input type="text" name="{$meta_input->input_name}" id="{$meta_input->input_name}" value="{$meta_input->getVal()}"/>
    {/if}
    <div style="font-size: -1; color: grey;"><img src="{$this->basePath()}/images/info.png" /> {$meta_input->description}</div>
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
    $.ajax({literal}{{/literal}
      url: action,
      data: data,
      dataType: 'json',
      type: 'POST',
      success: function(data, status, jqXHR) {
        $("#dialog-modal").dialog('close');
        if (data.result == 'error') {
          open_message_dialog(
            $(window).height() - 100,
            $(window).width() - 100,
            "Application Error",
            "<p>"+data.error+"</p>"
          );
        } else {
          content = response_messages_to_content(data.messages);
          open_message_dialog(
            350,
            500,
            "Provisioned Product",
            content
          );
        }
      },
      error: function(jqXHR, status, error) {
        content = $('<div>').append(jqXHR.responseText).find("#content");
        $('#dialog-modal').dialog('close');
        open_message_dialog(
          $(window).height() - 100,
          $(window).width() - 100,
          "Transport Error",
          content
        );
      }
    {literal}}{/literal});
		evt.preventDefault();
	});

  $('.cloud_meta').change(function(evt) {
    $("#product_{$id}_submit").attr('disabled', 'disabled');
    instance_type_selects = $(this).data('instance_type_selects');
    instance_type_select_ids = [];
    cloud_id = $(this).val();
    $(instance_type_selects).each(function(index, element) {
      $(element).empty();
      instance_type_select_ids.push($(element).attr('id'));
    });
    $.post('{$this->url('metainput', ['action' => 'instancetypes'])}/'+cloud_id, {literal}{'instance_type_ids':instance_type_select_ids}{/literal}, function(data, status, jqXHR) {
      $(data.instance_type_ids).each(function(index, instance_type_id) {
        $(data.instance_types).each(function(idx, instance_type) {
          $("#"+instance_type_id).append("<option value='"+instance_type.href+"'>"+instance_type.name+"</option>");
        });
      });
      $("#product_{$id}_submit").removeAttr('disabled');
    });
  });

  $('.cloud_meta').trigger('change');
});
</script>