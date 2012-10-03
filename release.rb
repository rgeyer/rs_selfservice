#!/usr/bin/env ruby
#
# Copyright (c) 2012 Ryan J. Geyer
#
# Permission is hereby granted, free of charge, to any person obtaining
# a copy of this software and associated documentation files (the
# "Software"), to deal in the Software without restriction, including
# without limitation the rights to use, copy, modify, merge, publish,
# distribute, sublicense, and/or sell copies of the Software, and to
# permit persons to whom the Software is furnished to do so, subject to
# the following conditions:
#
# The above copyright notice and this permission notice shall be
# included in all copies or substantial portions of the Software.
#
# THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
# EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
# MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
# NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
# LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
# OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
# WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

require 'trollop'
require 'github_api'
require 'logger'
require 'fileutils'
require 'yaml'

log = Logger.new(STDOUT)

opts = Trollop::options do
  banner = "Simple tool to take a specified directory, and upload it as a github 'download' for a specified repo"

  opt :force, "Should the release tarball be overwritten if it exists?", :type => :boolean, :default => false
  opt :release_name, "The prefix for the release tarball.  I.E. Specifying foobar will result in a download filename of foobar.tar.gz", :type => :string, :required => true
  opt :config_file, "A YAML file containing github config information, see .release_config.example for details.", :type => :string, :default => ".release_config"
end

release_tarball = "#{opts[:release_name]}.tar.gz"

config_yml = YAML.load_file opts[:config_file]

# Smash together the options from the config file, the params, and some extra stuff.

final_cfg = opts.
  merge(config_yml).
  merge({
  :release_name => opts[:release_name],
  :release_tarball => release_tarball
})

# Allow a configuration specified with cl params to be persisted in a file?
#File.open('.release_config', 'w') do |file|
#  file.write(config_hash.to_yaml)
#end

log.info("Opening a connection to the github API...")
gh_api = Github::Repos.new basic_auth: "#{final_cfg[:github][:username]}:#{final_cfg[:github][:password]}"

# Check if the release already exists.  Delete or warn accordingly
log.info("Checking to see if a release download of #{final_cfg[:release_name]} already exists...")
gh_api.downloads.list(final_cfg[:github][:organization_name], final_cfg[:github][:repo_name]) do |download|
  if download.name == final_cfg[:release_tarball]
    raise "A download with the name #{final_cfg[:release_tarball]} already exists.  Specify --force if you really want to overwrite it." unless final_cfg[:force]
    log.warn("Found an existing download with the name #{final_cfg[:release_tarball]}, --force was specified so it is being deleted...")
    gh_api.downloads.delete(final_cfg[:github][:organization_name], final_cfg[:github][:repo_name], download.id)
  end
end

# Package things up
log.info("Creating a tarball of #{final_cfg[:source_dir]}...")
`tar -zcvf #{final_cfg[:release_tarball]} --exclude=.git #{final_cfg[:source_dir]}`

# Upload that muthah!
log.info("Creating a new download for repository #{final_cfg[:github][:repo_name]}...")
create_resp = gh_api.downloads.create(
  final_cfg[:github][:organization_name],
  final_cfg[:github][:repo_name],
  {'name' => final_cfg[:release_tarball], 'size' => File.size(final_cfg[:release_tarball])}
)

log.info("Uploading...")
gh_api.downloads.upload(create_resp, final_cfg[:release_tarball])

# Clean up after ourselves
FileUtils.rm final_cfg[:release_tarball] if File.exist? final_cfg[:release_tarball]