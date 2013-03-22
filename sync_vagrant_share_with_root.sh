#!/bin/bash -e
rsync -avrc --exclude=logs/* --exclude=.git --exclude=sync_vagrant_share_with_root.sh  /vagrant/ /home/webapps/rsss/
