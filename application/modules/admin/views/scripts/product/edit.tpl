{extends file="$layout_dir/default.tpl"}

{block name=script}
<script type="text/javascript">
$.get('{$product_dialog_url}', { id: {$id} }, function(data) {
  $('#product_dialog').append(data);
});
</script>
{/block}

{block name=body}
<div id="product_dialog"></div>
{/block}