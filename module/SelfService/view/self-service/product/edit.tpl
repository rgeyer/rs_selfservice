<form method="POST" action="{$this->url('product', ['action' => 'update', 'id' => $product->id])}">
  <label for="name">Name:</label>
  <input type="text" name="name" id="name" value="{$product->name}" />
  <label for="launch_servers">Launch Servers:</label>
  <input type="checkbox" name="launch_servers" id="launch_servers"{if $product->launch_servers} checked{/if}/>
  <div class="clearfix"></div>
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

  <input type="submit" name="submit" value="Save" class="btn ajaxaction"/>
</form>

{literal}
<script>
  $('#icon_filename').imagepicker();
  selector = $('.image_picker_selector').detach();
  $('.accordion > div').append(selector);
  $('.accordion').accordion({collapsible: true, heightStyle: 'content', active: false});
</script>
{/literal}