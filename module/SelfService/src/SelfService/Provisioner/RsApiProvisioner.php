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

  public function provision($json) {
//    if(count($product) == 1) {
//      # TODO: Add a link to the RightScale deployment based on a configuration flag and/or user role
//      $prov_helper->setTags(array('rsss:provisioned_product_id='.$prov_prod->id));
//      try {
//        # Provision and record deployment
//        $deplname = sprintf("rsss-%s-%s", $product->name, $now);
//        $deplname = $this->params()->fromPost('deployment_name', $deplname);
//        $depldesc = sprintf("Created by rs_selfservice for the '%s' product", $product->name);
//        $depl = $prov_helper->provisionDeployment($deplname, $depldesc, $product->parameters);
//        $prov_depl = new ProvisionedDeployment($depl);
//        $prov_prod->provisioned_objects[] = $prov_depl;
//        $this->getLogger()->info(sprintf("Created Deployment - Name: %s href: %s", $deplname, $depl->href));
//
//        # Provision and record security groups
//        $this->getLogger()->info(sprintf("About to provision %d Security Groups", count($product->security_groups)));
//        foreach($product->security_groups as $security_group) {
//          $secGrpBaseName = $security_group->name->getVal();
//          $secGrpPrefixedName = sprintf("rsss-%s-%s", $secGrpBaseName, $now);
//          $security_group->name->setVal($secGrpPrefixedName);
//          $secGrp = $prov_helper->provisionSecurityGroup($security_group);
//          if($secGrp) {
//            $prov_grp = new ProvisionedSecurityGroup($secGrp);
//            $prov_prod->provisioned_objects[] = $prov_grp;
//          }
//        }
//
//        # Provision and record security group rules
//        $this->getLogger()->info("About to provision Security Group Rules");
//        foreach($product->security_groups as $security_group) {
//          $prov_helper->provisionSecurityGroupRules($security_group);
//        }
//
//        # Provision and record servers (and ssh keys as a byproduct)
//        $this->getLogger()->info(sprintf("About to provision %d different types of servers", count($product->servers)));
//        foreach($product->servers as $server) {
//          foreach($prov_helper->provisionServer($server, $depl) as $provisioned_model) {
//            # TODO: Shouldn't have to differentiate between the return types here, bad code smell.
//            if($provisioned_model instanceof SshKey) {
//              $prov_key = new ProvisionedSshKey($provisioned_model);
//              $prov_prod->provisioned_objects[] = $prov_key;
//            }
//            if($provisioned_model instanceof Server ) {
//              $prov_svr = new ProvisionedServer($provisioned_model);
//              $prov_prod->provisioned_objects[] = $prov_svr;
//            }
//          }
//        }
//
//        # Provision and record server arrays (and ssh keys as a byproduct)
//        $this->getLogger()->info(sprintf("About to provision %d server arrays", count($product->arrays)));
//        foreach($product->arrays as $array) {
//          foreach($prov_helper->provisionServerArray($array, $depl) as $provisioned_model) {
//            # TODO: Shouldn't have to differentiate between the return types here, bad code smell.
//            if($provisioned_model instanceof SshKey) {
//              $prov_key = new ProvisionedSshKey($provisioned_model);
//              $prov_prod->provisioned_objects[] = $prov_key;
//            }
//            if($provisioned_model instanceof ApiServerArray ) {
//              $prov_ary = new ProvisionedArray($provisioned_model);
//              $prov_prod->provisioned_objects[] = $prov_ary;
//            }
//          }
//        }
//
//        # Provision and record alert specs
//        $this->getLogger()->info(sprintf("About to provision %d alert specs", count($product->alerts)));
//        foreach($product->alerts as $alert) {
//          $prov_helper->provisionAlertSpec($alert);
//        }
//
//        # Start yer engines
//        if($product->launch_servers) {
//          $prov_helper->launchServers();
//        }
//      } catch (\Exception $e) {
//        $response['result'] = 'error';
//        $response['error'] = $e->getMessage();
//        $this->getLogger()->err("An error occurred provisioning the product. Error: " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
//      }
//
//      # All provisioned, time to store all the provisioned hrefs in the DB
//      try {
//        $this->getEntityManager()->persist($prov_prod);
//        $this->getEntityManager()->flush();
//      } catch (\Exception $e) {
//        $response['result'] = 'error';
//        $response['error'] = $e->getMessage();
//        $this->getLogger()->err("An error occurred persisting the provisioned product to the DB. Error " . $e->getMessage() . " Trace: " . $e->getTraceAsString());
//      }
//    }
  }

  public function cleanup($json) {
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