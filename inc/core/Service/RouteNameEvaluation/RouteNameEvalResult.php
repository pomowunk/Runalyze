<?php

namespace Runalyze\Service\RouteNameEvaluation;

/**
 * Result of the route-name evaluation to be used/stored on the training/activity.
 * #TSC
 */
class RouteNameEvalResult {
    const NOTES_START = "Strecken-Info>>";
    const NOTES_END = "<<Strecken-Info";
    const NOTES_PATTERN = "/(.*)(" . self::NOTES_START . ".*" . self::NOTES_END . ")(.*)/is";
    
    /** @var string */
    protected $names = '';

    /** @var string */
    protected $notes = '';

    /**
     * @param string $names
     * @param string $notes
     */
    public function __construct(string $names, string $notes) {
        // it should never occur, but limit it to 255 (same as the database attribute)
        $this->names = mb_strimwidth($names, 0, 255, "...");
        $this->notes = $notes;
    }

    public function getNames(): string{
        return $this->names;
    }

    /**
     * Gets the plain notes text without prefix and suffix.
     */
    public function getNotes(): string{
        return $this->notes;
    }

    /**
     * Appends or replace the existing note with the new notes text based on pre-/suffix.
     * 
     * @param string existingNote
     */
    public function appendNotes(?string $existingNote): string {
        if(empty($this->notes)) {
            return $existingNote;
        }

        $taggedNote = self::NOTES_START . PHP_EOL . $this->notes . ($this->endsWith($this->notes, PHP_EOL) ? '' : PHP_EOL) . self::NOTES_END;

        if(empty($existingNote)) {
            return $taggedNote;
        } else if(strpos($existingNote, self::NOTES_START) === false) {
            // when append the eval-note the first time, make a newline between for better usability
            return $existingNote . ($this->endsWith($existingNote, PHP_EOL) ? '' : PHP_EOL) . PHP_EOL . $taggedNote;
        } else {
            // replace between the tags the existing text
            return preg_replace(self::NOTES_PATTERN, "$1" . $taggedNote . "$3", $existingNote);
        }
    }

    protected function endsWith($haystack, $needle) {
        return $needle === "" || (substr($haystack, -strlen($needle)) === $needle);
    }
}