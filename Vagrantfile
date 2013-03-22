require 'berkshelf/vagrant'
require 'rs_vagrant_shim'

Vagrant::Config.run do |config|

  config.vm.define :centos do |default_config|
    default_config.berkshelf.berksfile_path = "Berksfile"

    default_config.vm.host_name = "centos"

    default_config.vm.box = "ri_centos6.3_v5.8.8"
    default_config.vm.box_url = "https://s3.amazonaws.com/rgeyer/pub/ri_centos6.3_v5.8.8_vagrant.box"

    default_config.vm.network :hostonly, "33.33.33.9"

    default_config.ssh.max_tries = 40
    default_config.ssh.timeout   = 120

    default_config.vm.provision Vagrant::RsVagrantShim::Provisioners::RsVagrantShim do |chef|
      chef.run_list_dir = "runlists/centos"
      chef.shim_dir = "rs_vagrant_shim/centos"
      #chef.data_bags_path = "~/Code/Chef/me/ryangeyer_com_org/data_bags"
    end
  end

  config.vm.define :ubuntu do |ubuntu_config|
    ubuntu_config.berkshelf.berksfile_path = "Berksfile"

    ubuntu_config.vm.host_name = "ubuntu"

    ubuntu_config.vm.box = "ri_ubuntu12.04_v5.8.8_vagrant"
    ubuntu_config.vm.box_url = "https://s3.amazonaws.com/rgeyer/pub/ri_ubuntu12.04_v5.8.8_vagrant.box"

    ubuntu_config.vm.network :hostonly, "33.33.33.10"

    ubuntu_config.ssh.max_tries = 40
    ubuntu_config.ssh.timeout   = 120

    ubuntu_config.vm.provision Vagrant::RsVagrantShim::Provisioners::RsVagrantShim do |chef|
      chef.run_list_dir = "runlists/ubuntu"
      chef.shim_dir = "rs_vagrant_shim/ubuntu"
      #chef.data_bags_path = "~/Code/Chef/me/ryangeyer_com_org/data_bags"
    end
  end
  
end