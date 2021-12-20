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
* 2021-01-10: Add API-REST service /api/import/activity for uploading and importing activities for an account
	* The POST request must be a "multipart/form-data" to support multiple times in one request.
	* This new API uses the existing command ActivityBulkImportCommand
	* If no problems occur and all uploaded files are imported successfully a HTTP state 200 is returned otherwise (duplicates or failed) a state 202 is returned.
	* If there are technical trouble (while creating the tmp-folder, moving files ...) a HTTP state 500 is returned.
	* Additional infos and the output of the "runalyze:activity:bulk-import" are set in the response content as "Content-Type: text/plain"
	* Example curl to use: curl -k -u "<runalyze-account>:<pwd>" -H "Accept-Language: de,en" -X POST https://<domain>/api/import/activity -F 'file1=@afile.fit' -F 'file2=@another.fit' [-w 'HttpCode: %{http_code}\n']
* 2021-11-05: Add tests(-folder) from the archived repository to ensure dependency changes
	1. database preperation script `tests/scripts/preparetests.sh` (one time action per DB instance)
	2. wrapper script for all tests `tests/scripts/runtests.sh`
	* some tests has errors (i think is caused by the usage of PHP7); see `runtests.sh` for expected failures
	* `tests` folder can be excluded for production environment
* 2021-11-09: Upgrade PHP dependencies to newer/recent versions
	* only deps where none or only few code changes necessary
	* changes affected composer-files, vendor folder, database migrations, tests
	* Clone `laufhannes/GpxTrackPoster` to `codeproducer198/GpxTrackPoster` and use this dependency (commit from Mar 11, 2017)
* 2021-11-14: Upgrade web dependencies to newer/recent versions
	* only javascript deps where no error occur while the resource build or the app works
	* changes affected `bower.json`, `web/vendor` folder
* 2021-11-14: Upgrade to a newer Runalyze Glossary version (fixed commit-id dev-master#d7e2540bf51dc1aabe4dbdce85af96e8203311b6)
	* Seems the last version which works with the v4.3.0
* 2021-11-14: Garmins _Performance Condition_ is now saved in the purpose column for the newest watches
	* _Performance Condition_ in the "fit detail" section of an selected activity is now also displayed as a value between -20/+20
	* Supported watches are: Forerunner 640 & 645 & 935 & 945 & 735, Fenix 3 & 5 & 6
	* Caution: the search page still uses values based on 100; if you search for "+5" use "105" ;-)
	* The value is stored so far in the column `runalyze_training.fit_hrv_analysis`; to fix your database you can use this sqls to move the values to `fit_performance_condition`:
	`update runalyze_training set fit_performance_condition = fit_hrv_analysis where fit_performance_condition is null and fit_hrv_analysis is not null and fit_performance_condition_end is not null;`
	and
	`update runalyze_training set fit_hrv_analysis = null where fit_performance_condition = fit_hrv_analysis is not null and fit_performance_condition_end is not null;`
* 2021-12-19: Add Garmins _Performance Condition_ and _Respiration Rate_ from the FIT activity files to new database-continuous-data columns and show it as diagrams
	* Store _Performance Condition_ (not the single "start" and "end" value of the _FIT details_) in `runalyze_trackdata.performance_condition` while importing FIT file
	* Store _Respiration Rate_ in `runalyze_trackdata.respiration_rate` while importing FIT file
	* Show _Performance Condition_ as line diagram in the _Miscellaneous_ panel if available
	* Show _Respiration Rate_ as line diagram in the _HVR_ panel if available (resp-rate is only available for me with my Fenix 6 if i use a _HRM-Run_ sensor)
	* Do same fixes to diagrams so not every time-tick needs a value (needed for _Performance Condition_ because tracking starts from one kilometer)
	* **Migration 20211215213500 is necessary!**
* 2021-12-20: Support Garmins _Respiration Rate_ (avg & max) and _self-evaluation_-fields (feeling & perceived effort) for FIT activity import and show the values
	* New db columns in table `training`: `fit_self_evaluation_feeling`, `fit_self_evaluation_perceived_effort`, `avg_respiration_rate`, `max_respiration_rate`
	* Display and search for _AVG Respiration Rate_ in the dataview settings/activity table and in the HRV detail section
	* Show all values in the _FIT details_ section
	* _self-evaluation_-fields are only available with the Fenix watches with a firmware 19.20 and you must activate _self-evaluation_ for your the activities
	* Parsing _self-evaluation_-effort with the enum `SelfEvaluationPerceivedEffort` to a human readable text same as Garmin Connect shows
	* **Migration 20211219161500 is necessary!**

Please notice:
* All the changes are only done for me to use this great product for me.
* I don't take any responsibility if you running this version on your infrastructure and have problems.
* The extensions i made was done on a release version. So i do not build a release. I have no translations for the new features (always use german language).

## Database migration

For Migration of the Database use the commands:
- Check state: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:status --env=prod --show-versions`
- Do it: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod [--dry-run]`
- Rollback to an previous version: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod <VersionAufDieZurückgerolltWerdenSoll>`
Notice: Do the migration with your "project" user.
