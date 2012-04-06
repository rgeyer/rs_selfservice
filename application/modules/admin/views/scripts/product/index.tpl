{extends file="$layout_dir/default.tpl"}

{block name=body}
<table>
  <thead>
   <tr>
     <td>Id:</td>
     <td>Name:</td>
     <td>Icon:</td>
     <td>Actions: </td>
   </tr>  
  </thead>
  <tbody>
{foreach $products as $product}
    <tr>
      <td>{$product->id}</td>
      <td>{$product->name}</td>
      <td>{$product->img_url}</td>
      <td>{foreach $actions as $key=>$action}<a href="{$action.uri_prefix}?id={$product->id}"><img src="{$action.img_path}"/></a>{/foreach}</td>
    </tr>
{/foreach}
  </tbody>
</table>
{/block}