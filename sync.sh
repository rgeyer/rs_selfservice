#!/bin/sh

rsync -avrc /vagrant/module/ /mnt/storage/rsss/33.33.33.9/module/
rsync -avrc /vagrant/public/ /mnt/storage/rsss/33.33.33.9/public/
chown apache:apache -R /mnt/storage/rsss
