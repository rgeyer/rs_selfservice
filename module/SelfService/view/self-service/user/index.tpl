<table class="ui-widget" cellpadding="2em">
  <thead>
   <tr class="ui-widget-header ui-corner-top">
     <td>Email:</td>
     <td>Authorized:</td>
     <td>Registered:</td>
     <td>API User:</td>
     <td>Actions:</td>
   </tr>
  </thead>
  <tbody class="ui-widget-content">
{foreach $users as $user}
    <tr>
      <td>{$user->email}</td>
      <td>{if $user->authorized}True{else}False{/if}</td>
      <td>{if $user->oid_url}True{else}False{/if}</td>
      <td>{if $user->api_user}True{else}False{/if}</td>
      <td>{foreach $actions[$user->id] as $key=>$action}<a href="{$action.uri}"{if $action.is_ajax} class="ajaxaction" data-method="{$action.method}" data-nexthop="self"{/if}><img class="action_{$key}" src="{$this->basePath()}/{$action.img_path}"/></a>{/foreach}</td>
    </tr>
{/foreach}
  </tbody>
</table>