<?php

/**
 * Timesheet class
 */

declare(strict_types=1);

namespace rxnlabs\Timesheet;

class Timesheet
{
    /**
     * The work year being referenced or used.
     */
    private $year;
    /**
     * The work week number representing the week of the year.
     */
    private $weekNumber;
    /**
     * The directory where log files are stored.
     */
    private $logDirectory;

    /**
     * The prefix used for generating unique identifiers for shifts.
     */
    private $uniqIdPrefix = 'shift-';

    public function __construct(string $logDirectory)
    {
        $this->logDirectory = $logDirectory;
        $dates = $this->getThisWorkWeek();
        $this->year = $dates['year'];
        $this->weekNumber = $dates['week_number'];
        $this->createTimesheet();
    }

    /**
     * Calculate and return the current work week based on a pay period that
     * starts on Thursday and ends on the following Wednesday.
     *
     * @return array An associative array containing the following keys:
     * - 'start_day': The DateTime object representing the start of the work week (Thursday).
     * - 'end_day': The DateTime object representing the end of the work week (Wednesday).
     * - 'week_number': Week number of the start day.
     * - 'year': The year of the start day.
     */
    public function getThisWorkWeek(): array
    {
        // Get the Thursday of this work week since the pay period goes from Thursday to the next Wednesday
        $maybeThisWorkWeekThursday = new \DateTime();
        $currentDayOfWeek = (int) $maybeThisWorkWeekThursday->format('w'); // 'w' returns day of the week, Sunday = 0

        // Calculate this work week's Thursday
        if ($currentDayOfWeek !== 4) {
            // If today is not Thursday, find the most recent Thursday
            // subtract 1 from current day of week if today is less than 4 since the day of week
            // index starts at 0 which is Sunday
            $daysToSubtract = $currentDayOfWeek < 4 ? (4 - ($currentDayOfWeek - 1)) : 4 - $currentDayOfWeek;
            $daysToSubtract = abs($daysToSubtract);
            $maybeThisWorkWeekThursday->modify("-$daysToSubtract days");
        }

        // Calculate the next Wednesday
        $nextWednesday = new \DateTime();
        if ($currentDayOfWeek === 4) {
            // If today is Thursday, find next Wednesday
            $nextWednesday->modify('+6 days');
        } else {
            // Otherwise, find the closest upcoming Wednesday from today
            $daysToAdd = $currentDayOfWeek <= 3 ? (3 - $currentDayOfWeek) : (10 - $currentDayOfWeek);
            $nextWednesday->modify("+$daysToAdd days");
        }

        return [
            'start_day' => $maybeThisWorkWeekThursday,
            'end_day' => $nextWednesday,
            'week_number' => $maybeThisWorkWeekThursday->format('W'),
            'year' => $maybeThisWorkWeekThursday->format('Y')
        ];
    }

    /**
     * Retrieve timesheet data for a specified year and week number, or default to
     * the object's current year and week number if not provided. Sanitizes and
     * structures the timesheet information into an array format.
     *
     * @param  string|int  $year  The year for which to retrieve the timesheet data.
     * Defaults to the object's current year.
     * @param  string|int  $week_number  The week number for which to retrieve the timesheet data.
     * Defaults to the object's current week number.
     *
     * @return array The structured timesheet data, containing an array of entries
     * with keys: 'day', 'location', 'clockIn', 'clockOut', 'minutes', and 'hours'.
     */
    public function getTimesheetData($year = '', $week_number = ''): array
    {
        $fileData = null;

        if (empty($year) || !is_numeric($year)) {
            $year = $this->year;
        }

        if (empty($week_number) || !is_numeric($week_number)) {
            $week_number = $this->weekNumber;
        }

        $data = ['timesheet' => [], 'totalHours' => 0];
        $totalHours = 0;
        $totalMinutes = 0;

        $timesheet = $this->findTimesheet($year, $week_number);

        if ($timesheet !== false) {
            $fileData = fopen($timesheet, 'r');
            while (($line = fgets($fileData)) !== false) {
                $lineParts = explode(' ', $line);
                list($id, $day, $location, $clockIn, $clockOut, $minutes, $hours) = $lineParts;

                $id = $this->sanitizeString($id);
                $day = $this->sanitizeString($day);
                $location = $this->sanitizeString($location);
                $clockIn = $this->sanitizeString($clockIn);
                $clockOut = $this->sanitizeString($clockOut);
                $minutes = $this->sanitizeString($minutes);
                $hours = $this->sanitizeString($hours);
                // show clock-in data even if there is no clock-out data
                if (empty($day) || empty($location) || empty($clockIn)) {
                    continue;
                }

                // if there is no clock-out data, set all to 0
                if (empty($clockOut) || empty($minutes) || empty($hours)) {
                    $clockOut = '';
                    $minutes = 0;
                    $hours = 0;
                }

                $data['timesheet'][] = [
                    'id' => $id,
                    'day' => $day,
                    'location' => $location,
                    'clockIn' => $clockIn,
                    'clockOut' => $clockOut,
                    'minutes' => $minutes,
                    'hours' => $hours
                ];

                $totalMinutes += $minutes;
            }
        }

        if ($totalMinutes > 0) {
            $totalHours = $totalMinutes / 60;
        }

        $data['totalHours'] = $totalHours;

        return $data;
    }

