<form id="product_edit_form" class="form-horizontal">
  <div class="control-group">
    <label for="name">Name:</label>
    <div class="controls">
      <input type="text" name="name" id="name" value="{$product->name}" />
    </div>
  </div>
  <div class="control-group">
    <label for="launch_servers">Launch Servers:</label>
    <div class="controls">
      <input type="checkbox" name="launch_servers" id="launch_servers"{if $product->launch_servers} checked{/if}/>
    </div>
  </div>
  <label for="icon_filename">Icon:</label>
  <select name="icon_filename" id="icon_filename" class="image-picker">
{foreach $icons as $icon}
    <option data-img-src="{$this->basePath()}/images/icons/{$icon}" value="{$icon}"{if $product->icon_filename == $icon} selected{/if}>{$icon}</option>
{/foreach}
  </select>
  <div class="accordion">
    <h3>Icons</h3>
    <div>

    </div>
  </div>

  <div class="control-group">
    <div class="controls">
      <a class="btn btn-primary ajaxaction"
         href="{$this->url('product', ['action' => 'update', 'id' => $product->id])}"
         data-method="POST"
         data-form="#product_edit_form"
         data-nexthop="{$this->url('product', ['action' => 'index'])}">Save</a>
    </div>
  </div>
</form>

{literal}
<script>
  $('#icon_filename').imagepicker();
  selector = $('.image_picker_selector').detach();
  $('.accordion > div').append(selector);
  $('.accordion').accordion({collapsible: true, heightStyle: 'content', active: false});
</script>
{/literal}