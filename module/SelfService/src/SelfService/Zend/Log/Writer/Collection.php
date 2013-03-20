<?php

namespace SelfService\Zend\Log\Writer;

use Zend\Log\Writer\AbstractWriter;
use Zend\Log\Formatter\Simple as SimpleFormatter;

class Collection extends AbstractWriter {

  public $messages = array();

  public function __construct() {
    if($this->formatter === null) {
      $this->formatter = new SimpleFormatter();
    }
  }

  public function doWrite(array $event) {
    $this->messages[] = $this->formatter->format($event);
  }
}