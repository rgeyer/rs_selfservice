<?php

class Application_Plugin_DisplayRequestParameters extends Zend_Controller_Plugin_Abstract
{
    
    public function dispatchLoopShutdown()
    {
        ob_start();
        print_r($this->getRequest()->getParams());
        $params = ob_get_contents();
        ob_clean();
        
        $this->getResponse()->appendBody("<hr style=\"margin-top: 40px;\" /><strong>Request Parameters:</strong><pre>{$params}</pre>");
    }
    
}