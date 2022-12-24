<?php

namespace Runalyze\Service\RouteNameEvaluation;

/**
 * Represents all result line from a Overpass response.
 * #TSC
 */
class OsmCsvDataCollection {
    protected $data = array();

    // array with key=<name> and value=<category>
    protected $names = array();

    /**
     * @param string $csvLines Overpass lines seperated with |
	 * @throws \InvalidArgumentException
     */
    public function __construct(string $csvLines) {
        $lines = explode(PHP_EOL, $csvLines);

        foreach($lines as &$l) {
            if(substr($l, 0, 6) !== '@type'.OsmCsvData::SEPERATOR && strlen($l) > 0) { // ignore header-line
                $this->data[] = new OsmCsvData($l);
            }
        }

        $this->sort();
        $this->fillOrderedNames();
    }

    protected function sort() {
        usort($this->data, function($a, $b) {
            $r = $a->order <=> $b->order;
            if($r != 0) {
                return $r;
            } else {
                // order is the same, sort with name
                return $a->name <=> $b->name;
            }
        });
    }

    public function hasNames() {
        return !empty($this->data);
    }

    /**
     * fills the internal array which map the names to a category.
     */
    protected function fillOrderedNames() {
        foreach($this->data as &$e) {
            $n = empty($e->nameDe) ? $e->name : $e->nameDe;

            // if this name is not already in the list, add it
            if(!array_key_exists($n, $this->names)) {
                $this->names[$n] = $e->category;
            }
        }
    }

    /**
     * get (already sorted) names of a category.
     */
    public function getNames(string $category): array {
        return array_keys(array_filter($this->names, function($v, $k) use ($category) {
            return $v == $category;
        }, ARRAY_FILTER_USE_BOTH));
    }
}
