#!/usr/bin/env bash

EZ_HOME="$HOME/.ezlaunchpad"
URL="https://ezsystems.github.io/ezlaunchpad/ez.phar"
mkdir -p $EZ_HOME
cd $EZ_HOME

curl -sS -o ez.phar "$URL"
curl -sS -o ez.phar.pubkey "$URL.pubkey"

ln -sf $EZ_HOME/ez.phar $HOME/ez
chmod +x $HOME/ez

echo "You can now use eZ Launchpad by running: ~/ez"
echo ""
echo "- You may want to put ~/ez in you PATH"
echo "- You may want to creat an alias (in your .zshrc or .bashrc) alias ez='~/ez'"

~/ez

