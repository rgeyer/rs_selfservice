<table class="ui-widget" cellpadding="2em">
  <thead>
   <tr class="ui-widget-header ui-corner-top">
     <td width="25%">Id</td>
     <td width="15%">Name</td>
     <td width="15%">Owner</td>
     <td width="10%">Create Date</td>
     <td width="25%">Provisioned Objects</td>
     <td width="10%">Actions</td>
   </tr>  
  </thead>
  <tbody class="ui-widget-content">
{foreach $provisioned_products as $product}
    <tr>
      <td>{$product->id}</td>
      <td>{$product->product->name}</td>
      <td>{$product->owner->getName()}</td>
      <td>{$product->createdate|date_format}</td>
      <td>{count($product->provisioned_objects)}
      <td>{foreach $actions as $key=>$action}<a href="{$this->url($action.url_parts.controller, ['action' => $action.url_parts.action, 'id' => $product->id])}"{if $action.is_ajax} class="icon ajaxaction" data-nexthop="self"{/if}><img src="{$this->basePath()}/{$action.img_path}"/></a>{/foreach}</td>
    </tr>
{/foreach}
  </tbody>
</table>