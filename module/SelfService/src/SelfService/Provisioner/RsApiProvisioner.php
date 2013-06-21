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

namespace SelfService\Provisioner;

class RsApiProvisioner extends AbstractProvisioner {

  /**
   * @return \SelfService\Service\ProvisioningHelper
   */
  protected function getProvisioningHelper() {
    return $this->getServiceLocator()->get('rs_provisioning_helper');
  }

  /**
   * @return \SelfService\Service\CleanupHelper
   */
  protected function getCleanupHelper() {
    return $this->getServiceLocator()->get('rs_cleanup_helper');
  }

  /**
   * @return \Doctrine\ODM\MongoDB\DocumentManager
   */
  protected function getDocumentManager() {
    return $this->getServiceLocator()->get('doctrine.documentmanager.odm_default');
  }

  /**
   * {@inheritdoc}
   */
  public function provision($provisioned_product_id, $json) {
    $this->getLogger()->debug("RsApiProvisioner::provision called with the following json \n".$json);
    // TODO: When I can, validate the json against the schema.
    $product = json_decode($json);
    $prov_helper = $this->getProvisioningHelper();
    $prov_prod_service = $this->getProvisionedProductService();
    $now = time();

    $prov_helper->setTags(array('rsss:provisioned_product_id='.$provisioned_product_id));

    // Security Groups are always first
    foreach($product->resources as $resource) {
      if($resource->resource_type == 'security_group') {
        $resource->name = sprintf("%s-%s", $resource->name, $now);
        $sg = $prov_helper->provisionSecurityGroup($resource);
        $prov_prod_service->addProvisionedObject(
          $provisioned_product_id,
          array(
            'href' => $sg->href,
            'cloud_id' => $sg->cloud_id,
            'type' => 'security_group'
          )
        );
      }
    }

    // Then the Security Group Rules
    foreach($product->resources as $resource) {
      if($resource->resource_type == 'security_group') {
        $prov_helper->provisionSecurityGroupRules($resource);
      }
    }

    // Now deployments and all their sub resources
    foreach($product->resources as $resource) {
      if($resource->resource_type == 'deployment') {
        $inputs = array();
        foreach($resource->inputs as $input) {
          $inputs[$input->name] = $inputs->value;
        }
        $depldesc = sprintf("Created by rs_selfservice for the '%s' product", $product->name);
        $deployment = $prov_helper->provisionDeployment($resource->name, $depldesc, $inputs);
        $prov_prod_service->addProvisionedObject(
          $provisioned_product_id,
          array(
            'href' => $deployment->href,
            'type' => 'deployment'
          )
        );

        foreach($resource->servers as $server) {
          $provisioned_objects = $prov_helper->provisionServer($server, $deployment);
          foreach($provisioned_objects as $provisioned_object) {
            $params = array(
              'href' => $provisioned_object->href,
              'type' => ($provisioned_object instanceof \RGeyer\Guzzle\Rs\Model\Mc\SshKey) ? 'ssh_key' : 'server'
            );
            if($provisioned_object->cloud_id)
            {
              $params['cloud_id'] = $provisioned_object->cloud_id;
            }
            $prov_prod_service->addProvisionedObject($provisioned_product_id, $params);
          }
        }

        foreach($resource->server_arrays as $array) {
          $provisioned_objects = $prov_helper->provisionServerArray($array, $deployment);
          foreach($provisioned_objects as $provisioned_object) {
            $params = array(
              'href' => $provisioned_object->href,
              'type' => ($provisioned_object instanceof \RGeyer\Guzzle\Rs\Model\Mc\SshKey) ? 'ssh_key' : 'server_array'
            );
            if($provisioned_object->cloud_id)
            {
              $params['cloud_id'] = $provisioned_object->cloud_id;
            }
            $prov_prod_service->addProvisionedObject($provisioned_product_id, $params);
          }
        }
      }
    }

    if($product->launch_servers) {
      $prov_helper->launchServers();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function cleanup($provisioned_product_id, $json) {
    $this->getLogger()->debug("RsApiProvisioner::cleanup called with the following json \n".$json);
    // TODO: When I can, validate the json against the schema.
    $provisioned_objects = json_decode($json);
    $clean_helper = $this->getCleanupHelper();
    $prov_prod_service = $this->getProvisionedProductService();
    $waiting_arrays = 0;
    $waiting_servers = 0;

    # Arrays and servers first since they may be running, and we don't
    # want to remove resources they're consuming.
    foreach($provisioned_objects as $object) {
      if($object->type == "server_array") {
        if($clean_helper->cleanupServerArray($object)) {
          $prov_prod_service->removeProvisionedObject($provisioned_product_id, $object->id);
        } else {
          $waiting_arrays++;
        }
      }
    }

    foreach($provisioned_objects as $object) {
      if($object->type == "server") {
        if($clean_helper->cleanupServer($object)) {
          $prov_prod_service->removeProvisionedObject($provisioned_product_id, $object->id);
        } else {
          $waiting_servers++;
        }
      }
    }

    # Wait up if we're waiting on servers or array instances
    if(($waiting_arrays + $waiting_servers) > 0) {
      $this->addMessage(
        sprintf(
          "There were %d servers still running and %d arrays with running instances.  A terminate request has been sent.  When the servers have been terminated, you can try to delete the product again",
          $waiting_servers,
          $waiting_arrays
        )
      );
      return;
    }

    foreach($provisioned_objects as $object) {
      if($object->type == "deployment") {
        $clean_helper->cleanupDeployment($object);
        $prov_prod_service->removeProvisionedObject($provisioned_product_id, $object->id);
      }
    }

    foreach($provisioned_objects as $object) {
      if($object->type == "ssh_key") {
        $clean_helper->cleanupSshKey($object);
        $prov_prod_service->removeProvisionedObject($provisioned_product_id, $object->id);
      }
    }

    foreach($provisioned_objects as $object) {
      if($object->type == "security_group") {
        $clean_helper->cleanupSecurityGroupRules($object);
      }
    }

    foreach($provisioned_objects as $object) {
      if($object->type == "security_group") {
        $clean_helper->cleanupSecurityGroup($object);
        $prov_prod_service->removeProvisionedObject($provisioned_product_id, $object->id);
      }
    }

    # If we got this far, the provisioned object may be deleted.
    $prov_prod_service->remove($provisioned_product_id);
  }

}