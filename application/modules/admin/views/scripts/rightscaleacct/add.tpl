{extends file="$layout_dir/default.tpl"}

{block name=body}
<form method="post">
  <fieldset>
    <label>Acct #:</label>
    <input type="text" name="acct_num" />
    <label>Email:</label>
    <input type="text" name="email" />
    <label>Password:</label>
    <input type="password" name="password" />
    <input type="submit" value="Submit" />
  </fieldset>
</form>
{/block}