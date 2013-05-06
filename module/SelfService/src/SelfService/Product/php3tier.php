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

namespace SelfService\Product;

use Doctrine\ORM\EntityManager;
use SelfService\Entity\Provisionable\Server;
use SelfService\Entity\Provisionable\Product;
use SelfService\Entity\Provisionable\AlertSpec;
use SelfService\Entity\Provisionable\ServerArray;
use SelfService\Entity\Provisionable\SecurityGroup;
use SelfService\Entity\Provisionable\ServerTemplate;
use SelfService\Entity\Provisionable\SecurityGroupRule;
use SelfService\Entity\Provisionable\MetaInputs\TextProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\InputProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\CloudProductMetaInput;
use SelfService\Entity\Provisionable\MetaInputs\NumberProductMetaInput;

class php3tier implements ProductInterface {

  public static function add(EntityManager $em) {
    $product = new Product();

    // START Cloud ProductMetaInput
    $cloud_metainput = new CloudProductMetaInput();
    $cloud_metainput->default_value = 1;
    $cloud_metainput->input_name = 'cloud';
    $cloud_metainput->display_name = 'Cloud';
    $cloud_metainput->description = 'The cloud to create the 3-Tier in';

    $em->persist($cloud_metainput);
    // END Cloud ProductMetaInput

    // START Text ProductMetaInput
    $text_metainput = new TextProductMetaInput();
    $text_metainput->default_value = 'phparray';
    $text_metainput->input_name = 'array_tag';
    $text_metainput->display_name = 'Array Vote Tag';
    $text_metainput->description = 'A tag used to identify the autoscaling array';

    $em->persist($text_metainput);
    // END Text ProductMetaInput

    // START Deployment Name MetaInput
    $deployment_name = new TextProductMetaInput();
    $deployment_name->default_value = 'PHP 3-Tier';
    $deployment_name->input_name = 'deployment_name';
    $deployment_name->display_name = 'Deployment Name';
    $deployment_name->description = 'The name of the deployment which will be created in RightScale';

    $em->persist($deployment_name);
    // END Deployment Name MetaInput

    // START Deployment Inputs for Application Server
    $app__database_name = new InputProductMetaInput('app/database_name', 'text:dbschema');
    $app__database_name->input_name = 'dbschema';
    $app__database_name->display_name = 'Database Schema Name';
    $app__database_name->description = 'Database Schema Name';
    $em->persist($app__database_name);

    $db__dns__master__fqdn = new InputProductMetaInput('db/dns/master/fqdn', 'text:localhost');
    $db__dns__master__fqdn->input_name = 'dbfqdn';
    $db__dns__master__fqdn->display_name = 'Database FQDN';
    $db__dns__master__fqdn->description = 'Database FQDN';
    $em->persist($db__dns__master__fqdn);

    $db__provider_type = new InputProductMetaInput('db/provider_type', 'text:db_mysql_5.5');
    $db__provider_type->input_name = 'dbtype';
    $db__provider_type->display_name = 'Database Type';
    $db__provider_type->description = 'Database Type';
    $em->persist($db__provider_type);

    $repo__default__repository = new InputProductMetaInput('repo/default/repository', 'text:git://github.com/rightscale/examples.git');
    $repo__default__repository->input_name = 'reporepository';
    $repo__default__repository->display_name = 'Repository URL';
    $repo__default__repository->description = 'Repository URL';
    $em->persist($repo__default__repository);

    $repo__default__revision = new InputProductMetaInput('repo/default/revision', 'text:unified_php');
    $repo__default__revision->input_name = 'reporevision';
    $repo__default__revision->display_name = 'Repository Revision';
    $repo__default__revision->description = 'Repository Revision';
    $em->persist($repo__default__revision);
    // END  Deployment Inputs for Application Server

    // START Deployment Inputs for DB Server
    $db__backup__lineage = new InputProductMetaInput('db/backup/lineage', 'text:changeme');
    $db__backup__lineage->input_name = 'dblineage';
    $db__backup__lineage->display_name = 'Database Backup Lineage';
    $db__backup__lineage->description = 'Database Backup Lineage';
    $em->persist($db__backup__lineage);

    $sys_dns__choice = new InputProductMetaInput('sys_dns/choice', 'text:DNSMadeEasy');
    $sys_dns__choice->input_name = 'sysdnschoice';
    $sys_dns__choice->display_name = 'DNS Provider';
    $sys_dns__choice->description = 'DNS Provider';
    $em->persist($sys_dns__choice);

    $sys_dns__password = new InputProductMetaInput('sys_dns/password', 'text:password');
    $sys_dns__password->input_name = 'sysdnspassword';
    $sys_dns__password->display_name = 'DNS Password';
    $sys_dns__password->description = 'DNS Password';
    $em->persist($sys_dns__password);

    $sys_dns__user = new InputProductMetaInput('sys_dns/user', 'text:user');
    $sys_dns__user->input_name = 'sysdnschoice';
    $sys_dns__user->display_name = 'DNS User';
    $sys_dns__user->description = 'DNS User';
    $em->persist($sys_dns__user);
    // END Deployment Inputs for DB Server

    // START php-default Security Group
    $php_default_sg = new SecurityGroup();
    $php_default_sg->name = new TextProductMetaInput('php-default');
    $php_default_sg->description = new TextProductMetaInput('PHP 3-Tier');
    $php_default_sg->cloud_id = $cloud_metainput;

    $em->persist($php_default_sg);
    // END php-default Security Group

    // START php-lb Security Group
    $php_lb_sg = new SecurityGroup();
    $php_lb_sg->name = new TextProductMetaInput("php-lb");
    $php_lb_sg->description = new TextProductMetaInput("PHP 3-Tier");
    $php_lb_sg->cloud_id = $cloud_metainput;

    $em->persist($php_lb_sg);
    // END php-lb Security Group

    // START php-app Security Group
    $php_app_sg = new SecurityGroup();
    $php_app_sg->name = new TextProductMetaInput('php-app');
    $php_app_sg->description = new TextProductMetaInput('PHP 3-Tier');
    $php_app_sg->cloud_id = $cloud_metainput;

    $em->persist($php_app_sg);
    // END php-app Security Group

    // START php-mysql Security Group
    $php_mysql_sg = new SecurityGroup();
    $php_mysql_sg->name = new TextProductMetaInput("php-mysql");
    $php_mysql_sg->description = new TextProductMetaInput("PHP 3-Tier");
    $php_mysql_sg->cloud_id = $cloud_metainput;

    $em->persist($php_mysql_sg);
    // END php-mysql Security Group


    // START Add security group rules
    $idx = 0;

    $php_default_sg->rules[$idx] = new SecurityGroupRule();
    $php_default_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
    $php_default_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(22);
    $php_default_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(22);
    $php_default_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

    $em->persist($php_default_sg);

    $idx = 0;

    $php_lb_sg->rules[$idx] = new SecurityGroupRule();
    $php_lb_sg->rules[$idx]->ingress_cidr_ips = new TextProductMetaInput("0.0.0.0/0");
    $php_lb_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(80);
    $php_lb_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(80);
    $php_lb_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
    $idx++;

    $em->persist($php_lb_sg);

    $idx = 0;

    $php_app_sg->rules[$idx] = new SecurityGroupRule();
    $php_app_sg->rules[$idx]->ingress_group = $php_lb_sg;
    $php_app_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(8000);
    $php_app_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(8000);
    $php_app_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');

    $em->persist($php_app_sg);

    $idx = 0;

    $php_mysql_sg->rules[$idx] = new SecurityGroupRule();
    $php_mysql_sg->rules[$idx]->ingress_group = $php_app_sg;
    $php_mysql_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(3306);
    $php_mysql_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(3306);
    $php_mysql_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
    $idx++;

    $php_mysql_sg->rules[$idx] = new SecurityGroupRule();
    $php_mysql_sg->rules[$idx]->ingress_group =  $php_mysql_sg;
    $php_mysql_sg->rules[$idx]->ingress_from_port = new NumberProductMetaInput(3306);
    $php_mysql_sg->rules[$idx]->ingress_to_port = new NumberProductMetaInput(3306);
    $php_mysql_sg->rules[$idx]->ingress_protocol = new TextProductMetaInput('tcp');
    $idx++;

    $em->persist($php_mysql_sg);
    // END Add security group rules

    // START app_server 1
    $app_server_st = new ServerTemplate();
    $app_server_st->version = new NumberProductMetaInput(153);
    $app_server_st->nickname = new TextProductMetaInput('PHP App Server (v13.2.1)');
    $app_server_st->publication_id = new TextProductMetaInput('46547');

    $app_server = new Server();
    $app_server->cloud_id = $cloud_metainput;
    $app_server->count = new NumberProductMetaInput(1);
    $app_server->security_groups = array($php_default_sg, $php_app_sg);
    $app_server->server_template = $app_server_st;
    $app_server->nickname = new TextProductMetaInput("App1");

    $em->persist($app_server);
    // END app_server 1

    // START php_db server(s)
    $php_db_st = new ServerTemplate();
    $php_db_st->version = new NumberProductMetaInput(102);
    $php_db_st->nickname = new TextProductMetaInput('Database Manager for MySQL 5.5 (v13.2.1)');
    $php_db_st->publication_id = new TextProductMetaInput('46554');

    $php_db = new Server();
    $php_db->cloud_id = $cloud_metainput;
    $php_db->count = new NumberProductMetaInput(2);
    $php_db->security_groups = array($php_default_sg, $php_mysql_sg);
    $php_db->server_template = $php_db_st;
    // This is really just a prefix, it'll get an index numeral appended to it.
    $php_db->nickname = new TextProductMetaInput("DB");

    $em->persist($php_db);
    // END php_db server(s)

    // START php_lb server(s)
    $php_lb_st = new ServerTemplate();
    $php_lb_st->version = new NumberProductMetaInput(136);
    $php_lb_st->nickname = new TextProductMetaInput('Load Balancer with HAProxy (v13.2.1)');
    $php_lb_st->publication_id = new TextProductMetaInput('46546');

    $php_lb = new Server();
    $php_lb->cloud_id = $cloud_metainput;
    $php_lb->count = new NumberProductMetaInput(2);
    $php_lb->security_groups = array($php_default_sg, $php_lb_sg);
    $php_lb->server_template = $php_lb_st;
    // This is really just a prefix, it'll get an index numeral appended to it.
    $php_lb->nickname = new TextProductMetaInput("LB");

    $em->persist($php_lb);
    // END php_lb server(s)

    // START App Server Array
    $php_server_ary = new ServerArray();
    $php_server_ary->cloud_id = $cloud_metainput;
    $php_server_ary->min_count = new NumberProductMetaInput(1);
    $php_server_ary->max_count = new NumberProductMetaInput(10);
    $php_server_ary->type = new TextProductMetaInput("alert");
    $php_server_ary->tag = $text_metainput;
    $php_server_ary->security_groups = array($php_default_sg, $php_app_sg);
    $php_server_ary->server_template = $app_server_st;
    $php_server_ary->nickname = new TextProductMetaInput("PHPArray");

    $em->persist($php_server_ary);
    // END App Server Array

    // START CPU Scale Up Alert
    $cpu_scale_up_alert = new AlertSpec();
    $cpu_scale_up_alert->name = new TextProductMetaInput('CPU Scale Up');
    $cpu_scale_up_alert->file = new TextProductMetaInput('cpu-0/cpu-idle');
    $cpu_scale_up_alert->variable = new TextProductMetaInput('value');
    $cpu_scale_up_alert->cond = new TextProductMetaInput('<');
    $cpu_scale_up_alert->threshold = new TextProductMetaInput('60');
    $cpu_scale_up_alert->duration = new NumberProductMetaInput(2);
    $cpu_scale_up_alert->subjects = array($app_server,$php_server_ary);
    $cpu_scale_up_alert->action = new TextProductMetaInput('vote');
    $cpu_scale_up_alert->vote_tag = $text_metainput;
    $cpu_scale_up_alert->vote_type = new TextProductMetaInput('grow');

    $em->persist($cpu_scale_up_alert);
    // END CPU Scale Up Alert

    // START CPU Scale Down Alert
    $cpu_scale_down_alert = new AlertSpec();
    $cpu_scale_down_alert->name = new TextProductMetaInput('CPU Scale Down');
    $cpu_scale_down_alert->file = new TextProductMetaInput('cpu-0/cpu-idle');
    $cpu_scale_down_alert->variable = new TextProductMetaInput('value');
    $cpu_scale_down_alert->cond = new TextProductMetaInput('>');
    $cpu_scale_down_alert->threshold = new TextProductMetaInput('80');
    $cpu_scale_down_alert->duration = new NumberProductMetaInput(2);
    $cpu_scale_down_alert->subjects = array($app_server,$php_server_ary);
    $cpu_scale_down_alert->action = new TextProductMetaInput('vote');
    $cpu_scale_down_alert->vote_tag = $text_metainput;
    $cpu_scale_down_alert->vote_type = new TextProductMetaInput('shrink');

    $em->persist($cpu_scale_down_alert);
    // END CPU Scale Down Alert

    $product->name = "PHP 3-Tier";
    $product->icon_filename = "php.png";
    $product->security_groups = array($php_app_sg, $php_default_sg, $php_lb_sg, $php_mysql_sg);
    $product->servers = array($app_server, $php_db, $php_lb);
    $product->arrays = array($php_server_ary);
    $product->alerts = array($cpu_scale_up_alert, $cpu_scale_down_alert);
    $product->meta_inputs = array($cloud_metainput, $text_metainput, $deployment_name);
    $product->parameters = array(
      $app__database_name,
      $db__dns__master__fqdn,
      $db__backup__lineage,
      $db__provider_type,
      $repo__default__repository,
      $repo__default__revision,
      $sys_dns__choice,
      $sys_dns__password,
      $sys_dns__user
    );
    $product->launch_servers = false;

    $em->persist($product);
    $em->flush();
  }

