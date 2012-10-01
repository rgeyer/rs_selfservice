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
      <td>{foreach $server->actions as $key=>$action}<a href="{$action.uri_prefix}?href={urlencode($server->href)}&api={$server->api}"><img src="{$action.img_path}"/></a>{/foreach}</td>
    </tr>
{/foreach}
  </tbody>
</table>
{/block}