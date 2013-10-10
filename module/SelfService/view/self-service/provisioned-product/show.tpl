<div class="adminpane">
  <div class="grid">
    <div class="col-3-4">
      <div class="list module">
        <h2>Servers</h2>
        <table class="ui-widget" cellpadding="2em">
          <thead>
           <tr class="ui-widget-header ui-corner-top">
             <td width="7%">State</td>
             <td width="20%">Name</td>
             <td width="20%">Create Date</td>
             <td width="45%">IP</td>
             <td width="8%">Actions</td>
           </tr>
          </thead>
          <tbody class="ui-widget-content">
        {foreach $servers as $server}
            <tr>
              <td>
                <div class="server">
                  <!--<img src="{$this->basePath()}/images/32server.png" />-->
                  <!--<img src="{$this->basePath()}/images/spacer.gif" class="state {$server->state}"/>-->
                  <span class="state {$server->state}"></span>
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
    </div>
    <div class="col-1-4">
      <div class="legend module">
      <h4>Legend</h4>
        <table>
          <tr>
            <td>
              <div class="server">
                <!--<img src="{$this->basePath()}/images/32server.png" />
                <img src="{$this->basePath()}/images/spacer.gif" class="state inactive"/>-->
                <span class="state inactive"></span>
              </div>
            </td>
            <td>Not Runnin'</td>
          </tr>
          <tr>
            <td>
              <div class="server">
                <!--<img src="{$this->basePath()}/images/32server.png" />
                <img src="{$this->basePath()}/images/spacer.gif" class="state operational"/>-->
                <span class="state operational"></span>
              </div>
            </td>
            <td>Runnin'</td>
          </tr>
          <tr>
            <td>
              <div class="server">
                <!--<img src="{$this->basePath()}/images/32server.png" />
                <img src="{$this->basePath()}/images/spacer.gif" class="state booting"/>-->
                 <span class="state booting"></span>
              </div>
            </td>
            <td>Startin' Up</td>
          </tr>
          <tr>
            <td>
              <div class="server">
                <!--<img src="{$this->basePath()}/images/32server.png" />
                <img src="{$this->basePath()}/images/spacer.gif" class="state decommissioning"/>-->
                <span class="state decommissioning"></span>
              </div>
            </td>
            <td>Shuttin' Down</td>
          </tr>
          <tr>
            <td>
              <div class="server">
                <!--<img src="{$this->basePath()}/images/32server.png" />
                <img src="{$this->basePath()}/images/spacer.gif" class="state stranded"/>-->
                <span class="state stranded"></span>
              </div>
            </td>
            <td>Broke</td>
          </tr>
        </table>
      </div>
    </div>
  </div>
</div>