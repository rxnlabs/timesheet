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

    public function __construct(string $logDirectory, $baseDate = null)
    {
        $this->logDirectory = $logDirectory;
        $dates = $this->getThisWorkWeek($baseDate);
        $this->year = $dates['year'];
        $this->weekNumber = $dates['week_number'];
        $this->createTimesheet($baseDate);
    }

    /**
     * Retrieves the directory path used for storing the timesheet logs.
     *
     * @return string The path to the timesheet logs.
     */
    public function getLogDirectory(): string
    {
        return $this->logDirectory;
    }

    /**
     * Calculate and return details of the current work week based on a specified start day
     * or the current day if no start day is provided. The work week spans from Thursday
     * to the following Wednesday.
     *
     * @param  mixed  $startDay  Optional. The starting day to calculate the work week.
     *
     * @return array An associative array containing the details of the work week:
     *               - 'start_day': The DateTime instance representing the starting Thursday of the work week.
     *               - 'end_day': The DateTime instance representing the ending Wednesday of the work week.
     *               - 'week_number': The ISO week number of the work week.
     *               - 'year': The year in which the work week's Thursday falls.
     */
    public function getThisWorkWeek($startDay = null): array
    {
        // Get the Thursday of this work week since the pay period goes from Thursday to the next Wednesday
        // Determine the base date from the input
        $baseDate = $this->getDateTimeFromParam($startDay);
        $maybeThisWorkWeekThursday = clone $baseDate;
        $currentDayOfWeek = (int) $maybeThisWorkWeekThursday->format('w'); // 'w' returns day of the week, Sunday = 0
        $daysDifference = 0;

        switch ($currentDayOfWeek) {
            case 0: // Sunday
                $daysDifference = -3; // Previous Thursday
                break;
            case 1: // Monday
                $daysDifference = -4; // Previous Thursday
                break;
            case 2: // Tuesday
                $daysDifference = -5; // Previous Thursday
                break;
            case 3: // Wednesday
                $daysDifference = -6; // Previous Thursday
                break;
            case 4: // Thursday
                $daysDifference = 0; // Today is Thursday
                break;
            case 5: // Friday
                $daysDifference = -1; // Previous Thursday
                break;
            case 6: // Saturday
                $daysDifference = -2; // Previous Thursday
                break;
        }
        $maybeThisWorkWeekThursday->modify("$daysDifference days");

        // Calculate the next Wednesday
        $nextWednesday = clone $maybeThisWorkWeekThursday;
        $nextWednesday->modify('+6 days');

        return [
            'start_day' => $maybeThisWorkWeekThursday,
            'end_day' => $nextWednesday,
            'week_number' => $maybeThisWorkWeekThursday->format('W'),
            'year' => $maybeThisWorkWeekThursday->format('Y')
        ];
    }

    /**
     * Convert the provided parameter into a DateTime object based on its type.
     * The method accepts a DateTime object, a timestamp, or a date string in the format 'MM/DD/YYYY',
     * and returns a DateTime object. If the input is invalid, it defaults to the current date.
     *
     * @param  \DateTime|int|string  $startDay  The input parameter, which can be a DateTime object,
     *                                       a Unix timestamp, a string in 'MM/DD/YYYY' format,
     *                                       or omitted to use the current date.
     *
     * @return \DateTime The constructed DateTime object based on the provided input.
     *
     * @throws \InvalidArgumentException If the input string does not match the expected date format 'MM/DD/YYYY'.
     */
    public function getDateTimeFromParam($startDay = null): \DateTime
    {
        if ($startDay === null) {
            $baseDate = new \DateTime(); // Default to current date
        } elseif ($startDay instanceof \DateTime) {
            $baseDate = clone $startDay; // Use the given DateTime object
        } elseif (is_int($startDay)) {
            $baseDate = (new \DateTime())->setTimestamp($startDay); // Interpret as a timestamp
        } elseif (!empty($baseDate) && is_string($startDay)) {
            $baseDate = \DateTime::createFromFormat('m/d/Y', $startDay); // Parse MM/DD/YYYY
            if (!$baseDate) {
                throw new \InvalidArgumentException('Invalid date format. Expected MM/DD/YYYY.');
            }
        } else {
            $baseDate = new \DateTime(); // Default to current date
        }

        return $baseDate;
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
                $cleanLine = trim($line);
                if (empty($cleanLine)) {
                    continue;
                }

                $lineParts = explode(' ', $cleanLine);

                if (empty($lineParts) || count($lineParts) < 7) {
                    continue;
                }

                list($id, $day, $location, $clockIn, $clockOut, $minutes, $hours) = $lineParts;

                $id = $this->sanitizeString($id);
                $day = $this->sanitizeString(urldecode($day));
                $location = $this->sanitizeString(urldecode($location));
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
     * Sorts timesheet data by work week, organizing the log entries starting from Thursday
     * through the following Wednesday. Each day is sorted by timestamp, and the updated data
     * is written back to the log file.
     *
     * @param  string  $year  The year used to find the relevant timesheet log file. Defaults to an empty string.
     * @param  string  $week_number  The week number used to find the relevant timesheet log file. Defaults to an empty string.
     *
     * @return void
     */
    public function sortTimesheetDataByWorkWeek($year = '', $week_number = '')
    {
        $log = $this->findTimesheet($year, $week_number);
        // Set the work week starting at Thursday and ending at the next week Wednesday
        $workWeekOrder = ['4' => [],'5' => [], '6' => [], '0' => [], '1' => [], '2' => [], '3' => []];
        if ($log !== false) {
            $fileData = fopen($log, 'r');
            while (($line = fgets($fileData)) !== false) {
                $cleanLine = trim($line);
                $shiftDate = $this->findShiftDateTimeInLogLine($cleanLine);

                if ($shiftDate) {
                    $dayOfWeek = (string)$shiftDate->format('N');

                    if (isset($workWeekOrder[$dayOfWeek])) {
                        $timestampString = (string)$shiftDate->getTimestamp();
                        $workWeekOrder[$dayOfWeek][$timestampString] = $cleanLine;
                    }
                }
            }

            foreach ($workWeekOrder as $weekDayNumber => $value) {
                if (!empty($value) && is_array($value)) {
                    // sort the shifts by their timestamps
                    ksort($workWeekOrder[$weekDayNumber], SORT_NUMERIC);
                    // get rid of the timestamps since we don't need them anymore
                    $workWeekOrder[$weekDayNumber] = array_values($workWeekOrder[$weekDayNumber]);
                }
            }

            // flatten the array so we only have lines of shifts
            $workWeekOrder = array_merge(...array_values($workWeekOrder));


            if (!empty($workWeekOrder)) {
                $fileData = fopen($log, 'w');
                foreach ($workWeekOrder as $newShiftLine) {
                    fwrite($fileData, $newShiftLine . PHP_EOL);
                }
                fclose($fileData);
            }
        }
    }

    /**
     * Extract a DateTime object from a log line string if it contains a valid
     * DateTime formatted value based on the ATOM (ISO 8601) standard.
     *
     * @param  string  $shiftLine  The log line string to search for a DateTime value.
     *
     * @return \DateTime|null The extracted DateTime object if found, or null if no valid DateTime is detected.
     */
    public function findShiftDateTimeInLogLine(string $shiftLine)
    {
        $shiftLine = trim($shiftLine);
        $data = explode(' ', $shiftLine);
        foreach ($data as $key => $value) {
            try {
                $maybeDateTime = \DateTime::createFromFormat(\DateTime::ATOM, $value);
                if ($maybeDateTime instanceof \DateTime) {
                    return $maybeDateTime;
                }
            } catch (\Exception $e) {
                continue;
            }
        }
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
     * @param  string|int  $year  Optional. The year for which the timesheet is being searched.
     *  Defaults to internal property if empty or non-numeric.
     * @param  string|int  $week_number  Optional.
     *  The week number for which the timesheet is being searched. Defaults to internal property if empty or non-numeric.
     *
     * @return string|bool The path to the created or found timesheet file, or false on failure.
     */
    public function createTimesheet($baseDate = null)
    {
        $dates = $this->getThisWorkWeek($baseDate);
        $year = $dates['year'];
        $startDay = $dates['start_day'];
        $endDay = $dates['end_day'];

        // Determine the appropriate timesheet's workweek number based on the Thursday
        $findTimesheet = $this->findTimesheet($dates['year'], $dates['week_number']);
        if ($findTimesheet !== false) {
            return $findTimesheet;
        }

        // Ensure the directory for the year exists
        $this->createYearDirectory($year);

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
     * Adds a new entry to the timesheet file with the provided day, location, and clock-in/clock-out times.
     * Creates a new timesheet if none exists for the given base date.
     *
     * @param  string  $day  The day of the timesheet entry (e.g., "Monday", "2023-10-23").
     * @param  string  $location  The location where the time was logged.
     * @param  string  $clockIn  The clock-in time for the entry (e.g., "08:00").
     * @param  string  $clockOut  The clock-out time for the entry (e.g., "16:00").
     * @param  mixed  $baseDate  An optional base date to identify the timesheet file to add the entry to (null defaults to current date).
     *
     * @return bool True if the entry was successfully added to the timesheet, false otherwise.
     */
    public function addTimesheetEntry(string $day, string $location, string $clockIn, string $clockOut, $baseDate = null): bool
    {
        if ($baseDate) {
            $baseDate = $this->getDateTimeFromParam($baseDate);
            $workweek = $this->getThisWorkWeek($baseDate);
            $timesheet = $this->findTimesheet($workweek['year'], $workweek['week_number']);
        } else {
            $timesheet = $this->findTimesheet();
        }

        if (empty($timesheet)) {
            $timesheet = $this->createTimesheet($baseDate);
        }

        if ($timesheet !== false) {
            $dateTimeString = '';
            // If a base date was passed, copy the ATOM representation of that date
            if ($baseDate) {
                $dateTimeString = $baseDate->format(DATE_ATOM);
            }
            $file = fopen($timesheet, 'a');
            if ($file === false) {
                return false;
            }
            $verify = fwrite($file, $this->normalizeTimesheetEntry($day, $location, $clockIn, $clockOut, null, $dateTimeString) . PHP_EOL);

            if ($verify === false) {
                return false;
            } else {
                return true;
            }
        }

        return false;
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
    public function normalizeTimesheetEntry(string $day, string $location, string $clockIn, string $clockOut, null|string $id = null, null|string $dateTimeFormat = null): string
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
            $id = $this->generateEntryID();
        }

        return sprintf(
            "%s %s %s %s %s %d %f %s",
            $this->sanitizeKey($id),
            urlencode($day),
            urlencode($location),
            $normalizedClockIn,
            $normalizedClockOut,
            $totalMinutesWorked,
            $hoursMinutesPercentage,
            $dateTimeFormat ?: ''
        );
    }

    /**
     * Generate a unique entry ID by using a defined prefix and a unique identifier.
     *
     * @return string The generated unique entry ID.
     */
    public function generateEntryID(): string
    {
        return uniqid($this->uniqIdPrefix);
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
    public function createYearDirectory($directoryYear = null): bool
    {
        $directoryYear = is_numeric($directoryYear) ? $directoryYear : $this->year;
        $directory = $this->logDirectory . '/' . $directoryYear;

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

    /**
     * Sanitizes a string to ensure it is safe to use as a key.
     *
     * This function mimics the behavior of WordPress's sanitize_key function.
     * It converts the string to lowercase and removes unsafe characters,
     * allowing only alphanumeric characters, underscores, and dashes.
     *
     * @param string $key The key to sanitize.
     * @return string The sanitized key.
     */
    protected function sanitizeKey($key)
    {
        // Convert to lowercase
        $key = strtolower($key);

        // Remove all characters that are not a-z, 0-9, underscores, or dashes
        $key = preg_replace('/[^a-z0-9_\-]/', '', $key);

        return $key;
    }
}
