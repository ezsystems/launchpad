#!/usr/bin/env bash

# Unpack secrets; -C ensures they unpack *in* the .travis directory
tar xvf .travis/secrets.tar -C .travis

# Setup SSH agent:
eval "$(ssh-agent -s)" #start the ssh agent
chmod 600 .travis/ezlaunchpad-private.pem
ssh-add .travis/ezlaunchpad-private.pem

# Setup git defaults:
git config --global user.email "ezlaunchpad@ez.no"
git config --global user.name "eZ Launchpad"

# Add SSH-based remote to GitHub repo:
git remote add deploy git@github.com:ezsystems/ezlaunchpad.git
git fetch deploy

# Get box and build PHAR
curl -LSs https://box-project.github.io/box2/installer.php | php
chmod 755 box.phar

# Build the box
./box.phar build -vv

# Generate
sha1sum ez.phar > docs/ez.phar.version
mv ez.phar docs/ez.phar
git add docs/ez.phar docs/ez.phar.version

# Commit and push:
DATED_SUFFIX=`date +%Y-%m-%d-%H-%M-%S`
git commit -m "Deployment master on $DATED_SUFFIX"
git push deploy master
