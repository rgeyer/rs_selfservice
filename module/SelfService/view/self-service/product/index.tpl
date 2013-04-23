<table class="ui-widget" cellpadding="2em">
  <thead>
   <tr class="ui-widget-header ui-corner-top">
     <td>Name:</td>
     <td>Icon:</td>
     <td>Launch Servers:</td>
     <td>Actions:</td>
   </tr>
  </thead>
  <tbody class="ui-widget-content">
{foreach $products as $product}
    <tr>
      <td>{$product->name}</td>
      <td>{$product->icon_filename}</td>
      <td>{if $product->launch_servers}True{else}False{/if}</td>
      <td>{foreach $actions[$product->id] as $key=>$action}<a href="{$action.uri}"{if $action.is_ajax} class="ajaxaction"{/if}><img class="action_{$key}" src="{$this->basePath()}/{$action.img_path}"/></a>{/foreach}</td>
    </tr>
{/foreach}
  </tbody>
</table>