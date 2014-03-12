site :opscode

group :servertemplate do
  cookbook "rightscale",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/rightscale"

  cookbook "logging",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/logging"

  cookbook "logging_rsyslog",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/logging_rsyslog"

  cookbook "logging_syslog_ng",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/logging_syslog_ng"

  cookbook "block_device",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/block_device"

  cookbook "sys",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/sys"

  cookbook "sys_dns",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/sys_dns"

  cookbook "sys_ntp",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/sys_ntp"

  cookbook "sys_firewall",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/sys_firewall"

  cookbook "web_apache",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/web_apache"

  cookbook "app",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/app"

  cookbook "app_django",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/app_django"

  cookbook "app_jboss",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/app_jboss"

  cookbook "app_passenger",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/app_passenger"

  cookbook "app_php",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/app_php"

  cookbook "app_tomcat",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/app_tomcat"

  cookbook "memcached",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/memcached"

  cookbook "repo",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/repo"

  cookbook "repo_ftp",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/repo_ftp"

  cookbook "repo_git",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/repo_git"

  cookbook "repo_ros",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/repo_ros"

  cookbook "repo_rsync",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/repo_rsync"

  cookbook "repo_svn",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/repo_svn"

  cookbook "cloudmonitoring",
    git: "git://github.com/rightscale/rackspace_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cloudmonitoring"

  cookbook "driveclient",
    git: "git://github.com/rightscale/rackspace_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "driveclient"

  cookbook "db",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/db"

  cookbook "db_mysql",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/db_mysql"

  cookbook "db_postgres",
    git: "git://github.com/rightscale/rightscale_cookbooks.git",
    branch: "v13.5.0-LTS",
    rel: "cookbooks/db_postgres"

  cookbook "rsss",
    git: "git://github.com/rgeyer-rs-cookbooks/rsss.git"

  cookbook "apt", "~> 1.10.0"
end

group :vagrant_only do
  cookbook "yum"

  cookbook "rightscaleshim",
    git: "https://github.com/rgeyer-rs-cookbooks/rightscaleshim.git"

  cookbook "system",
    git: "git://github.com/xhost-cookbooks/system.git"

  cookbook "resolver"
end
