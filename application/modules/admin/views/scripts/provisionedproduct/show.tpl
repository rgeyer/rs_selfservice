{extends file="$layout_dir/default.tpl"}

{block name=body}
<table>
  <thead>
   <tr>
     <td>State:</td>
     <td>Name:</td>
     <td>Create Date:</td>
     <td>Actions:</td>
   </tr>  
  </thead>
  <tbody>
{foreach $servers as $server}
    <tr>
      <td><div class="server"><img src="/images/32server.png" /><img src="/images/spacer.gif" class="state {$server->state}"/></td>
      <td>{$server->name}</td>
      <td>{$server->created_at|date_format}</td>
      <td>{$server->server_type}</td>
    </tr>
{/foreach}
  </tbody>
</table>
{/block}