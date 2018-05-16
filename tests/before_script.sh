#!/bin/bash

#
# Before script for Travis CI
#

# config composer
if [ "$TRAVIS_SECURE_ENV_VARS" = "true" ]; then
  mkdir ~/.composer -p
  touch ~/.composer/composer.json
  composer config -g github-oauth.github.com $GH_OAUTH
fi

mysql -u root -e 'create database $DBNAME;'
git clone --depth=1 $GLPI_SOURCE -b $GLPI_BRANCH ../glpi && cd ../glpi
composer install --no-dev --no-interaction
if [ -e scripts/cliinstall.php ] ; then php scripts/cliinstall.php --db=glpitest --user=root --tests ; fi
if [ -e tools/cliinstall.php ] ; then php tools/cliinstall.php --db=glpitest --user=root --tests ; fi
mkdir plugins/fusioninventory && git clone --depth=1 $FI_SOURCE -b $FI_BRANCH plugins/fusioninventory
mkdir plugins/flyvemdm && git clone --depth=1 $FLYVEMDM_SOURCE -b $FLYVEMDM_BRANCH plugins/flyvemdm
cd plugins/flyvemdm && composer install --no-dev && cd ../..
IFS=/ read -a repo <<< $TRAVIS_REPO_SLUG
mv ../${repo[1]} plugins/flyvemdmdemo
cd plugins/flyvemdmdemo && composer install -o