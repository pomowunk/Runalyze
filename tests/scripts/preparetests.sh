#!/bin/bash

DB_HOST="-h 127.0.0.1"
DB_USER="-urunalyze_test"

# base is https://github.com/Runalyze/Runalyze/blob/support/4.3.x/.travis.yml

# apt-get install --no-install-recommends mariadb-client

# before_script:
cp app/config/default_config.yml data/config.yml

# create/update database
mysql -uroot -p $DB_HOST -e \
'DROP DATABASE IF EXISTS runalyze_unittest; DROP DATABASE IF EXISTS runalyze_test;
 SET @@global.sql_mode = TRADITIONAL; CREATE DATABASE runalyze_unittest; CREATE DATABASE runalyze_test;
 CREATE USER IF NOT EXISTS runalyze_test@localhost; GRANT ALL PRIVILEGES ON runalyze_unittest.* TO runalyze_test@localhost; GRANT ALL PRIVILEGES ON runalyze_test.* to runalyze_test@localhost;'

php bin/console --env=test doctrine:schema:update --force --complete

mysql $DB_USER $DB_HOST runalyze_unittest < inc/install/structure.sql

