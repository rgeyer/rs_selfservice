path_to_rss = "/path/to/rss_install"

# May have to switch to Pyrus or a standalone recipe for installation
`pear channel-discover zend.googlecode.com/svn`
`pear install zend/zend-1.11.11`

# Install Smarty to a shared lib dir and/or add config extension to PHP include.
# http://www.smarty.net/files/Smarty-3.1.7.tar.gz
# Currently just tossing it into the pear dir

directory ::File.join(path_to_rss, 'logs') do
  owner 'apache|http'
  group 'apache|http'
  action :create
end

file ::File.join(path_to_rss, 'logs', 'application.log') do
  owner 'apache|http'
  group 'apache|http'
  action :create|touch
end

# Create or re-create virtualhost (apache) or config for nginx
# Point doc root to /root/of/app/public
# Set AllowOverride All
# SetEnv APPLICATION_ENV "production|development"

# Recursively get submodules, cause apparently we don't.  Doctrine submodule of guzzle dir has SSL cert problems as well

template ::File.join(path_to_rss, 'application', 'configs', 'db.ini') do
  source "db.ini.erb"
end

template ::File.join(path_to_rss, 'application', 'configs', 'cloud_creds.ini') do
  source "cloud_creds.ini.erb"
end

# Create empty model directories
directory ::File.join(path_to_rss, 'application', 'modules', 'admin', 'models')

directory ::File.join(path_to_rss, 'application', 'modules', 'default', 'models')

# Create a php.d file to set the timezone