    /**
     * Locate a timesheet file based on the provided year and week number.
     *
     * @param  string|int  $year  Optional. The year for which the timesheet is being searched.
     * Defaults to internal property if empty or non-numeric.
     * @param  string|int  $week_number  Optional.
     * The week number for which the timesheet is being searched. Defaults to internal property if empty or non-numeric.
     *
     * @return string|bool|null The full path to the timesheet file if found, false if not found, or null
     * in case of errors.
     */
    public function findTimesheet($year = '', $week_number = ''): string|bool|null
    {
        if (empty($year) || !is_numeric($year)) {
            $year = $this->year;
        }

        if (empty($week_number) || !is_numeric($week_number)) {
            $week_number = $this->weekNumber;
        }

        $directory = $this->logDirectory . '/' . $year;
        $files = glob($directory . '/*.txt');
        $foundTimesheet = false;
        foreach ($files as $file) {
            $fileName = basename($file);
            if (str_starts_with($fileName, 'week-' . $week_number)) {
                $foundTimesheet = realpath($file);
                break;
            }
        }

        return $foundTimesheet;
    }

    /**
     * Create a new timesheet file for the current work week or return an existing one
     * if it already exists. The work week is determined as Thursday to the following Wednesday.
     * Ensures the year directory exists and generates the timesheet filename based on the
     * corresponding week's date range.
     *
     * @return string|bool The path to the created or found timesheet file, or false on failure.
     */
    public function createTimesheet()
    {
        $dates = $this->getThisWorkWeek();
        $startDay = $dates['start_day'];
        $endDay = $dates['end_day'];

        // Determine the appropriate timesheet's workweek number based on the Thursday
        $findTimesheet = $this->findTimesheet($dates['year'], $dates['week_number']);
        if ($findTimesheet !== false) {
            return $findTimesheet;
        }

        // Ensure the directory for the year exists
        $this->createYearDirectory();

        // Construct the filename for the new timesheet
        $newTimesheet = sprintf(
            '%s/%s/week-%s.%s-%s-%s-%s.hours.txt',
            $this->logDirectory,
            $dates['year'],       // Year of the Thursday
            $dates['week_number'],       // Week number of the Thursday
            $startDay->format('n'),       // Month of the Thursday
            $startDay->format('j'),       // Day of the month (Thursday)
            $endDay->format('n'),       // Month of the Wednesday
            $endDay->format('j')        // Day of the month (Wednesday)
        );

        // Create the new timesheet file
        $file = fopen($newTimesheet, 'w');
        if ($file === false) {
            return false;
        }
        fclose($file);

        return $newTimesheet;
    }

