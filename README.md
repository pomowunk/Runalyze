# RUNALYZE personal fork based on v4.3.0

[Runalyze](https://blog.runalyze.com) is a web application for analyzing your training - more detailed than any other sports diary.
We are offering a official hosted version at [runalyze.com](https://runalyze.com).
Runalyze is mainly developed by [laufhannes](https://github.com/laufhannes) and [mipapo](https://github.com/mipapo).

## Documentation
We provide two different documentations.
Both documentations have their own repos: [docs](https://github.com/Runalyze/docs) and [admin-docs](https://github.com/Runalyze/admin-docs). In addition, there's our [runalyze-playground](https://github.com/Runalyze/runalyze-playground) to play around with some new ideas. Feel free to contribute there.

## Install / Development
Runalyze requires [composer](https://getcomposer.org/doc/00-intro.md#system-requirements) and
[npm](https://nodejs.org/en/download/)
(plus [bower](https://bower.io/) and
[gulp](https://gulpjs.com/), will be installed via npm).

To install dependencies and build:
```
composer install
npm install
gulp
```

## Features
 * import activity files (*.fit, *.tcx, *.gpx and many more)
 * TRIMP principle
 * long-term form analysis
 * VO2max estimation
 * race prediction based on your shape
 * statistics like *Monotony, training strain, stress balance*
 * heart rate variability (HRV) in activity view
 * elevation correction and calculation
 * ...

Look at [help.runalyze.com](https://help.runalyze.com/en/latest/features.html) for a feature list with screenshots.

## License
Yep, we know that we have to add a `LICENSE.md` and `CONTRIBUTING.md` to our repository. Finally we need to setup a CLA. These things take time and we are really busy developing new things for RUNALYZE.
 (see discussion at [#952](https://github.com/Runalyze/Runalyze/issues/952))

## Credits
* Icons
  * [Font Awesome](http://fontawesome.io/) by Dave Gandy
  * [Forecast Font](http://forecastfont.iconvau.lt/) by Ali Sisk
  * [Icons8 Font](https://icons8.com/) by VisualPharm
* Elevation data from Shuttle Radar Topography Mission
  * [SRTM tiles](http://dwtkns.com/srtm/) grabbed via Derek Watkins
  * [SRTM files](http://srtm.csi.cgiar.org/) by International Centre for Tropical  Agriculture (CIAT)
  * [SRTMGeoTIFFReader](https://www.osola.org.uk/elevations/index.htm) by Bob Osola
* [jQuery](https://jquery.org/) by jQuery Foundation, Inc.
  * [Bootstrap Tooltip](https://bootstrapdocs.com/v2.0.0/docs/javascript.html#tooltips) by Twitter, Inc.
  * [Flot](http://www.flotcharts.org/) by IOLA and Ole Laursen
  * [Leaflet](http://leafletjs.com/) by Vladimir Agafonkin
  * [FineUploader](https://github.com/Widen/fine-uploader) by Widen Enterprises, Inc.
  * [Tablesorter](http://tablesorter.com/docs/) by Christian Bach
  * [Datepicker](http://www.eyecon.ro/) by Stefan Petre
  * [Chosen](https://www.getharvest.com/) by Patrick Filler for Harvest
  * [FontIconPicker](https://codeb.it/) by Alessandro Benoit &amp; Swashata Ghosh
* Miscellaneaous
  * [phpFastCache](https://github.com/khoaofgod/phpfastcache) by Khoa Bui
  * [Garmin Communicator](https://software.garmin.com/de-DE/gcp.html) by Garmin Ltd.
  * [Weather data](http://openweathermap.org) from OpenWeatherMap Inc.
