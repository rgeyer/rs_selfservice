maintainer       "Ryan J. Geyer"
maintainer_email "me@ryangeyer.com"
license          "All rights reserved"
description      "Installs/Configures rsss"
long_description "Installs/Configures rsss"
version          "0.0.1"

supports "centos"

depends "rightscale"

recipe "rsss::setup_rsss", "Assuming that the code has already been downloaded and that an apache vhost is setup, this installs and configures dependencies for the RSSS"

attribute "rsss/install_dir",
  :display_name => "RSSS Install Directory",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]

attribute "rsss/rightscale_email",
  :display_name => "RSSS RightScale Email",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]

attribute "rsss/rightscale_password",
  :display_name => "RSSS RightScale Password",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]

attribute "rsss/rightscale_acct_num",
  :display_name => "RSSS RightScale Account Number",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]

attribute "rsss/aws_access_key",
  :display_name => "RSSS AWS Access Key",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]

attribute "rsss/aws_secret_access_key",
  :display_name => "RSSS AWS Secret Access Key",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]

attribute "rsss/datapipe_owner",
  :display_name => "RSSS Datapipe Owner Name",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]

attribute "rsss/fqdn",
  :display_name => "RSSS Fully Qualified Domainname",
  :required => "required",
  :recipes => ["rsss::setup_rsss"]