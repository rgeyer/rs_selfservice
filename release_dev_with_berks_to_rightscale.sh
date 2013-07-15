#!/bin/sh

berks_to_rightscale release rsss dev --container=chef-blueprints --provider=aws --force --provider-options region=us-west-2 --only=servertemplate