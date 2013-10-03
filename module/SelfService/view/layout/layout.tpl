{if isset($use_layout) && $use_layout === false}
{$this->content}
{else}
{$this->doctype()}
<html lang="en">
<head>
  <meta charset="utf-8">
  {$this->headTitle($this->translate('RightScale IT Vending Machine'))->setSeparator(' - ')->setAutoEscape(false)}

  {$this->headMeta()->appendName('viewport', 'width=device-width, initial-scale=1.0')}

  {assign var="headlink_ary" value=['rel' => 'shortcut icon', 'type' => 'image/vnd.microsoft.icon', 'href' => $this->basePath()|cat:'/images/favicon.ico']}
  {$this->headLink($headlink_ary)->prependStylesheet($this->basePath()|cat:'/css/bootstrap-responsive.min.css')
  ->prependStylesheet($this->basePath()|cat:'/css/style.css')
  ->prependStylesheet($this->basePath()|cat:'/css/default.css')
  ->prependStylesheet($this->basePath()|cat:'/css/bootstrap.min.css')
  ->prependStylesheet($this->basePath()|cat:'/css/image-picker.css')
  ->prependStylesheet($this->basePath()|cat:'/js/jquery-ui-1.10.2.custom/css/ui-darkness/jquery-ui-1.10.2.custom.css')
  ->prependStylesheet($this->basePath()|cat:'/css/jquery.bxslider.css')}

  {assign var="conditional_scripts_ary" value=['conditional' => 'lt IE 9']}
  {$this->headScript()->prependFile($this->basePath()|cat:'/js/html5.js', 'text/javascript', $conditional_scripts_ary)
  ->prependFile($this->basePath()|cat:'/js/bootstrap.min.js')
  ->prependFile('https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js')
  ->appendFile('https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js')
  ->appendFile($this->basePath()|cat:'/js/functions.js')
  ->appendFile($this->basePath()|cat:'/js/image-picker.min.js')
  ->appendFile($this->basePath()|cat:'/js/vendor/livereload-js/dist/livereload.js')
  ->appendFile($this->basePath()|cat:'/js/prefixfree.min.js')
  ->appendFile($this->basePath()|cat:'/js/application.js')
  ->appendFile($this->basePath()|cat:'/js/vendor/jquery.bxslider.min.js')
  ->appendFile($this->basePath()|cat:'/js/behaviors/behavior.bxslider.js')}

  {literal}
  <script>
    $(document).ready(function() {
      $( "#progressbar" ).progressbar({
        value: 0
      });

      $('#progress-dialog').dialog({
        height: 180,
        width: 500,
        modal: true,
        autoOpen:false
      });

      $(document).on('click', 'a.ajaxaction', function(evt) {
        messageDlgAsParent = $(this).closest('#message-dialog');
        if(messageDlgAsParent.length > 0) { $("#message-dialog").modal('hide'); }
        $('#progress-dialog').dialog('open');
        timeout_func();
        data = {};
        href = $(this).attr('href');
        method = 'GET';
        nexthop = $(this).attr('data-nexthop');
        if($(this).attr('data-method')) {
          method = $(this).attr('data-method');
        }
        formproperty = $(this).attr('data-form');
        if(formproperty) {
          form = $(formproperty);
          data = convertFormToKeyValuePairJson(form);
        }
        $.ajax({
          url: href,
          dataType: 'json',
          type: method,
          data: data,
          success: function(data, status, jqXHR) {
            if (data.result == 'error') {
              $('#progress-dialog').dialog('close');
              open_message_dialog(
                "Error",
                "<p>"+data.error+"</p>"
              );
            } else if (data.messages != undefined && data.messages.length > 0) {
              $('#message-dialog').on('hidden', function() {
                if(nexthop == 'self') {
                  location.reload();
                } else if(nexthop) {
                  window.location.href = nexthop
                }
                $(this).on('hidden');
              });
              content = response_messages_to_content(data.messages);
              $('#progress-dialog').dialog('close');
              open_message_dialog(
                "Messages",
                content
              );
            } else {
              if(nexthop == 'self') {
                location.reload();
              } else if(nexthop) {
                window.location.href = nexthop
              }
              $('#progress-dialog').dialog('close');
            }
          },
          error: function(jqXHR, status, error) {
            content = $('<div>').append(jqXHR.responseText).find("#content");
            if(content.length == 0) {
              data = $.parseJSON(jqXHR.responseText);
              content = response_messages_to_content(data.messages);
            }
            $('#progress-dialog').dialog('close');
            open_message_dialog(
              "Error",
              content
            );
          }
        });
        evt.preventDefault();
      });
    });
  </script>
  {/literal}

</head>
<body>
  <header>
    <div class="container">
      <a class="brand" href="{$this->url('home')}">{$this->translate('IT Vending Machine')}</a>
      <nav>
        <ul class="nav nav-tabs" id="nav">
          <li{if $this->controllerName() == 'SelfService\Controller\Index'} class="active"{/if}><a href="{$this->url('home')}">{$this->translate('Home')}</a></li>
          <li{if $this->controllerName() == 'SelfService\Controller\Product'} class="active"{/if}><a href="{$this->url('product', ['action' => 'index'])}">{$this->translate("Products")}</a></li>
          <li{if $this->controllerName() == 'SelfService\Controller\ProvisionedProduct'} class="active"{/if}><a href="{$this->url('provisionedproducts', ['action' => 'index'])}">{$this->translate('Provisioned Products')}</a></li>
          <li class="{if $this->controllerName() == 'SelfService\Controller\User'}active {/if}dropdown">
            <a class="dropdown-toggle" data-toggle="dropdown" href="#">{$this->translate('Users')}<b class="caret"></b></a>
            <ul class="dropdown-menu">
              <li><a href="{$this->url('user', ['action' => 'index'])}">{$this->translate("List")}</a></li>
              <li><a href="{$this->url('user', ['action' => 'apiadd'])}">{$this->translate("Add API User")}</a></li>
            </ul>
          </li>
        </ul>
      </nav>
    </div>
  </header>
  <div id="main" role="main">
    <div id="content" class="container">
      {$this->content}
    </div> <!-- /container -->
  </div>
  <div class="container navbar-fixed-bottom"
    <hr>
  <footer>
    <div class="container">
      <p>&copy; 2013 by Ryan J. Geyer {$this->translate('All rights reserved.')}</p>
    </div>
  </footer>
</div>
<div id="progress-dialog" title="Please wait">
  <p>Processing your request</p>
  <div id="progressbar"></div>
</div>
<div id="message-dialog" class="modal hide fade">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3>Header</h3>
  </div>
  <div class="modal-body"></div>
  <div class="modal-footer">
  </div>
</div>
{$this->inlineScript()}
</body>
</html>
{/if}