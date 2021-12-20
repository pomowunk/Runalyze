<?php
/**
 * This file contains class::SectionRunningDynamics
 * @package Runalyze\DataObjects\Training\View\Section
 */

use Runalyze\Model\Trackdata;

/**
 * Section: Running dynamics
 *
 * @author Hannes Christiansen
 * @package Runalyze\DataObjects\Training\View\Section
 */
class SectionRunningDynamics extends TrainingViewSectionTabbedPlot {
	/**
	 * Set header and rows
	 */
	protected function setHeaderAndRows() {
		$this->Header = __('Running Dynamics').' <a class="window" href="glossary/run-dynamics"><i class="fa fa-question-circle-o"></i></a>';

		$this->appendRowTabbedPlot( new SectionRunningDynamicsRow($this->Context) );
	}

	/**
	 * Has the training all required data?
	 * @return bool
	 */
	protected function hasRequiredData() {
		return (
			$this->Context->trackdata()->has(Trackdata\Entity::CADENCE) ||
			$this->Context->trackdata()->has(Trackdata\Entity::VERTICAL_OSCILLATION) ||
			$this->Context->trackdata()->has(Trackdata\Entity::GROUNDCONTACT)
		);
	}

	/**
	 * CSS-ID
	 * @return string
	 */
	protected function cssId() {
		return 'runningdynamics';
	}
}
