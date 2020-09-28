# [RUNALYZE v4.3.0] Release with minor fixes/changes.

This fork of Runalyze is a release fork with all needed dependencies and can be used directly with [Docker](https://github.com/2er0/runalyze-docker).
I have done some small fixes/imporvements.
Because it based on the [Release 4.3.0](https://github.com/Runalyze/Runalyze/releases/tag/v4.3.0) i hope it is more future-proof in an "old" docker container.

I host it on a private Pine64 Rock64 SOC computer to host my family activities (running, walking, mountain climbing, swimming). It runs on a Debian Buster/ARM64 in a Docker container serviced with docker-compose. Buster supports PHP 7.3 and it runs without problems with some PHP warnings. As input GPS devices i use Garmin Forerunner 45S, Garmin Fenix 6 and Android-Handy with ApeMap/OruxMaps. I import my tacks without use of Garmin-Tools (like Garmin Connect) and so i think no of my private sensible health-data is transmit to the "public" cloud.

With my other Github project [Clone of Tkl2Gpx](https://github.com/codeproducer198/Tkl2Gpx) i have imported my old running activities from the year 2012 until now into RUNALYZE. These old tracks are record with a GPS MapJack watch and transformed to GPX files imported via RUNYLZE bulk-job.

Here some fixes/improvements i have done in RUNALYZE (see details in the commits):
* Fixes some small bugs until the base release is running on my environment (missing DB attribute, wrong/missing number values, ...)
* Batch/Bulk-imports can now set/override the sports type
* Imports from MapJack watch/GPX and Garmin FR45 & Fenix6/FIT results in errors because missing heart-rates and altitutes. Now the NULL will be filled.
* Sport types hiking and (new) mountain climbing.
* Imported filename is stored in title attribute.
* Temperature of FIT files are stored in the temp attribute as average value.
* 2020-09-27: Import Garmin FIT "total_training_effect"-attribute (Aerob Training Effect) already with greater 0.0 (and not even 1.0)
* 2020-09-27: Garmin FIT "total_anaerobic_training_effect" attribute as "Anaerobic Training Effect" is imported
	* Store it in the DB (runalyze_training.fit_anaerobic_training_effect)
	* Added on the dataset configuration
	* Also add field on statistic heart-rate view (activity main page)
	* **Migration 20200926230800 is necessary!**

## Database migration

For Migration of the Database use the commands:
- Check state: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:status --env=prod --show-versions`
- Do it: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod [--dry-run]`
- Rollback to an previous version: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod <VersionAufDieZurückgerolltWerdenSoll>`
Notice: Do the migration with your "project" user.
