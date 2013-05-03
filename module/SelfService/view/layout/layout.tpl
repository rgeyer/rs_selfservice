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
  ->prependStylesheet($this->basePath()|cat:'/js/jquery-ui-1.10.2.custom/css/ui-darkness/jquery-ui-1.10.2.custom.css')}

  {assign var="conditional_scripts_ary" value=['conditional' => 'lt IE 9']}
  {$this->headScript()->prependFile($this->basePath()|cat:'/js/html5.js', 'text/javascript', $conditional_scripts_ary)
  ->prependFile($this->basePath()|cat:'/js/bootstrap.min.js')
  ->prependFile('https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js')
  ->appendFile('https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js')
  ->appendFile($this->basePath()|cat:'/js/functions.js')
  ->appendFile($this->basePath()|cat:'/js/image-picker.min.js')}

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

      $('#message-dialog').dialog({
        height: 350,
        width: 500,
        modal: true,
        autoOpen:false
      });

      $('a.ajaxaction').click(function(evt) {
        $('#progress-dialog').dialog('open');
        timeout_func();
        href = $(this).attr('href');
        $.ajax({
          url: href,
          dataType: 'json',
          success: function(data, status, jqXHR) {
            $('#progress-dialog').dialog('close');
            if (data.result == 'error') {
              open_message_dialog(
                $(window).height() - 100,
                $(window).width() - 100,
                "Error",
                "<p>"+data.error+"</p>"
              );
            } else if (data.messages != undefined) {
              $('#message-dialog').dialog({
                close: function( event, ui ) {
                  location.reload();
                }
              });
              content = response_messages_to_content(data.messages);
              open_message_dialog(
                350,
                500,
                "Messages",
                content
              );
            } else {
              location.reload();
            }
          },
          error: function(jqXHR, status, error) {
            content = $('<div>').append(jqXHR.responseText).find("#content");
            $('#progress-dialog').dialog('close');
            open_message_dialog(
              $(window).height() - 100,
              $(window).width() - 100,
              "Error",
              content
            );
          }
        });
        evt.preventDefault();
      });

      $('input[type="submit"].ajaxaction').click(function(evt) {
        $('#progress-dialog').dialog('open');
        timeout_func();
        form = $(this).parent();
        href = $(form).attr('action');
        $.ajax({
          url: href,
          dataType: 'json',
          type: 'POST',
          data: $(form).serializeArray(),
          success: function(data, status, jqXHR) {
            $('#progress-dialog').dialog('close');
            if (data.result == 'error') {
              open_message_dialog(
                $(window).height() - 100,
                $(window).width() - 100,
                "Error",
                "<p>"+data.error+"</p>"
              );
            } else if (data.messages != undefined) {
              $('#message-dialog').dialog({
                close: function( event, ui ) {
                  location.reload();
                }
              });
              content = response_messages_to_content(data.messages);
              open_message_dialog(
                350,
                500,
                "Messages",
                content
              );
            } else {
              location.reload();
            }
          },
          error: function(jqXHR, status, error) {
            content = $('<div>').append(jqXHR.responseText).find("#content");
            $('#progress-dialog').dialog('close');
            open_message_dialog(
              $(window).height() - 100,
              $(window).width() - 100,
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
<div class="navbar navbar-inverse navbar-fixed-top">
  <div class="navbar-inner">
    <div class="container">
      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>
      <a class="brand" href="{$this->url('home')}">{$this->translate('IT Vending Machine')}</a>
      <div class="nav-collapse collapse">
        <ul class="nav nav-tabs" id="nav">
          <li class="active"><a href="{$this->url('home')}">{$this->translate('Home')}</a></li>
          <li class="dropdown"><a class="dropdown-toggle" data-toggle="dropdown" href="#">{$this->translate('Admin')}</a>
            <ul class="dropdown-menu">
              <li><a href="{$this->url('product', ['action' => 'index'])}">{$this->translate("Products")}</a></li>
              <li><a href="{$this->url('provisionedproducts', ['action' => 'index'])}">{$this->translate('Provisioned Products')}</a></li>
              <li><a href="{$this->url('user', ['action' => 'index'])}">{$this->translate("Users")}</a></li>
            </ul>
          </li>
        </ul>
      </div><!--/.nav-collapse -->
    </div>
  </div>
</div>
<div id="content" class="container">
  {$this->content}
  <hr>
  <footer>
    <p>&copy; 2013 by Ryan J. Geyer {$this->translate('All rights reserved.')}</p>
  </footer>
</div> <!-- /container -->
<div id="progress-dialog" title="Please wait">
  <p>Processing your request</p>
  <div id="progressbar"></div>
</div>
<div id="message-dialog" title="Message"></div>
  {$this->inlineScript()}
</body>
</html>
{/if}