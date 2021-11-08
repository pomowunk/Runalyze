<?php
/**
 * This file contains class::SelectRow
 * @package Runalyze\Parameter
 */

namespace Runalyze\Parameter;

/**
 * Select row from database
 * @author Hannes Christiansen
 * @package Runalyze\Parameter
 */
class SelectRow extends Select {
	/** @var bool */
	protected $UseFallback = false;

	/**
	 * Construct
	 * @param mixed $default
	 * @param array $options [optional]
	 */
	public function __construct($default, $options = array()) {
		$options = array_merge(
			array(
				'table' => '',
				'column' => ''
			),
			$options
		);

		parent::__construct($default, $options);
	}

	/**
	 * Table
	 * @return string
	 */
	public function table() {
		return $this->Options['table'];
	}

	/**
	 * Column
	 * @return string
	 */
	public function column() {
		return $this->Options['column'];
	}

	/**
	 * Value allowed?
	 * @param mixed $value
	 * @return bool
	 */
	protected function valueIsAllowed($value) {
		return true;
	}
}
