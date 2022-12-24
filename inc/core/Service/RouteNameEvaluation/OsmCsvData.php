<?php

namespace Runalyze\Service\RouteNameEvaluation;

/**
 * Parses and represents one result line in CSV from a Overpass request.
 * We use the Overpass CSV export (not JSON or XML). So the response is smaller and easier to parse.
 * #TSC
 */
class OsmCsvData {
    // seperator for the fields in a line
    const SEPERATOR = "|";

    // internal categories
    public static $CAT_PEAK = "peak";
    public static $CAT_PLACE = "place";
    public static $CAT_HUT = "hut";
    public static $CAT_WAY = "way";
    public static $CAT_INFO = "info";

    protected static $fieldCount = 12;

    /**
     * Used for internal order of the "place"; 1 is higher than 8 or 9; 9 if not "known" by this logic
     */
    protected static $placeOrder = array("city" => 1, "borough" => 2, "suburb" => 3, "quarter" => 3, "neighbourhood" => 4, "town" => 5,
        "village" => 6, "hamlet" => 7, "isolated_dwelling" => 8);

    /**
     * @var string
     * node|way|relation
     * But is not needed for the evaluation (more for information or debugging)
     */
    public $type;

    /** @var string */
    public $name;

    /** @var string */
    public $nameDe;

    /**
     * @var string
     * examples: city|suburb|town|village|hamlet|isolated_dwelling
     * https://wiki.openstreetmap.org/wiki/DE:Key:place
     */
    public $place;

    /**
     * @var string
     * only: city_limit can be on type=node or type=way
     * https://wiki.openstreetmap.org/wiki/DE:Key:traffic_sign
     */
    public $trafficSign;

    /**
     * @var string
     * on nodes: information (nicht sehr nützlich, weil auch Weggabelung ... z. B. "Rundwanderweg Derchinger Forst")
     * on nodes, ways: alpine_hut|wilderness_hut
     * possible values (we are filter): https://wiki.openstreetmap.org/wiki/Key:tourism
     */
    public $tourism;

    /**
     * @var string
     * only: forest
     */
    public $landuse;

    /**
     * @var string
     * primary on node: peak|saddle|wood|water
     */
    public $natural;

    /**
     * @var string
     * on nodes: elevation on peaks or saddles (partly on huts); used for sorting
     */
    public $elevation;

    /**
     * @var string
     * on ways: yes
     */
    public $hiking;

    /**
     * @var string
     * on way:
     * examples: (names are: Gamsbocksteig, alle betreffen Bergsteigen T1-T6)
     * difficult_alpine_hiking,demanding_alpine_hiking,alpine_hiking,demanding_mountain_hiking,mountain_hiking,hiking
     * (https://wiki.openstreetmap.org/wiki/Key:sac_scale)
     */
    public $sacScale;

    /**
     * @var string
     * on node & way:
     * some alphine huts are amenity=restaurant
     * (https://wiki.openstreetmap.org/wiki/Key:amenity)
     */
    public $amenity;

    /**
     * @var string
     * Not set by OSM, internal sort-order
     */
    public $order;

    /**
     * @var string
     * Not set by OSM, internal category like peak or place (CAT_*)
     */
    public $category;

    /**
     * @param string $csvLine Overpass line seperated with |
	 * @throws \InvalidArgumentException
     */
    public function __construct(string $csvLine) {
        $arr = explode(self::SEPERATOR, $csvLine);
        if(count($arr) != self::$fieldCount) {
            throw new \InvalidArgumentException('Invalid attributes in Overpass result line ' . $csvLine);
        }

        $this->setFields($arr);
        $this->setCategory();
        $this->setOrder();
    }

    protected function setFields(array $arr) {
        $this->type = $arr[0];
        $this->name = $this->cleanNameChar($arr[1]);
        $this->nameDe = $arr[2]? $this->cleanNameChar($arr[2]) : null;

        $this->natural = $arr[7]? $arr[7] : null;
        $this->elevation = $arr[8]? $arr[8] : null;
        if(!empty($this->natural)) return;

        $this->place = $arr[3]? $arr[3] : null;
        if(!empty($this->place)) return;

        $this->trafficSign = $arr[4]? $arr[4] : null;
        if(!empty($this->trafficSign)) return;

        $this->tourism = $arr[5]? $arr[5] : null;
        if(!empty($this->tourism)) return;

        $this->landuse = $arr[6]? $arr[6] : null;
        if(!empty($this->landuse)) return;

        $this->amenity = $arr[11]? $arr[11] : null;
        if(!empty($this->amenity)) return;

        $this->hiking = $arr[9]? $arr[9] : null;
        if(!empty($this->hiking)) return;
        $this->sacScale = $arr[10]? $arr[10] : null;
        if(!empty($this->sacScale)) return;
    }

    protected function cleanNameChar(string $text): string {
        return str_replace("-", " ", $text);
    }

    protected function setCategory() {
        if(!empty($this->natural) && ($this->natural == "peak" || $this->natural == "saddle")) {
            $this->category = self::$CAT_PEAK;
        } else if(!empty($this->place) || !empty($this->trafficSign) ||
            !empty($this->landuse) || $this->natural == "wood" || $this->natural == "water") {
            $this->category = self::$CAT_PLACE;
        } else if($this->tourism == "alpine_hut" || $this->tourism == "wilderness_hut" || $this->amenity == "restaurant") {
            $this->category = self::$CAT_HUT;
        } else if($this->tourism == "way" || !empty($this->hiking) || !empty($this->sacScale)) {
            $this->category = self::$CAT_WAY;
        } else if($this->tourism == "information") {
            $this->category = self::$CAT_INFO;
        }
    }

    protected function setOrder() {
        $o = "";

        // peak/saddle
        if($this->category == self::$CAT_PEAK) {
            // high must be substract, so if it higher it is upper in the sort
            $ele = !empty($this->elevation) ? 9999 - $this->elevation : 9999;
            $o .= sprintf("pk1.hi%04d.", $ele);
        } else {
            $o .= sprintf("pk9.hi%04d.", 9999); // max high
        }

        // places (orte) and orts-schilder
        $o .= sprintf("pl%01d.", $this->getPlaceOrder());
        $o .= !empty($this->trafficSign) ? "si1." : "si9.";

        // (alpine) huts
        $o .= $this->category == self::$CAT_HUT ? "ht1." : "ht9.";

        // sea,lake,water
        $o .= $this->natural == "water" ? "w1." : "w9.";

        // forest/woods
        $o .= !empty($this->landuse) || $this->natural == "wood" ? "fo1." : "fo9.";

        // any (other) information
        $o .= $this->category == self::$CAT_INFO ? "in1" : "in9";

        $this->order = $o;
    }

    protected function getPlaceOrder(): int {
        if(empty($this->place) || !array_key_exists($this->place, self::$placeOrder)) {
            return 9; // always at the end when sorting
        }

        $num = self::$placeOrder[$this->place];
        if(empty($num)) {
            return 9; // always at the end when sorting
        } else {
            return $num;
        }
    }
}