<?php
/*
Copyright (c) 2013 Ryan J. Geyer <me@ryangeyer.com>

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
'Software'), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

namespace SelfService\Service;

use RGeyer\Guzzle\Rs\Common\ClientFactory;
use RGeyer\Guzzle\Rs\Model\Mc\ServerArray;

class CleanupHelper {

  /**
   * A RightScale 1.5 API client.  This is public to allow mocking for unit testing. Likely
   * won't want to muck with this much
   * @var \RGeyer\Guzzle\Rs\RightScaleClient
   */
  public $client;

  /**
   * @var \Zend\Log\Logger
   */
  protected $log;

  /**
   * @param $rs_account
   * @param $rs_email
   * @param $rs_password
   * @param \Zend\Log\Logger $log
   */
  public function __construct($rs_account, $rs_email, $rs_password, $log) {
    $this->log = $log;
    ClientFactory::setCredentials($rs_account, $rs_email, $rs_password);
    $this->client = ClientFactory::getClient('1.5');
  }

  /**
   * @param \stdClass $array A json representation of a \SelfService\Document\ProvisionedObject for a provisioned array, used only for it's ->href param
   * @return bool True if the array had no running instances and was destroyed.  False if the array had running instances, and the multi_terminate method was called
   */
  public function cleanupServerArray($array) {
    $api_array = $this->client->newModel('ServerArray');
    $api_array->find_by_href($array->href);
    if($api_array->instances_count > 0) {
      $api_array->multi_terminate();
      return false;
    } else {
      $api_array->destroy();
      return true;
    }
  }

  /**
   * @param \stdClass $server A json representation of a \SelfService\Document\ProvisionedObject for a provisioned array, used only for it's ->href param
   * @return bool True if the array had no running instances and was destroyed.  False if the server was running, and the servers_terminate method was called
   */
  public function cleanupServer($server){
    $api_server = $this->client->newModel('Server');
    $api_server->find_by_href($server->href);
    if(!in_array($api_server->state, array('inactive','stopped','decommissioning'))) {
      $api_server->terminate();
      return false;
    } else if($api_server->state == "decommissioning") {
      return false;
    } else {
      $api_server->destroy();
      return true;
    }
  }

  /**
   * @param \stdClass $deployment A json representation of a \SelfService\Document\ProvisionedObject for a provisioned array, used only for it's ->href param
   * @return bool Always true, throws exceptions if an error occurred
   */
  public function cleanupDeployment($deployment) {
    $api_deployment = $this->client->newModel('Deployment');
    $api_deployment->find_by_href($deployment->href);
    $api_deployment->destroy();
    return true;
  }

  /**
   * @param \stdClass $ssh_key A json representation of a \SelfService\Document\ProvisionedObject for a provisioned ssh key, used only for it's ->href and ->cloud_id params
   * @return bool Always true, throws exceptions if an error occurred
   */
  public function cleanupSshKey($ssh_key) {
    $api_ssh_key = $this->client->newModel('SshKey');
    $api_ssh_key->cloud_id = $ssh_key->cloud_id;
    $api_ssh_key->find_by_href($ssh_key->href);
    $api_ssh_key->destroy();
    return true;
  }


  /**
   * @param \stdClass $sec_grp A json representation of a \SelfService\Document\ProvisionedObject for a provisioned security group, used only for it's ->href param
   * @return bool Always true, throws exceptions if an error occurred
   */
  public function cleanupSecurityGroupRules($sec_grp) {
    $api_sec_grp = $this->client->newModel('SecurityGroup');
    $api_sec_grp->cloud_id = $sec_grp->cloud_id;
    $api_sec_grp->find_by_href($sec_grp->href);
    foreach($api_sec_grp->security_group_rules() as $rule) {
      # Accommodate https://rightsite.gitsrc.com/trac/ticket/14366
      # Requires the full href including cloud and security group
      $long_href = str_replace('/api/', '', $sec_grp->href) . str_replace('/api', '', $rule->href);
      $rule->destroy(array('path' => $long_href));
    }
    return true;
  }


  /**
   * @param \stdClass $sec_grp A json representation of a \SelfService\Document\ProvisionedObject for a provisioned security group, used only for it's ->href and ->cloud_id params
   * @return bool Always true, throws exceptions if an error occurred
   */
  public function cleanupSecurityGroup($sec_grp) {
    $api_sec_grp = $this->client->newModel('SecurityGroup');
    $api_sec_grp->cloud_id = $sec_grp->cloud_id;
    $api_sec_grp->find_by_href($sec_grp->href);
    $api_sec_grp->destroy();
    return true;
  }
}