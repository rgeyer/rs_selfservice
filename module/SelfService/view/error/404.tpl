<h1>{$this->translate('A 404 error occurred')}</h1>
<h2>{$this->message}</h2>

{if isset($this->reason) && $this->reason}

{assign var="reasonMessage" value="We cannot determine at this time why a 404 was generated."}
{if $this->reason == 'error-controller-cannot-dispatch'}
  {assign var="reasonMessage" value=$this->translate('The requested controller was unable to dispatch the request.')}
{/if}

{if $this->reason == 'error-controller-not-found'}
  {assign var="reasonMessage" value=$this->translate('The requested controller could not be mapped to an existing controller class.')}
{/if}

{if $this->reason == 'error-controller-invalid'}
  {assign var="reasonMessage" value=$this->translate('The requested controller was not dispatchable.')}
{/if}

{if $this->reason == 'error-router-no-match'}
  {assign var="reasonMessage" value=$this->translate('The requested URL could not be matched by routing.')}
{/if}

<p>{$reasonMessage}</p>

{/if}

{if isset($this->controller) && $this->controller }

<dl>
    <dt>{$this->translate('Controller')}:</dt>
    <dd>{$this->escapeHtml($this->controller)}
{if isset($this->controller_class)
    && $this->controller_class
    && $this->controller_class != $this->controller}
    ({sprintf($this->translate('resolves to %s'), $this->escapeHtml($this->controller_class))})
{/if}
?>
</dd>
</dl>

{/if}

{if isset($this->display_exceptions) && $this->display_exceptions }

{if isset($this->exception)}
<hr/>
<h2>{$this->translate('Additional information')}:</h2>
<h3>{get_class($this->exception)}</h3>
<dl>
    <dt>{$this->translate('File')}:</dt>
    <dd>
        <pre class="prettyprint linenums">{$this->exception->getFile()}:{$this->exception->getLine()}</pre>
    </dd>
    <dt>{$this->translate('Message')}:</dt>
    <dd>
        <pre class="prettyprint linenums">{$this->exception->getMessage()}</pre>
    </dd>
    <dt>{$this->translate('Stack trace')}:</dt>
    <dd>
        <pre class="prettyprint linenums">{$this->exception->getTraceAsString()}</pre>
    </dd>
</dl>
{assign var="e" value=$this->exception->getPrevious()}
{if $e != null}
<hr/>
<h2>{$this->translate('Previous exceptions')}:</h2>
<ul class="unstyled">
    {while $e}
    <li>
        <h3>{get_class($e)}</h3>
        <dl>
            <dt>{$this->translate('File')}:</dt>
            <dd>
                <pre class="prettyprint linenums">{$e->getFile()}:{$e->getLine()}</pre>
            </dd>
            <dt>{$this->translate('Message')}:</dt>
            <dd>
                <pre class="prettyprint linenums">{$e->getMessage()}</pre>
            </dd>
            <dt>{$this->translate('Stack trace')}:</dt>
            <dd>
                <pre class="prettyprint linenums">{$e->getTraceAsString()}</pre>
            </dd>
        </dl>
    </li>
    {assign var="e" value=$e->getPrevious()}
    {/while}
</ul>
{/if}

{else}

<h3>{$this->translate('No Exception available')}</h3>

{/if}

{/if}