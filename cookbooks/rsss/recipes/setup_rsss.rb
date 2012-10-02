#
# Cookbook Name:: rsss
# Recipe:: setup_rsss
#
# Copyright 2012, Ryan J. Geyer <me@ryangeyer.com>
#
# All rights reserved - Do Not Redistribute
#

smarty_version = '3.1.11'
smarty_tmp_path = ::File.join(Chef::Config[:file_cache_path], "Smarty-#{smarty_version}.tar.gz")
smarty_dest_path = "/usr/share/php/Smarty"

composer_path = ::File.join(Chef::Config[:file_cache_path], "composer.phar")

bash "Install Zend Framework Prerequisite" do
  code <<-EOF
pear channel-discover zend.googlecode.com/svn
pear install zend/zend-1.11.12
  EOF
end

unless ::File.exists?(::File.join(smarty_dest_path, "lib"))
  directory smarty_dest_path do
    recursive true
  end

  remote_file smarty_tmp_path do
    source "http://www.smarty.net/files/Smarty-#{smarty_version}.tar.gz"
  end

  execute "Extract Smarty lib Download" do
    command "tar -zxf #{smarty_tmp_path} -C #{smarty_dest_path} --strip-components 1"
  end

end

directory ::File.join(node.rsss.install_dir, 'logs') do
  owner 'apache'
  group 'apache'
end

file ::File.join(node.rsss.install_dir, 'logs', 'application.log') do
  owner 'apache'
  group 'apache'
  action [:create, :touch]
end

# Create or re-create virtualhost (apache) or config for nginx
# Point doc root to /root/of/app/public
# Set AllowOverride All
# SetEnv APPLICATION_ENV "production|development"

# Install and run composer.php to get dependencies
execute "Download composer.phar" do
  cwd Chef::Config[:file_cache_path]
  command "curl -s http://getcomposer.org/installer | php"
  creates composer_path
end

execute "Get rsss vendor libraries" do
  cwd node.rsss.install_dir
  command "php composer.phar install"
  creates ::File.join(node.rsss.install_dir, 'vendor')
end

template ::File.join(node.rsss.install_dir, 'application', 'configs', 'db.ini') do
  local true
  source ::File.join(node.rsss.install_dir, 'application', 'configs', 'db.ini.erb')
end

template ::File.join(node.rsss.install_dir, 'application', 'configs', 'cloud_creds.ini') do
  local true
  source ::File.join(node.rsss.install_dir, 'application', 'configs', 'cloud_creds.ini.erb')
  variables(
    :rs_email => node.rsss.rightscale_email,
    :rs_pass => node.rsss.rightscale_password,
    :rs_acct_num => node.rsss.rightscale_acct_num,
    :aws_access_key => node.rsss.aws_access_key,
    :aws_secret_access_key => node.rsss.aws_secret_access_key,
    :datapipe_owner => node.rsss.datapipe_owner
  )
end

template ::File.join(node.rsss.install_dir, 'application', 'configs', 'rsss.ini') do
  local true
  source ::File.join(node.rsss.install_dir, 'application', 'configs', 'rsss.ini.erb')
  variables :hostname => node.rsss.fqdn
end

# Create empty model directories
directory ::File.join(node.rsss.install_dir, 'application', 'modules', 'admin', 'models')

directory ::File.join(node.rsss.install_dir, 'application', 'modules', 'default', 'models')

# Create a php.d file to set the timezone