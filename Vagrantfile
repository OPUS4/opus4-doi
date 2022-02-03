# -*- mode: ruby -*-
# vi: set ft=ruby :

$software = <<SCRIPT
# Downgrade to PHP 7.1
apt-add-repository -y ppa:ondrej/php
apt-get -yq update
apt-get -yq install php7.1

# Install required PHP packages
apt-get -yq install php7.1-dom
apt-get -yq install php7.1-curl

# Install Composer for running tests
apt-get -yq install composer
SCRIPT

$composer = <<SCRIPT
cd /vagrant
composer update
SCRIPT

$environment = <<SCRIPT
if ! grep "cd /vagrant" /home/vagrant/.profile > /dev/null; then
  echo "cd /vagrant" >> /home/vagrant/.profile
fi
SCRIPT

$help = <<SCRIPT
echo "Use 'vagrant ssh' to log into VM and 'logout' to leave it."
echo "In VM use:"
echo "'composer test' for running tests"
echo "'composer update' to update dependencies"
echo "'composer cs-check' to check coding style"
echo "'composer cs-fix' to automatically fix basic style problems"
SCRIPT

Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-20.04"

  config.vm.provision 'shell', inline: $software
  config.vm.provision 'shell', privileged: false, inline: $composer
  config.vm.provision 'shell', inline: $environment
  config.vm.provision "Help", type: "shell", privileged: false, inline: $help
end
