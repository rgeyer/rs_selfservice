#
# Cookbook Name:: rsss
# Recipe:: setup_rsss
#
# Copyright 2012, Ryan J. Geyer <me@ryangeyer.com>
#
# All rights reserved - Do Not Redistribute
#

rightscale_marker :begin

smarty_version = '3.1.11'
smarty_tmp_path = ::File.join(Chef::Config[:file_cache_path], "Smarty-#{smarty_version}.tar.gz")
smarty_dest_path = "/usr/share/php/Smarty"

composer_path = ::File.join(Chef::Config[:file_cache_path], "composer.phar")

bash "Install Zend Framework Prerequisite" do
  code <<-EOF
pear channel-discover zend.googlecode.com/svn
pear install zend/zend-1.11.12
  EOF
  creates "/usr/share/pear/Zend"
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

# Install and run composer.php to get dependencies
execute "Download composer.phar" do
  cwd Chef::Config[:file_cache_path]
  command "curl -s http://getcomposer.org/installer | php"
  creates composer_path
end

execute "Get rsss vendor libraries" do
  cwd node.rsss.install_dir
  command "php #{composer_path} install"
  creates ::File.join(node.rsss.install_dir, 'vendor')
end

template ::File.join(node.rsss.install_dir, 'application', 'configs', 'db.ini') do
  local true
  source ::File.join(node.rsss.install_dir, 'application', 'configs', 'db.ini.erb')
  mode 0650
  group "apache"
end

template ::File.join(node.rsss.install_dir, 'application', 'configs', 'cloud_creds.ini') do
  local true
  source ::File.join(node.rsss.install_dir, 'application', 'configs', 'cloud_creds.ini.erb')
  mode 0650
  group "apache"
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
  mode 0650
  group "apache"
  variables(
    :hostname => node.rsss.fqdn
  )
end

# Create empty model directories
directory ::File.join(node.rsss.install_dir, 'application', 'modules', 'admin', 'models')

directory ::File.join(node.rsss.install_dir, 'application', 'modules', 'default', 'models')

directory ::File.join(node.rsss.install_dir, 'application', 'proxies') do
  mode 0774
  group "apache"
end

# Create a php.d file to set the timezone

# Create DB and zap schema
if `mysql -e 'show databases' | grep rs_selfservice`.empty?
  execute "Create Database Schema" do
    command "mysql -e 'create database rs_selfservice'"
  end

  execute "Zap Schema" do
    cwd ::File.join(node.rsss.install_dir, 'application', 'scripts')
    command './zap_schema.sh'
  end
end

# Hack up the vhost for AllowOverride and using /public
# Create or re-create virtualhost (apache) or config for nginx
# Point doc root to /root/of/app/public
# Set AllowOverride All
# SetEnv APPLICATION_ENV "production|development"

bash "Hack up the vhost" do
  code <<-EOF
sed -i 's/AllowOverride None/AllowOverride All/g' /etc/httpd/sites-available/rsss.conf
sed -i 's,/home/webapps/rsss\\(>\\?\\)$,/home/webapps/rsss/public\\1,g' /etc/httpd/sites-available/rsss.conf
/etc/init.d/httpd restart
  EOF
end

rightscale_marker :end