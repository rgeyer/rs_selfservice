<style type="text/css">
#secgrp_rules input {
  float: none;
  margin: 0;
  width: 50px;
}
</style>

<div id="secgrp_dialog_messages">
</div>
<form method="post">
  <fieldset>
    <label>Name:</label>
    <input type="text" name="name" id="secgrp_name" value="{$secgrp->name}"/>
    <label>Description:</label>
    <input type="text" name="description" id="secgrp_desc" value="{$secgrp->description}"/>
    <label>Rules:</label>
    <ul id="secgrp_rules_list" style="clear: both;">      
    </ul>
    <fieldset id="secgrp_rules" style="clear: both;">
      <select id="secgrp_protocol" name="protocol">
        <option>tcp</option>
        <option>udp</option>
        <option>icmp</option>
      </select>
      Ports:
      <input name="from_port" id="secgrp_from_port" />..
      <input name="to_port" id="secgrp_to_port" />
      <select id="secgrp_type" name="type">
        <option>IPs</option>
        <option>group</option>
      </select>
      <input id="secgrp_cidr_ips_or_group" name="cidr_ips_or_group" value="0.0.0.0/0" style="width: 100px;"/>
      <button id="secgrp_add_rule">Add</button>
     </fieldset>
    <button id="secgrp_submit">Submit</button>      
  </fieldset>
</form>

<script type="text/javascript">
// If an id is specified, this is an edit action.
// If a data object is specified this is an add action.
function addRule(protocol, from_port, to_port, type, cidr_ips_or_group_value, id, data) {
	liDom = $('<li>protocol: ' + protocol + ' ports: ' + from_port + '..' + to_port + ' ' + type + ': ' + cidr_ips_or_group_value + '</li>');
	delDom = $('<img src="/images/delete.png" class="delete"/>');
  liDom.append(delDom);
  if(id) { delDom.data('id', id); }
  if(data) { liDom.data('rule', data); }
  delDom.data('li', liDom);
  $('#secgrp_rules_list').append(liDom);
}
// Bootstrap the security group rules list items when used as an edit control
{foreach $secgrp->rules as $rule}
addRule('{$rule->ingress_protocol}', '{$rule->ingress_from_port}', '{$rule->ingress_to_port}', {if $rule->ingress_group}'group'{else}'IPs'{/if}, {if $rule->ingress_group}'{$rule->ingress_group->name}'{else}'{$rule->ingress_cidr_ips}'{/if}, '{$rule->id}', null);
{/foreach}

// Set some defaults for the cidr_ips_or_group input box
$('#secgrp_cidr_ips_or_group').data('cidr_ips', '0.0.0.0/0');
$('#secgrp_cidr_ips_or_group').data('group', 'default');

// Store the previous value of cidr_ips_or_group and switch to the right
// value for the selection in secgrp_type
$('#secgrp_type').change(function() {
  ip_or_grp = $('#secgrp_type option:selected').text();
  if(ip_or_grp == 'IPs') {
    $('#secgrp_cidr_ips_or_group').data('group', $('#secgrp_cidr_ips_or_group').val());
    $('#secgrp_cidr_ips_or_group').val($('#secgrp_cidr_ips_or_group').data('cidr_ips'));
  } else {
    $('#secgrp_cidr_ips_or_group').data('cidr_ips', $('#secgrp_cidr_ips_or_group').val());
    $('#secgrp_cidr_ips_or_group').val($('#secgrp_cidr_ips_or_group').data('group'));
  }
});

// Add Security Group Rule button handler  
$('#secgrp_add_rule').click(function(event) {
  event.preventDefault();
  data = new Object();    
  data.protocol = $('#secgrp_protocol option:selected').text();   
  data.from = $('#secgrp_from_port').val();
  data.to = $('#secgrp_to_port').val();
  data.type = $('#secgrp_type option:selected').text();
  data.cidr_ips_or_group = $('#secgrp_cidr_ips_or_group').val();
  
  addRule(data.protocol, data.from, data.to, data.type, data.cidr_ips_or_group, null, data);
});

// Delete Security Group Rule button/image handler
$('#secgrp_rules_list img.delete').live('click', function(event) {
	liDom = $(this).data('li');
	if($(this).data('id')) {
		$.post('{$secgrp_rule_del_uri}', { id: $(this).data('id') }, function(data) {			
			if(data.status == 'error') {
        $('#secgrp_dialog_messages.inner').replaceWith('<p class="error">' + data.error = '</p>');
        return;
      } else {
        $('#secgrp_dialog_messages.inner').replaceWith('');
      }
      liDom.remove();
		});
	} else {  
	  liDom.remove();
	}
});

// Submit Security Group button handler
$('#secgrp_submit').click(function(event) {
  event.preventDefault();
  $("#secgrp_dialog").dialog('close');
});
</script>