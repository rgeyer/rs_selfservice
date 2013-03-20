<table class="ui-widget" cellpadding="2em">
  <thead>
   <tr class="ui-widget-header ui-corner-top">
     <td>Id:</td>
     <td>Name:</td>
     <td>Owner:</td>
     <td>Create Date:</td>
     <td>Provisioned Objects:</td>
     <td>Actions:</td>
   </tr>  
  </thead>
  <tbody class="ui-widget-content">
{foreach $provisioned_products as $product}
    <tr>
      <td>{$product->id}</td>
      <td>{$product->product->name}</td>
      <td>{$product->owner->name}</td>
      <td>{$product->createdate|date_format}</td>
      <td>{count($product->provisioned_objects)}
      <td>{foreach $actions as $key=>$action}<a href="{$action.uri_prefix}/{$product->id}"><img src="{$action.img_path}"/></a>{/foreach}</td>
    </tr>
{/foreach}
  </tbody>
</table>