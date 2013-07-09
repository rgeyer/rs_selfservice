<div class="adminpane">
  <div class="list">
    <h2>Servers</h2>
    <table class="ui-widget" cellpadding="2em">
      <thead>
       <tr class="ui-widget-header ui-corner-top">
         <td>State:</td>
         <td>Name:</td>
         <td>Create Date:</td>
         <td>IP:</td>
         <td>Actions:</td>
       </tr>
      </thead>
      <tbody class="ui-widget-content">
    {foreach $servers as $server}
        <tr>
          <td>
            <div class="server">
              <img src="{$this->basePath()}/images/32server.png" />
              <img src="{$this->basePath()}/images/spacer.gif" class="state {$server->state}"/>
            </div>
          </td>
          <td>{$server->name}</td>
          <td>{$server->created_at|date_format}</td>
          <td>{$server->ip}</td>
          <td>{foreach $server->actions as $key=>$action}<a href="{$this->url($action.url_parts.controller, ['action' => $action.url_parts.action, 'id' => $server->provisioned_product_id, 'provisioned_object_id' => $server->prov_server->id])}"{if $action.is_ajax} class="ajaxaction" data-nexthop="self"{/if}><img src="{$this->basePath()}/{$action.img_path}"/></a>{/foreach}</td>
        </tr>
    {/foreach}
      </tbody>
    </table>
  </div>
  <div class="legend">
    <h2>Legend</h2>
    <table>
      <tr>
        <td>
          <div class="server">
            <img src="{$this->basePath()}/images/32server.png" />
            <img src="{$this->basePath()}/images/spacer.gif" class="state inactive"/>
          </div>
        </td>
        <td>Not Runnin'</td>
      </tr>
      <tr>
        <td>
          <div class="server">
            <img src="{$this->basePath()}/images/32server.png" />
            <img src="{$this->basePath()}/images/spacer.gif" class="state operational"/>
          </div>
        </td>
        <td>Runnin'</td>
      </tr>
      <tr>
        <td>
          <div class="server">
            <img src="{$this->basePath()}/images/32server.png" />
            <img src="{$this->basePath()}/images/spacer.gif" class="state booting"/>
          </div>
        </td>
        <td>Startin' Up</td>
      </tr>
      <tr>
        <td>
          <div class="server">
            <img src="{$this->basePath()}/images/32server.png" />
            <img src="{$this->basePath()}/images/spacer.gif" class="state decommissioning"/>
          </div>
        </td>
        <td>Shuttin' Down</td>
      </tr>
      <tr>
        <td>
          <div class="server">
            <img src="{$this->basePath()}/images/32server.png" />
            <img src="{$this->basePath()}/images/spacer.gif" class="state stranded"/>
          </div>
        </td>
        <td>Broke</td>
      </tr>
    </table>
  </div>
</div>