  public static function fullJson() {
    return <<<EOF
{
  "name":"PHP 3-Tier",
  "icon_filename":"php.png",
  "security_groups":{
    "3":{
      "name":"php-app",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_group":{
            "rel":"security_groups",
            "id":2
          },
          "ingress_from_port":8000,
          "ingress_to_port":8000,
          "ingress_owner":"8167-8398-8377"
        }
      ],
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      }
    },
    "1":{
      "name":"php-default",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_cidr_ips":"0.0.0.0\/0",
          "ingress_from_port":22,
          "ingress_to_port":22
        }
      ],
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      }
    },
    "2":{
      "name":"php-lb",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_cidr_ips":"0.0.0.0\/0",
          "ingress_from_port":80,
          "ingress_to_port":80
        }
      ],
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      }
    },
    "4":{
      "name":"php-mysql",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_group":{
            "rel":"security_groups",
            "id":3
          },
          "ingress_from_port":3306,
          "ingress_to_port":3306,
          "ingress_owner":"8167-8398-8377"
        },
        {
          "ingress_protocol":"tcp",
          "ingress_group":{
            "rel":"security_groups",
            "id":4
          },
          "ingress_from_port":3306,
          "ingress_to_port":3306,
          "ingress_owner":"8167-8398-8377"
        }
      ],
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      }
    }
  },
  "servers":{
    "4":{
      "nickname":"App1",
      "server_template":{
        "rel":"server_templates",
        "id":1
      },
      "count":1,
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      },
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":3
        }
      ]
    },
    "5":{
      "nickname":"DB",
      "server_template":{
        "rel":"server_templates",
        "id":2
      },
      "count":2,
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      },
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":4
        }
      ]
    },
    "6":{
      "nickname":"LB",
      "server_template":{
        "rel":"server_templates",
        "id":3
      },
      "count":2,
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      },
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":2
        }
      ]
    }
  },
  "arrays":{
    "7":{
      "nickname":"PHPArray",
      "server_template":{
        "rel":"server_templates",
        "id":1
      },
      "min_count":1,
      "max_count":10,
      "cloud_id":{
        "rel":"meta_inputs",
        "id":72
      },
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":3
        }
      ],
      "type":"alert",
      "tag":{
        "rel":"meta_inputs",
        "id":30
      }
    }
  },
  "alerts":{
    "1":{
      "name":"CPU Scale Up",
      "file":"cpu-0\/cpu-idle",
      "variable":"value",
      "cond":"<",
      "threshold":"60",
      "duration":2,
      "subjects":[
        {
          "rel":"servers",
          "id":4
        },
        {
          "rel":"arrays",
          "id":7
        }
      ],
      "action":"vote",
      "vote_tag":{
        "rel":"meta_inputs",
        "id":30
      },
      "vote_type":"grow"
    },
    "2":{
      "name":"CPU Scale Down",
      "file":"cpu-0\/cpu-idle",
      "variable":"value",
      "cond":">",
      "threshold":"80",
      "duration":2,
      "subjects":[
        {
          "rel":"servers",
          "id":4
        },
        {
          "rel":"arrays",
          "id":7
        }
      ],
      "action":"vote",
      "vote_tag":{
        "rel":"meta_inputs",
        "id":30
      },
      "vote_type":"shrink"
    }
  },
  "meta_inputs":{
    "72":{
      "type":"cloud",
      "extra":{
        "default_value":1,
        "input_name":"cloud",
        "display_name":"Cloud",
        "description":"The cloud to create the 3-Tier in"
      },
      "value":1
    },
    "30":{
      "type":"text",
      "extra":{
        "input_name":"array_tag",
        "display_name":"Array Vote Tag",
        "description":"A tag used to identify the autoscaling array",
        "default_value":"phparray"
      },
      "value":"phparray"
    },
    "31":{
      "type":"text",
      "extra":{
        "input_name":"deployment_name",
        "display_name":"Deployment Name",
        "description":"The name of the deployment which will be created in RightScale",
        "default_value":"PHP 3-Tier"
      },
      "value":"PHP 3-Tier"
    }
  },
  "launch_servers":false,
  "parameters":[
    {
      "name":"app\/database_name",
      "value":"text:dbschema",
      "extra":{
        "rs_input_name":"app\/database_name",
        "input_name":"dbschema",
        "display_name":"Database Schema Name",
        "description":"Database Schema Name",
        "default_value":"text:dbschema"
      }
    },
    {
      "name":"db\/dns\/master\/fqdn",
      "value":"text:localhost",
      "extra":{
        "rs_input_name":"db\/dns\/master\/fqdn",
        "input_name":"dbfqdn",
        "display_name":"Database FQDN",
        "description":"Database FQDN",
        "default_value":"text:localhost"
      }
    },
    {
      "name":"db\/backup\/lineage",
      "value":"text:changeme",
      "extra":{
        "rs_input_name":"db\/backup\/lineage",
        "input_name":"dblineage",
        "display_name":"Database Backup Lineage",
        "description":"Database Backup Lineage",
        "default_value":"text:changeme"
      }
    },
    {
      "name":"db\/provider_type",
      "value":"text:db_mysql_5.5",
      "extra":{
        "rs_input_name":"db\/provider_type",
        "input_name":"dbtype",
        "display_name":"Database Type",
        "description":"Database Type",
        "default_value":"text:db_mysql_5.5"
      }
    },
    {
      "name":"repo\/default\/repository",
      "value":"text:git:\/\/github.com\/rightscale\/examples.git",
      "extra":{
        "rs_input_name":"repo\/default\/repository",
        "input_name":"reporepository",
        "display_name":"Repository URL",
        "description":"Repository URL",
        "default_value":"text:git:\/\/github.com\/rightscale\/examples.git"
      }
    },
    {
      "name":"repo\/default\/revision",
      "value":"text:unified_php",
      "extra":{
        "rs_input_name":"repo\/default\/revision",
        "input_name":"reporevision",
        "display_name":"Repository Revision",
        "description":"Repository Revision",
        "default_value":"text:unified_php"
      }
    },
    {
      "name":"sys_dns\/choice",
      "value":"text:DNSMadeEasy",
      "extra":{
        "rs_input_name":"sys_dns\/choice",
        "input_name":"sysdnschoice",
        "display_name":"DNS Provider",
        "description":"DNS Provider",
        "default_value":"text:DNSMadeEasy"
      }
    },
    {
      "name":"sys_dns\/password",
      "value":"text:password",
      "extra":{
        "rs_input_name":"sys_dns\/password",
        "input_name":"sysdnspassword",
        "display_name":"DNS Password",
        "description":"DNS Password",
        "default_value":"text:password"
      }
    },
    {
      "name":"sys_dns\/user",
      "value":"text:user",
      "extra":{
        "rs_input_name":"sys_dns\/user",
        "input_name":"sysdnschoice",
        "display_name":"DNS User",
        "description":"DNS User",
        "default_value":"text:user"
      }
    }
  ],
  "server_templates":{
    "1":{
      "nickname":"PHP App Server (v13.2.1)",
      "version":153,
      "publication_id":"46547"
    },
    "2":{
      "nickname":"Database Manager for MySQL 5.5 (v13.2.1)",
      "version":102,
      "publication_id":"46554"
    },
    "3":{
      "nickname":"Load Balancer with HAProxy (v13.2.1)",
      "version":136,
      "publication_id":"46546"
    }
  }
}
EOF;
  }

  public static function staticJson() {
    return <<<EOF
{
  "name":"PHP 3-Tier",
  "icon_filename":"php.png",
  "security_groups":{
    "3":{
      "name":"php-app",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_group":{
            "rel":"security_groups",
            "id":2
          },
          "ingress_from_port":8000,
          "ingress_to_port":8000,
          "ingress_owner":"8167-8398-8377"
        }
      ],
      "cloud_id":1
    },
    "1":{
      "name":"php-default",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_cidr_ips":"0.0.0.0\/0",
          "ingress_from_port":22,
          "ingress_to_port":22
        }
      ],
      "cloud_id":1
    },
    "2":{
      "name":"php-lb",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_cidr_ips":"0.0.0.0\/0",
          "ingress_from_port":80,
          "ingress_to_port":80
        }
      ],
      "cloud_id":1
    },
    "4":{
      "name":"php-mysql",
      "description":"PHP 3-Tier",
      "rules":[
        {
          "ingress_protocol":"tcp",
          "ingress_group":{
            "rel":"security_groups",
            "id":3
          },
          "ingress_from_port":3306,
          "ingress_to_port":3306,
          "ingress_owner":"8167-8398-8377"
        },
        {
          "ingress_protocol":"tcp",
          "ingress_group":{
            "rel":"security_groups",
            "id":4
          },
          "ingress_from_port":3306,
          "ingress_to_port":3306,
          "ingress_owner":"8167-8398-8377"
        }
      ],
      "cloud_id":1
    }
  },
  "servers":{
    "4":{
      "nickname":"App1",
      "server_template":{
        "rel":"server_templates",
        "id":1
      },
      "count":1,
      "cloud_id":1,
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":3
        }
      ]
    },
    "5":{
      "nickname":"DB",
      "server_template":{
        "rel":"server_templates",
        "id":2
      },
      "count":2,
      "cloud_id":1,
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":4
        }
      ]
    },
    "6":{
      "nickname":"LB",
      "server_template":{
        "rel":"server_templates",
        "id":3
      },
      "count":2,
      "cloud_id":1,
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":2
        }
      ]
    }
  },
  "arrays":{
    "7":{
      "nickname":"PHPArray",
      "server_template":{
        "rel":"server_templates",
        "id":1
      },
      "min_count":1,
      "max_count":10,
      "cloud_id":1,
      "security_groups":[
        {
          "rel":"security_groups",
          "id":1
        },
        {
          "rel":"security_groups",
          "id":3
        }
      ],
      "type":"alert",
      "tag":"phparray"
    }
  },
  "alerts":{
    "1":{
      "name":"CPU Scale Up",
      "file":"cpu-0\/cpu-idle",
      "variable":"value",
      "cond":"<",
      "threshold":"60",
      "duration":2,
      "subjects":[
        {
          "rel":"servers",
          "id":4
        },
        {
          "rel":"arrays",
          "id":7
        }
      ],
      "action":"vote",
      "vote_tag":"phparray",
      "vote_type":"grow"
    },
    "2":{
      "name":"CPU Scale Down",
      "file":"cpu-0\/cpu-idle",
      "variable":"value",
      "cond":">",
      "threshold":"80",
      "duration":2,
      "subjects":[
        {
          "rel":"servers",
          "id":4
        },
        {
          "rel":"arrays",
          "id":7
        }
      ],
      "action":"vote",
      "vote_tag":"phparray",
      "vote_type":"shrink"
    }
  },
  "launch_servers":false,
  "parameters":[
    {
      "name":"app\/database_name",
      "value":"text:dbschema",
      "extra":{
        "rs_input_name":"app\/database_name",
        "input_name":"dbschema",
        "display_name":"Database Schema Name",
        "description":"Database Schema Name",
        "default_value":"text:dbschema"
      }
    },
    {
      "name":"db\/dns\/master\/fqdn",
      "value":"text:localhost",
      "extra":{
        "rs_input_name":"db\/dns\/master\/fqdn",
        "input_name":"dbfqdn",
        "display_name":"Database FQDN",
        "description":"Database FQDN",
        "default_value":"text:localhost"
      }
    },
    {
      "name":"db\/backup\/lineage",
      "value":"text:changeme",
      "extra":{
        "rs_input_name":"db\/backup\/lineage",
        "input_name":"dblineage",
        "display_name":"Database Backup Lineage",
        "description":"Database Backup Lineage",
        "default_value":"text:changeme"
      }
    },
    {
      "name":"db\/provider_type",
      "value":"text:db_mysql_5.5",
      "extra":{
        "rs_input_name":"db\/provider_type",
        "input_name":"dbtype",
        "display_name":"Database Type",
        "description":"Database Type",
        "default_value":"text:db_mysql_5.5"
      }
    },
    {
      "name":"repo\/default\/repository",
      "value":"text:git:\/\/github.com\/rightscale\/examples.git",
      "extra":{
        "rs_input_name":"repo\/default\/repository",
        "input_name":"reporepository",
        "display_name":"Repository URL",
        "description":"Repository URL",
        "default_value":"text:git:\/\/github.com\/rightscale\/examples.git"
      }
    },
    {
      "name":"repo\/default\/revision",
      "value":"text:unified_php",
      "extra":{
        "rs_input_name":"repo\/default\/revision",
        "input_name":"reporevision",
        "display_name":"Repository Revision",
        "description":"Repository Revision",
        "default_value":"text:unified_php"
      }
    },
    {
      "name":"sys_dns\/choice",
      "value":"text:DNSMadeEasy",
      "extra":{
        "rs_input_name":"sys_dns\/choice",
        "input_name":"sysdnschoice",
        "display_name":"DNS Provider",
        "description":"DNS Provider",
        "default_value":"text:DNSMadeEasy"
      }
    },
    {
      "name":"sys_dns\/password",
      "value":"text:password",
      "extra":{
        "rs_input_name":"sys_dns\/password",
        "input_name":"sysdnspassword",
        "display_name":"DNS Password",
        "description":"DNS Password",
        "default_value":"text:password"
      }
    },
    {
      "name":"sys_dns\/user",
      "value":"text:user",
      "extra":{
        "rs_input_name":"sys_dns\/user",
        "input_name":"sysdnschoice",
        "display_name":"DNS User",
        "description":"DNS User",
        "default_value":"text:user"
      }
    }
  ],
  "server_templates":{
    "1":{
      "nickname":"PHP App Server (v13.2.1)",
      "version":153,
      "publication_id":"46547"
    },
    "2":{
      "nickname":"Database Manager for MySQL 5.5 (v13.2.1)",
      "version":102,
      "publication_id":"46554"
    },
    "3":{
      "nickname":"Load Balancer with HAProxy (v13.2.1)",
      "version":136,
      "publication_id":"46546"
    }
  }
}
EOF;
  }

}