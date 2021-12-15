#!/bin/bash

# "vendor/bin/phpunit" not executable because the x permission is not set when the container is build from the zip file, so use the call via php 
# PHPUNIT=vendor/bin/phpunit
PHPUNIT="php vendor/phpunit/phpunit/phpunit"

# based on the combinations of https://github.com/Runalyze/Runalyze/blob/support/4.3.x/.travis.yml

# before_script:
cp app/config/default_config.yml data/config.yml

# tests
echo '----------------------------- import -----------------------------------------------'
$PHPUNIT --colors -c tests/config.xml --group "import" --exclude-group "dependsOnSRTM,dependsOnTimezoneDatabase"

#..E.FF..........................F......FEF.....................  63 / 109 ( 57%)
#........F....F.....................F...E..E...
#
#Time: 4.29 minutes, Memory: 98.00MB
#Tests: 109, Assertions: 5688, Errors: 4, Failures: 8.

echo '----------------------------- requiresKernel,requiresDoctrine,requiresClient -------'
$PHPUNIT --colors -c tests/config.xml --group "requiresKernel,requiresDoctrine,requiresClient" --exclude-group "dependsOnSRTM,dependsOnTimezoneDatabase"

#........................................................F........ 65 / 83 ( 78%)
#...............F..
#
#Time: 36.88 seconds, Memory: 131.00MB
#Tests: 83, Assertions: 305, Failures: 2.

echo '----------------------------- default,dependsOn,requiresSqlite ---------------------'
$PHPUNIT --colors -c tests/config.xml --group "default,dependsOn,requiresSqlite" --exclude-group "dependsOnSRTM,dependsOnTimezoneDatabase,import,requiresKernel,requiresDoctrine,requiresClient"

#................................................F........F...   61 / 1132 (  5%)
#.........E...........................................F.......  122 / 1132 ( 10%)
#....................................F........................  183 / 1132 ( 16%)
#.............................................................  244 / 1132 ( 21%)
#...........................................................F.  305 / 1132 ( 26%)
#FF...............E.....F..F..................................  366 / 1132 ( 32%)
#.............................................................  427 / 1132 ( 37%)
#.............................................................  488 / 1132 ( 43%)
#....FF.....F.................................................  549 / 1132 ( 48%)
#....................F........................................  610 / 1132 ( 53%)
#.............................EEEE............................  671 / 1132 ( 59%)
#.............................................................  732 / 1132 ( 64%)
#.............................................................  793 / 1132 ( 70%)
#........F.............................S......................  854 / 1132 ( 75%)
#.....................................F.......................  915 / 1132 ( 80%)
#.............................................................  976 / 1132 ( 86%)
#.............................F............................... 1037 / 1132 ( 91%)
#...........................................EEEEEEEEEEEEEE.... 1098 / 1132 ( 96%)
#..................................
#
#Time: 12.72 seconds, Memory: 32.00MB
#Tests: 1132, Assertions: 5043, Errors: 20, Failures: 16, Skipped: 1.

# run one test
# vendor/bin/phpunit --colors -c tests/config.xml --group "import,requiresDoctrine,dependsOnOldDatabase" --exclude-group "dependsOnSRTM,dependsOnTimezoneDatabase" --filter InstallDatabaseCommandTest

