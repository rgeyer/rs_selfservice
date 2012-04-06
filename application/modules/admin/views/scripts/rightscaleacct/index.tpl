{extends file="$layout_dir/default.tpl"}

{block name=body}
<table>
	<thead>
	 <tr>
	   <td>Id:</td>
	   <td>Acct #:</td>
	   <td>Email:</td>
	   <td>Actions: </td>
	 </tr>	
	</thead>
	<tbody>
{foreach $rs_accts as $acct}
    <tr>
      <td>{$acct->id}</td>
      <td>{$acct->acct_id}</td>
      <td>{$acct->email}</td>
      <td>{foreach $actions as $key=>$action}<a href="{$action.uri_prefix}?id={$acct->id}"><img src="/images/delete.png" /></a>{/foreach}</td>
    </tr>
{/foreach}
  </tbody>
</table>
{/block}