    /**
     * Add a new entry to the timesheet file with details such as day, location,
     * clock-in time, clock-out time, minutes worked, and hours worked.
     *
     * @param  string  $day  The day of the timesheet entry.
     * @param  string  $location  The location associated with the timesheet entry.
     * @param  string  $clockIn  The clock-in time in a valid time format.
     * @param  string  $clockOut  The clock-out time in a valid time format.
     *
     * @return bool Returns true on successful entry addition, false on failure to open or write to the file.
     */
    public function addTimesheetEntry(string $day, string $location, string $clockIn, string $clockOut)
    {
        $timesheet = $this->findTimesheet();
        if ($timesheet !== false) {
            $file = fopen($timesheet, 'a');
            if ($file === false) {
                return false;
            }
            $verify = fwrite($file, $this->normalizeTimesheetEntry($day, $location, $clockIn, $clockOut) . PHP_EOL);

            if ($verify === false) {
                return false;
            } else {
                return true;
            }
        }
    }

    /**
     * Edit an existing entry in the timesheet file by replacing it with updated
     * details such as day, location, clock-in time, clock-out time, minutes worked, and hours worked.
     *
     * @param  string     $id        The ID of the timesheet entry to be edited.
     * @param  string  $day       The updated day of the timesheet entry.
     * @param  string  $location  The updated location associated with the timesheet entry.
     * @param  string  $clockIn   The updated clock-in time in a valid time format.
     * @param  string  $clockOut  The updated clock-out time in a valid time format.
     *
     * @return bool Returns true on successful entry update, false on failure to open, read, or write to the file.
     */
    public function editTimeEntry(string $id, string $day, string $location, string $clockIn, string $clockOut): bool
    {
        $timesheet = $this->findTimesheet();

        if ($timesheet !== false) {
            // Read the entire file into an array to work with
            $lines = file($timesheet, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines === false) {
                return false;
            }

            // Check if ID exists in the file and replace the corresponding line
            $found = false;
            foreach ($lines as $key => $line) {
                if (str_starts_with($line, $id)) { // Match the ID at the start of the line
                    $lines[$key] = $this->normalizeTimesheetEntry($day, $location, $clockIn, $clockOut, $id);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                // ID not found in the file
                return false;
            }

            // Write the updated lines back to the file
            $result = file_put_contents($timesheet, implode(PHP_EOL, $lines) . PHP_EOL);
            return $result !== false;
        }

        return false;
    }

    /**
     * Normalize a timesheet entry by validating and adjusting the clock-in and
     * clock-out times, calculating the total hours and minutes worked, and formatting
     * the output for consistent representation.
     *
     * @param  string  $day  The day of the timesheet entry.
     * @param  string  $location  The location associated with the entry.
     * @param  string  $clockIn  The clock-in time in a string format (e.g., "HH:MM").
     * @param  string  $clockOut  The clock-out time in a string format (e.g., "HH:MM").
     * @param null|string $id The ID to use for the entry
     *
     * @return string A formatted string representing the normalized timesheet entry,
     *                including day, location, normalized clock-in and clock-out times,
     *                and hours and minutes worked.
     */
    public function normalizeTimesheetEntry(string $day, string $location, string $clockIn, string $clockOut, null|string $id = null): string
    {
        $day = $this->sanitizeString($day);
        $location = $this->sanitizeString($location);

        $maxHours = 24;
        // Validate and normalize inputs
        $clockInTime = $this->parseTime($clockIn);
        $clockOutTime = $this->parseTime($clockOut);

        // Extract hours and minutes
        [$clockInHour, $clockInMin] = $clockInTime;
        [$clockOutHour, $clockOutMin] = $clockOutTime;

        // Adjust for clock-out past midnight (if necessary)
        if ($clockOutHour < $clockInHour) {
            $clockOutHour += 12;
        }

        // Validate time range
        $this->validateTimeRange($clockInHour, $clockInMin, $clockOutHour, $clockOutMin);

        // Calculate total minutes worked
        $totalMinutesWorked = (($clockOutHour * 60 + $clockOutMin) - ($clockInHour * 60 + $clockInMin));
        if ($totalMinutesWorked < 0) {
            $totalMinutesWorked += $maxHours * 60; // Handle cases where clock-out time resets past midnight
        }

        // Calculate hours and remaining minutes
        $hoursWorked = intdiv($totalMinutesWorked, 60);
        $minutesWorked = $totalMinutesWorked % 60;
        $hoursMinutesPercentage = $totalMinutesWorked / 60;

        // Reconstruct normalized clock-in and clock-out times
        $normalizedClockIn = sprintf("%02d:%02d", $clockInHour, $clockInMin);
        $normalizedClockOut = sprintf("%02d:%02d", $clockOutHour, $clockOutMin);

        if ($id === null) {
            $id = uniqid($this->uniqIdPrefix);
        }

        return sprintf(
            "%s %s %s %s %s %d %f",
            $id,
            $day,
            $location,
            $normalizedClockIn,
            $normalizedClockOut,
            $totalMinutesWorked,
            $hoursMinutesPercentage
        );
    }

    /**
     * Parse a time string in the format 'HH:MM' and extract its hour and minute components.
     *
     * @param  string  $time  The time string to parse, in the format 'HH:MM'.
     *
     * @return array An array containing two integers: the hour and the minute.
     * @throws \Exception If the time string does not include a colon or is not in the correct format.
     */
    private function parseTime(string $time): array
    {
        if (strpos($time, ":") === false) {
            throw new \Exception("Time must include a colon (e.g., '12:30').");
        }

        $timeParts = explode(":", $time);
        if (
            count($timeParts) !== 2
            || strlen($timeParts[0]) > 2
            || strlen($timeParts[1]) !== 2
            || !is_numeric((int)$timeParts[0])
            || !is_numeric((int)$timeParts[1])
        ) {
            throw new \Exception("Invalid time format. Please enter the time in the 'HH:MM' format (e.g., '12:30'). Do not include 'am' or 'pm' identifiers.");
        }

        return [(int)$timeParts[0], (int)$timeParts[1]];
    }

    /**
     * Validate the given time range to ensure that the hours and minutes
     * fall within acceptable boundaries.
     *
     * @param  int  $inHour  The input starting hour, expected in a 24-hour format.
     * @param  int  $inMin  The input starting minutes.
     * @param  int  $outHour  The output ending hour, expected in a 24-hour format.
     * @param  int  $outMin  The output ending minutes.
     *
     * @return void
     *
     * @throws \Exception Thrown when any hour exceeds 24, any minute exceeds 59,
     * or when the calculated time exceeds 24:00.
     */
    private function validateTimeRange(int $inHour, int $inMin, int $outHour, int $outMin): void
    {
        $maxHours = 24;
        $maxMinutes = 59;
        $maxTimeFormat = 2400;

        if ($inHour > $maxHours || $outHour > $maxHours) {
            throw new \Exception("Hour cannot exceed " . $maxHours . ":00.");
        }

        if ($inMin > $maxMinutes || $outMin > $maxMinutes) {
            throw new \Exception("Minutes cannot exceed " . $maxMinutes . ".");
        }

        if (($inHour * 100 + $inMin) > $maxTimeFormat || ($outHour * 100 + $outMin) > $maxTimeFormat) {
            throw new \Exception(
                "Time cannot exceed 24:00 (use '00:00' or '12:00' for midnight)."
            );
        }
    }

    /**
     * Create a directory for the current year if it does not already exist.
     *
     * @return bool True if the directory was successfully created or already exists, false otherwise.
     */
    public function createYearDirectory()
    {
        $directory = $this->logDirectory . '/' . $this->year;

        if (!is_dir($directory)) {
            return mkdir($directory, 0755, true);
        } elseif (is_dir($directory)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize a string to remove potential threats such as HTML, JavaScript,
     * SQL injection attempts, and ensure it's safe to display on the frontend.
     *
     * @param string $input The string to sanitize.
     * @return string The sanitized and normalized string.
     */
    protected function sanitizeString(string $input): string
    {
        // Remove HTML tags, including script and style tags
        $input = strip_tags($input);

        // Decode HTML entities to prevent double encoding issues
        $input = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Escape special characters to prevent SQL injection risks
        $input = addslashes($input);

        // Convert special characters back to their HTML-encoded equivalents
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');

        // Normalize UTF-8 encoding to prevent encoding issues
        $input = mb_convert_encoding($input, 'UTF-8', 'UTF-8');

        // Trim whitespace from the beginning and end of the string
        $input = trim($input);

        return $input;
    }
}
