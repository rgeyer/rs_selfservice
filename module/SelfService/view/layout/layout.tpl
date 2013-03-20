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
  ->prependStylesheet($this->basePath()|cat:'/js/jquery-ui-1.10.2.custom/css/ui-darkness/jquery-ui-1.10.2.custom.css')}

  {assign var="conditional_scripts_ary" value=['conditional' => 'lt IE 9']}
  {$this->headScript()->prependFile($this->basePath()|cat:'/js/html5.js', 'text/javascript', $conditional_scripts_ary)
  ->prependFile($this->basePath()|cat:'/js/bootstrap.min.js')
  ->prependFile('http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js')
  ->appendFile('http://ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js')}

  {literal}
  <script>
    $(document).ready(function() {
      $('#nav').menu({position:{my: "left top", at: "left top+40"}});
      // Eventually my nav initialization will go here.
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
        <ul class="nav" id="nav">
          <li class="active"><a href="{$this->url('home')}">{$this->translate('Home')}</a></li>
          <li class="active"><a href="{$this->url('admin')}">{$this->translate('Admin')}</a>
            <ul class="nav">
              <li><a href="{$this->url('admin/provisionedproducts')}/provisionedproducts">{$this->translate('Provisioned Products')}</a></li>
            </ul>
          </li>
        </ul>
      </div><!--/.nav-collapse -->
    </div>
  </div>
</div>
<div class="container">
  {$this->content}
  <hr>
  <footer>
    <p>&copy; 2013 by Ryan J. Geyer {$this->translate('All rights reserved.')}</p>
  </footer>
</div> <!-- /container -->
  {$this->inlineScript()}
</body>
</html>
{/if}