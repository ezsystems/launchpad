#!/usr/bin/env bash

EZ_HOME="$HOME/.ezlaunchpad"
mkdir -p $EZ_HOME
cd $EZ_HOME

php -r "copy('https://ezsystems.github.io/launchpad/installer', 'installer');"
php installer
rm installer

ln -sf $EZ_HOME/ez.phar $HOME/ez
chmod +x $HOME/ez

echo "You can now use eZ Launchpad by running: ~/ez"
echo ""
echo "- You may want to put ~/ez in you PATH"
echo "- You may want to creat an alias (in your .zshrc or .bashrc) alias ez='~/ez'"

~/ez
