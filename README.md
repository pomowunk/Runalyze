# [RUNALYZE v4.3.0] Release with minor fixes/changes.

This fork of Runalyze is a release fork with all needed dependencies and can be used directly with [Docker](https://github.com/2er0/runalyze-docker).
I have done some small fixes/imporvements.
Because it based on the [Release 4.3.0](https://github.com/Runalyze/Runalyze/releases/tag/v4.3.0) i hope it is more future-proof in an "old" docker container.

I host it on a private Pine64 Rock64 SOC computer to host my family activities (running, walking, mountain climbing, swimming). It runs on a Debian Buster/ARM64 in a Docker container serviced with docker-compose. Buster supports PHP 7.3 and it runs without problems with some PHP warnings. As input GPS devices i use Garmin Forerunner 45S, Garmin Fenix 6 and Android-Handy with ApeMap/OruxMaps. I import my tacks without use of Garmin-Tools (like Garmin Connect) and so i think no of my private sensible health-data is transmit to the "public" cloud.

With my other Github project [Clone of Tkl2Gpx](https://github.com/codeproducer198/Tkl2Gpx) i have imported my old running activities from the year 2012 until now into RUNALYZE. These old tracks are record with a GPS MapJack watch and transformed to GPX files imported via RUNYLZE bulk-job.

Here some fixes/improvements i have done in RUNALYZE (see details in the commits):
* Fixes some small bugs until the base release is running on my environment (missing DB attribute, wrong/missing number values, ...)
* Fixes some small bugs while importing FIT files from Garmin
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
* 2020-09-29: Garmin FIT attributes as fit_lactate_threshold_hr, fit_total_ascent/descent (the watch original values) are imported
	* Store it in the DB (runalyze_training.fit_anaerobic_training_effect)
	* Shown in the statistic view (activity main page) under 'Miscellaneous' panel
	* **Migration 20200928150400 is necessary!**
* 2020-09-29: Show further FIT file attributes (recovery-time, training-effect, creator, vo2max, performance-conditions, hvr) on statistic view (activity main page) under 'Miscellaneous' panel
* 2020-10-06: Loading weather data now uses the time in the middle of the activity _(start + (duration / 2))_.
* 2020-10-07: Add support for [meteostat](https://meteostat.net) historical weather data while editing a activity or while bulk import. Usable with setting the new "meteostatnet_api_key" in config.yml.
* 2020-10-17 to 2020-11-04: Import heartrate and temperature of Fenix 6 for swimming activities.
* 2020-11-04: Some fixing of correlate trackdata to laps/swim-lanes.
* 2020-11-09: Auto detection of type "interval-training" (detection only works in batch/bulk-mode). You must configure a training-type with short-cut "IT" to your sports in the configuration to use this feature.
* 2021-01-02: Fix "Start date" of an existing equipment is set to the stored date (not to current date).
* 2021-01-02: While batch/bulk-importing of activities main equipment of the sports are assigned to new activities
	* only main equipment-type are considered (set your equipment type as main equipment in the sports configuration)
	* only equipment-type with single-choice considered
	* a unique/time-ranged equipment must exists for this sport; if multiple equipments are found, nothing is assigned and a warning is logged while importing
	* keep in mind, that f. e. multiple shoes can not be mapped because no shoe can be clearly identified

Please notice:
* All the changes are only done for me to use this great product for me.
* I don't take any responsibility if you running this version on your infrastructure and have problems.
* The extensions i made was done on a release version. So i do not build a release. I have no translations for the new features.

## Database migration

For Migration of the Database use the commands:
- Check state: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:status --env=prod --show-versions`
- Do it: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod [--dry-run]`
- Rollback to an previous version: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod <VersionAufDieZurückgerolltWerdenSoll>`
Notice: Do the migration with your "project" user.
