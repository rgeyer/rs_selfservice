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
          array(
            'href' => $deployment->href,
            'type' => 'deployment'
          )
        );

        foreach($resource->servers as $server) {
          $provisioned_objects = $prov_helper->provisionServer($server, $deployment);
          foreach($provisioned_objects as $provisioned_object) {
            $prov_prod_service->addProvisionedObject(
              array(
                'href' => $provisioned_object->href,
                'type' => ($provisioned_object instanceof \RGeyer\Guzzle\Rs\Model\Mc\SshKey) ? 'ssh_key' : 'server'
              )
            );
          }
        }

        foreach($resource->server_arrays as $array) {
          $provisioned_objects = $prov_helper->provisionServerArray($array, $deployment);
          foreach($provisioned_objects as $provisioned_object) {
            $prov_prod_service->addProvisionedObject(
              array(
                'href' => $provisioned_object->href,
                'type' => ($provisioned_object instanceof \RGeyer\Guzzle\Rs\Model\Mc\SshKey) ? 'ssh_key' : 'server_array'
              )
            );
          }
        }
      }
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
    $prov_prod = $this->getProvisionedProductService()->find($provisioned_product_id);
    foreach($provisioned_objects as $object) {
      if($object->type == "security_group") {
        $clean_helper->cleanupSecurityGroupRules($object);
      }
    }

    foreach($provisioned_objects as $object) {
      if($object->type == "security_group") {
        $clean_helper->cleanupSecurityGroup($object);
      }
    }

//    $em = $this->getEntityManager();
//    $cleanup_helper = $this->getServiceLocator()->get('rs_cleanup_helper');
//    $prov_product = $em->getRepository('SelfService\Entity\ProvisionedProduct')->find($product_id);
//    if(count($prov_product) == 1) {
//      $keep_going = false;
//      do {
//        $prov_arrays = array();
//        $prov_severs = array();
//        $prov_depl = null;
//        $prov_sshkeys = array();
//        $prov_secgrps = array();
//        foreach($prov_product->provisioned_objects as $provisioned_obj) {
//          if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedDeployment')) {
//            $prov_depl = $provisioned_obj;
//          }
//          if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedServer')) {
//            $prov_servers[] = $provisioned_obj;
//          }
//          if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedArray')) {
//            $prov_arrays[] = $provisioned_obj;
//          }
//          if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedSshKey')) {
//            $prov_sshkeys[] = $provisioned_obj;
//          }
//          if(is_a($provisioned_obj, 'SelfService\Entity\ProvisionedSecurityGroup')) {
//            $prov_secgrps[] = $provisioned_obj;
//          }
//        }
//
//        # Stop and destroy arrays
//        if(count($prov_arrays) > 0) {
//          foreach($prov_arrays as $prov_array) {
//            if($cleanup_helper->cleanupServerArray($prov_array)) {
//              $prov_product->provisioned_objects->removeElement($prov_array);
//              $em->remove($prov_array);
//              $em->flush();
//            } else {
//              $response['wait_for_decom']['arrays'][] = $prov_array->href;
//            }
//          }
//        }
//
//        # Stop and destroy the servers
//        if(count($prov_servers) > 0) {
//          foreach($prov_servers as $prov_server) {
//            if($cleanup_helper->cleanupServer($prov_server)) {
//              $prov_product->provisioned_objects->removeElement($prov_server);
//              $em->remove($prov_server);
//              $em->flush();
//            } else {
//              $response['wait_for_decom']['servers'][] = $prov_server->href;
//            }
//          }
//        }
//
//        # Wait up if we're waiting on servers or array instances
//        if(	array_key_exists('wait_for_decom', $response) && count($response['wait_for_decom']) > 0) {
//          $response['messages'][] = sprintf(
//            "There were %d servers still running and %d arrays with running instances.  A terminate request has been sent.  When the servers have been terminated, you can try to delete the product again",
//            count($response['wait_for_decom']['servers']),
//            count($response['wait_for_decom']['arrays'])
//          );
//          $keep_going = true;
//          break;
//        }
//
//        # Destroy the deployment
//        if($prov_depl) {
//          $cleanup_helper->cleanupDeployment($prov_depl);
//          $prov_product->provisioned_objects->removeElement($prov_depl);
//          $em->remove($prov_depl);
//          $em->flush();
//        }
//
//        # Destroy SSH key
//        if(count($prov_sshkeys) > 0) {
//          foreach($prov_sshkeys as $prov_sshkey) {
//            $cleanup_helper->cleanupSshKey($prov_sshkey);
//            $prov_product->provisioned_objects->removeElement($prov_sshkey);
//            $em->remove($prov_sshkey);
//            $em->flush();
//          }
//        }
//
//        # Destroy SecurityGroups
//        if(count($prov_secgrps) > 0) {
//          foreach($prov_secgrps as $prov_secgrp) {
//            $cleanup_helper->cleanupSecurityGroupRules($prov_secgrp);
//          }
//
//          foreach($prov_secgrps as $prov_secgrp) {
//            $cleanup_helper->cleanupSecurityGroup($prov_secgrp);
//            $prov_product->provisioned_objects->removeElement($prov_secgrp);
//            $em->remove($prov_secgrp);
//            $em->flush();
//          }
//        }
//      } while ($keep_going);
//      if(!$keep_going) {
//        $em->remove($prov_product);
//        $em->flush();
//      }
//    }
  }

}