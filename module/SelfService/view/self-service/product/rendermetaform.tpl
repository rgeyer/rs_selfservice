{function name=renderinput}
      <fieldset{if !$meta_input->display} style="display: none;"{/if}>
        <label for="{$meta_input->input_name}">{$meta_input->display_name}:</label>
        {if preg_match('/CloudProductInput$/', get_class($meta_input))}
        <select name="{$meta_input->input_name}" id="{$meta_input->id}" class="cloud_meta">
          {foreach $clouds as $cloud_name => $cloud_id}
          <option value="{$cloud_id}"{if "/api/clouds/$cloud_id" == $meta_input->default_value} selected{/if}>{$cloud_name}</option>
          {/foreach}
        </select>
        {elseif preg_match('/SelectProductInput$/', get_class($meta_input))}
        <select name="{$meta_input->input_name}[]" id="{$meta_input->input_name}" class="select_meta"{if $meta_input->multiselect} multiple{/if}>
{foreach $meta_input->options as $option}
          <option{if in_array($option, $meta_input->default_value)} selected{/if}>{$option}</option>
{/foreach}
        </select>
        {elseif preg_match('/InstanceTypeProductInput$/', get_class($meta_input))}
        <select name="{$meta_input->input_name}" id="{$meta_input->input_name}" class="instance_type_meta" data-defaults='{json_encode($meta_input->default_value)}'>
        </select>
        <script>
          $(function() {
            instance_type_selects = $('#{$meta_input->cloud_product_input.id}').data('instance_type_selects');
            if(!instance_type_selects) {
              instance_type_selects = [];
            }
            instance_type_selects.push($('#{$meta_input->input_name}'));
            $('#{$meta_input->cloud_product_input.id}').data('instance_type_selects', instance_type_selects);
          });
        </script>
        {elseif preg_match('/DatacenterProductInput$/', get_class($meta_input)) }
        <select name="{$meta_input->input_name}[]" id="{$meta_input->input_name}" class="datacenter_meta" data-defaults='{json_encode($meta_input->default_value)}'{if $meta_input->multiselect} multiple{/if}>
        </select>
        <script>
          $(function() {
            datacenter_selects = $('#{$meta_input->cloud_product_input.id}').data('datacenter_selects');
            if(!datacenter_selects) {
              datacenter_selects = [];
            }
            datacenter_selects.push($('#{$meta_input->input_name}'));
            $('#{$meta_input->cloud_product_input.id}').data('datacenter_selects', datacenter_selects);
          });
        </script>
        {else}
        <input type="text" name="{$meta_input->input_name}" id="{$meta_input->input_name}" value="{$meta_input->default_value}"/>
        {/if}
        <div style="font-size: -1; color: grey;"><img src="{$this->basePath()}/images/info.png" /> {$meta_input->description}</div>
      </fieldset>
{/function}
<form action="{$this->url('product', ['action' => 'provision', 'id' => $id])}" method="POST" id="product_{$id}_form" class="productform">
  <div id="tabs" width="80%">
    <ul>
      <li><a href="#basic">Basic</a></li>
      <li><a href="#advanced">Advanced</a></li>
    </ul>
    <div id="basic">
{foreach $meta_inputs as $meta_input}
{if $meta_input->advanced}{continue}{/if}
{call renderinput meta_input=$meta_input}
{/foreach}
    </div>
    <div id="advanced">
{foreach $meta_inputs as $meta_input}
{if !$meta_input->advanced}{continue}{/if}
{call renderinput meta_input=$meta_input}
{/foreach}
    </div>
  </div>
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
      if(data[field.name]) {
        if($.isArray(data[field.name])) {
          data[field.name] = data[field.name].concat(field.value);
        } else {
          oldval = data[field.name];
          data[field.name] = [oldval].concat(field.value);
        }
      } else {
        data[field.name] = field.value;
      }
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
    cloud_id = $(this).val();

    instance_type_selects = $(this).data('instance_type_selects');
    instance_type_select_ids = [];
    $(instance_type_selects).each(function(index, element) {
      $(element).empty();
      instance_type_select_ids.push($(element).attr('id'));
    });

    datacenter_selects = $(this).data('datacenter_selects');
    datacenter_select_ids = [];
    $(datacenter_selects).each(function(index, element) {
      $(element).empty();
      datacenter_select_ids.push($(element).attr('id'));
    });

    $.post('{$this->url('metainput', ['action' => 'instancetypes'])}/'+cloud_id, {literal}{'instance_type_ids':instance_type_select_ids}{/literal}, function(data, status, jqXHR) {
      $(data.instance_type_ids).each(function(index, instance_type_id) {
        defaults = $.parseJSON($("#"+instance_type_id).attr('data-defaults'));
        default_instance_type = "";
        $(defaults).each(function(idx, def) {
          cloud_href = "/api/clouds/"+cloud_id;
          if(def.cloud_href == cloud_href) {
            default_instance_type = def.resource_hrefs.pop();
          }
        });
        $(data.instance_types).each(function(idx, instance_type) {
          extra_attrs = "";
          if(default_instance_type == instance_type.href) {
            extra_attrs = " selected";
          }
          $("#"+instance_type_id).append("<option value='"+instance_type.href+"'"+extra_attrs+">"+instance_type.name+"</option>");
        });
      });
      $("#product_{$id}_submit").removeAttr('disabled');
    });

    $.post('{$this->url('metainput', ['action' => 'datacenters'])}/'+cloud_id, {literal}{'datacenter_ids':datacenter_select_ids}{/literal}, function(data, status, jqXHR) {
      $(data.datacenter_ids).each(function(index, datacenter_id) {
        defaults = $.parseJSON($("#"+datacenter_id).attr('data-defaults'));
        default_datacenters = [];
        $(defaults).each(function(idx, def) {
          cloud_href = "/api/clouds/"+cloud_id;
          if(def.cloud_href == cloud_href) {
            default_datacenters = default_datacenters.concat(def.resource_hrefs);
          }
        });
        $(data.datacenters).each(function(idx, datacenter) {
          extra_attrs = "";
          if($.inArray(datacenter.href, default_datacenters) > -1) {
            extra_attrs = " selected";
          }
          $("#"+datacenter_id).append("<option value='"+datacenter.href+"'"+extra_attrs+">"+datacenter.name+"</option>");
        });
      });
      $("#product_{$id}_submit").removeAttr('disabled');
    });
  });

  $('.cloud_meta').trigger('change');

  $('#tabs').tabs();
});
</script>