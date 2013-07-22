Vagrant.configure("2") do |config|

  config.berkshelf.enabled = true

  config.vm.define :centos do |default_config|
    default_config.vm.hostname = "centos"

    default_config.vm.box = "ri_centos6.4_v13.4"
    default_config.vm.box_url = "https://s3.amazonaws.com/rgeyer/pub/ri_centos6.4_v13.4.box"

    default_config.vm.network :private_network, ip: "33.33.33.9"
    default_config.vm.network :forwarded_port, guest: 27017, host: 27017

    default_config.ssh.max_tries = 40
    default_config.ssh.timeout   = 120

    default_config.rightscaleshim.run_list_dir = "runlists/centos"
    default_config.rightscaleshim.shim_dir = "rightscaleshim/centos"
    default_config.vm.provision :chef_solo do |chef|
      chef.binary_env = "GEM_HOME=/opt/rightscale/sandbox/lib/ruby/gems/1.8"
      chef.binary_path = "/opt/rightscale/sandbox/bin"
    end
  end

  config.vm.define :ubuntu do |ubuntu_config|
    ubuntu_config.vm.hostname = "ubuntu"

    ubuntu_config.vm.box = "ri_ubuntu12.04_v5.8.8_vagrant"
    ubuntu_config.vm.box_url = "https://s3.amazonaws.com/rgeyer/pub/ri_ubuntu12.04_v5.8.8_vagrant.box"

    ubuntu_config.vm.network :private_network, ip: "33.33.33.10"

    ubuntu_config.ssh.max_tries = 40
    ubuntu_config.ssh.timeout   = 120

    ubuntu_config.rightscaleshim.run_list_dir = "runlists/ubuntu"
    ubuntu_config.rightscaleshim.shim_dir = "rightscaleshim/ubuntu"
    ubuntu_config.vm.provision :chef_solo do |chef|
    end
  end

  config.vm.define :testservices do |testservices_config|
    testservices_config.vm.hostname = "testservices"

    testservices_config.vm.box = "ri_centos6.3_v5.8.8"
    testservices_config.vm.box_url = "https://s3.amazonaws.com/rgeyer/pub/ri_centos6.3_v5.8.8_vagrant.box"

    testservices_config.vm.network :private_network, ip: "33.33.33.11"
    testservices_config.vm.network :forwarded_port, guest: 27017, host: 27017

    testservices_config.ssh.max_tries = 40
    testservices_config.ssh.timeout   = 120

    testservices_config.rightscaleshim.run_list_dir = "runlists/testservices"
    testservices_config.rightscaleshim.shim_dir = "rightscaleshim/testservices"
    testservices_config.vm.provision :chef_solo do |chef|
    end
  end
  
end
