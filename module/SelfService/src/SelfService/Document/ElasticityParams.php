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

namespace SelfService\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as ODM;

/**
 * RJG Can Depend
 * @ODM\EmbeddedDocument
 * @author Ryan J. Geyer <me@ryangeyer.com>
 *
 * TODO: Should I auto instantiate the embedone documents recursively? Would
 * make initializing a new one easier methinks.  I.E. $elasticity_params->queue_specific_params->item_age->algorithm
 */
class ElasticityParams extends AbstractResource {

  public $resource_type = "elasticity_params";

  /**
   * @ODM\EmbedOne(targetDocument="ElasticityParamsBounds")
   * @var \SelfService\Document\ElasticityParamsBounds
   */
  public $bounds;

  /**
   * @ODM\EmbedOne(targetDocument="ElasticityParamsPacing")
   * @var \SelfService\Document\ElasticityParamsPacing
   */
  public $pacing;

  /**
   * @ODM\EmbedOne(targetDocument="ElasticityParamsAlertSpecificParams")
   * @var \SelfService\Document\ElasticityParamsAlertSpecificParams
   */
  public $alert_specific_params;

  /**
   * @ODM\EmbedOne(targetDocument="ElasticityParamsQueueSpecificParams")
   * @var \SelfService\Document\ElasticityParamsQueueSpecificParams
   */
  public $queue_specific_params;

  /**
   * @ODM\EmbedOne(targetDocument="ElasticityParamsSchedule")
   * @var \SelfService\Document\ElasticityParamsSchedule
   */
  public $schedule;
}