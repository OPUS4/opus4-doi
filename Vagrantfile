# -*- mode: ruby -*-
# vi: set ft=ruby :

$software = <<SCRIPT
# Downgrade to PHP 8.1
apt-add-repository -y ppa:ondrej/php
apt-get -yq update
apt-get -yq install php8.1

# Install required PHP packages
apt-get -yq install php8.1-dom
apt-get -yq install php8.1-curl
apt-get -yq install php8.1-mbstring
apt-get -yq install php8.1-zip

SCRIPT

$composer = <<SCRIPT
/vagrant/bin/install-composer.sh
cd /vagrant
bin/composer update
SCRIPT

$environment = <<SCRIPT
if ! grep "cd /vagrant" /home/vagrant/.profile > /dev/null; then
  echo "cd /vagrant" >> /home/vagrant/.profile
fi
if ! grep "PATH=/vagrant/bin" /home/vagrant/.bashrc > /dev/null; then
  echo "export PATH=/vagrant/bin:$PATH" >> /home/vagrant/.bashrc
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
  config.vm.box = "bento/ubuntu-22.04"

  config.vm.provision 'shell', inline: $software
  config.vm.provision 'shell', privileged: false, inline: $composer
  config.vm.provision 'shell', inline: $environment
  config.vm.provision "Help", type: "shell", privileged: false, inline: $help
end
