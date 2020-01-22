# -*- mode: ruby -*-
# vi: set ft=ruby :

Vagrant.configure("2") do |config|
  config.vm.box = "debian/stretch64"
  config.vm.provision "shell", path: "Vagrant/provision.sh"
  config.vm.define :basilbox
  config.vm.synced_folder ".", "/vagrant", type: "rsync"
end
