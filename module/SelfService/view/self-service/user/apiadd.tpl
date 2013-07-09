<form id="api_add_user_form" class="form-horizontal">
  <input type="hidden" id="authorized" name="authorized" value="true" />
  <input type="hidden" id="api_user" name="api_user" value="true" />
  <div class="control-group">
    <label class="control-label" for="email">Email</label>
    <div class="controls">
      <input type="text" id="email" name="email" placeholder="Email">
    </div>
  </div>
  <div class="control-group">
    <label class="control-label" for="password">Password</label>
    <div class="controls">
      <input type="password" id="password" name="password" placeholder="Password">
    </div>
  </div>
  <div class="control-group">
    <div class="controls">
      <a class="btn btn-primary ajaxaction"
         id="addbtn"
         href="{$this->url('api-user')}"
         data-method="POST"
         data-form="#api_add_user_form"
         data-nexthop="{$this->url('user', ['action' => 'index'])}">Add</a>
    </div>
  </div>
</form>