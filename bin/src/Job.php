<?php

declare(strict_types=1);

namespace rxnlabs\Timesheet;

/**
 * Represents a job with specific attributes and methods to manage shifts, locations,
 * and office hours while handling schedule-related functionality.
 */
class Job
{
    // Properties
    private string $name;
    public int $minimumHours;
    public int $maximumHours = 0;
    private string $shiftsPerDay;
    private bool $openWeekends;
    private string $shiftsStartMinute;
    private string $officeHours;
    private array $location;

    private array $shifts = [];
    public \DateTime $semeseterStartDate;

    private const ONE_HOUR_IN_SECONDS = 3600;
    private const MAX_CONSECUTIVE_SHIFT_HOURS = 4;

    // Constructor
    public function __construct(
        string $name,
        string $shiftsPerDay,
        bool $openWeekends,
        string $shiftsStartMinute,
        string $officeHours,
        array $location
    ) {
        $this->name              = $name;
        $this->shiftsPerDay      = $shiftsPerDay;
        $this->openWeekends          = $openWeekends;
        $this->shiftsStartMinute = $shiftsStartMinute;
        $this->officeHours       = $officeHours;
        $this->location          = $location;
        $this->semeseterStartDate = new \DateTime();
    }

    /**
     * Get the name of the job.
     *
     * @return string Returns the name as a string.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the range of hours for the current job.
     *
     * @return array Returns an array representing the hours range.
     */
    public function getHoursRange(): array
    {
        return $this->hoursRange;
    }

    /**
     * Retrieves the number of shifts per day for the current job.
     *
     * Some jobs have me working multiple shifts. Leaving and coming back in-between classes.
     *
     * @return string Returns the number of shifts assigned per day as a string.
     */
    public function getShiftsPerDay(): string
    {
        return $this->shiftsPerDay;
    }

    /**
     * Checks if the current job is open on weekends.
     *
     * @return bool Returns true if weekends are allowed, false otherwise.
     */
    public function isOpenWeekends(): bool
    {
        return $this->openWeekends;
    }

    /**
     * Retrieves the start minute of shifts. Some jobs have shifts that don't start on the exact hour.
     *
     * @return string The start minute of the shifts.
     */
    public function getShiftsStartMinute(): string
    {
        return $this->shiftsStartMinute;
    }

    /**
     * Retrieves the office hours for a given semester start date.
     *
     * This method calculates the office opening and closing times based on the office hours string
     * and the current semester's start date. It returns the opening and closing times as both
     * DateTime and Unix timestamp formats.
     *
     * @return array An associative array containing:
     *               - 'open_datetime': A DateTime object representing the office opening time.
     *               - 'close_datetime': A DateTime object representing the office closing time.
     *               - 'open_timestamp': A Unix timestamp for the office opening time.
     *               - 'close_timestamp': A Unix timestamp for the office closing time.
     */
    public function getOfficeHours(): array
    {
        [$officeOpen, $officeClose] = array_map('trim', explode(' - ', $this->officeHours));

        $officeOpenTimestamp = strtotime($officeOpen);
        $officeCloseTimestamp = strtotime($officeClose);

        // Get the date of the office hours according to the current semester.
        $officeOpenDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->semeseterStartDate->format('Y-m-d') . ' ' . date('H:i:s', $officeOpenTimestamp)
        );
        $officeCloseDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $this->semeseterStartDate->format('Y-m-d') . ' ' . date('H:i:s', $officeCloseTimestamp)
        );

        $officeOpenTimestamp = $officeOpenDateTime->getTimestamp();
        $officeCloseTimestamp = $officeCloseDateTime->getTimestamp();

        return [
            'open_datetime' => $officeOpenDateTime,
            'close_datetime' => $officeCloseDateTime,
            'open_timestamp' => $officeOpenTimestamp,
            'close_timestamp' => $officeCloseTimestamp
        ];
    }

    /**
     * Determines if a given shift occurs after the office closing hours.
     *
     * This method checks whether the timestamp of the shift falls after the configured
     * office closing time. It compares the shift's hour against the office's closing hour
     * converted into a comparable format.
     *
     * @param  int  $shiftTimestamp  The Unix timestamp of the shift to be evaluated.
     *
     * @return bool Returns true if the shift occurs after office closing hours, otherwise false.
     */
    public function isShiftAfterHours($shiftTimestamp)
    {
        $officeOpenTimestamp = $this->getOfficeHours()['close_datetime'];
        $officeCloseHour = (int)$officeOpenTimestamp->format('His');
        $shiftTimestampDateTime = new \DateTime();
        $shiftTimestampDateTime->setTimestamp($shiftTimestamp);
        $shiftHour = (int)$shiftTimestampDateTime->format('His');

        if ($shiftHour >= $officeCloseHour) {
            return true;
        }

        return false;
    }

    /**
     * Determines whether a given shift timestamp occurs before the office opening hours.
     *
     * This method checks the shift's time against the office's opening time to ascertain
     * if the shift starts prior to the office being open.
     *
     * @param  int  $shiftTimestamp  The Unix timestamp representing the start time of the shift.
     *
     * @return bool Returns true if the shift occurs before the office's opening hour, otherwise false.
     */
    public function isShiftBeforeHours($shiftTimestamp)
    {
        $officeOpenTimestamp = $this->getOfficeHours()['open_datetime'];
        $officeOpenHour = (int)$officeOpenTimestamp->format('His');
        $shiftTimestampDateTime = new \DateTime();
        $shiftTimestampDateTime->setTimestamp($shiftTimestamp);
        $shiftHour = (int)$shiftTimestampDateTime->format('His');

        if ($shiftHour < $officeOpenHour) {
            return true;
        }

        return false;
    }

    /**
     * Retrieves the location information.
     *
     * @return array An array containing the location details.
     */
    public function getLocation(): array
    {
        return $this->location;
    }

    /**
     * Sets the location for the current instance.
     *
     * This method updates the internal location property with the provided location data.
     *
     * @param  array  $location  An array containing the location details to be set.
     *                        The structure and format of the array depend on the specific use case or implementation.
     *
     * @return void
     */
    public function setLocation(array $location)
    {
        $this->location = $location;
    }

    /**
     * Removes a specific location from the list of stored locations.
     *
     * This method searches for a given location in the location list and removes it
     * if found. If the location does not exist in the list, no action is taken.
     *
     * @param  string  $location  The name of the location to be removed.
     *
     * @return void
     */
    public function removeLocation(string $location)
    {
        $key = array_search($location, $this->location);
        if ($key !== false) {
            unset($this->location[$key]);
        }
    }

    /**
     * Adds a new location to the list of locations if it does not already exist.
     *
     * This method checks whether the provided location is already in the list of locations
     * and adds it only if it is not present, ensuring no duplicates in the list.
     *
     * @param  string  $location  The location to be added.
     *
     * @return void
     */
    public function addLocation(string $location)
    {
        if (! in_array($location, $this->location)) {
            $this->location[] = $location;
        }
    }

    /**
     * Retrieves the list of shifts.
     *
     * @return array An array containing the shifts.
     */
    public function getShifts(): array
    {
        return $this->shifts;
    }

    /**
     * Adds one or multiple shifts to the existing list of shifts.
     *
     * @param  mixed  $shifts  A single shift or an array of shifts to be added.
     *
     * @return void
     */
    public function addShifts($shifts)
    {
        if (is_array($shifts)) {
            $this->shifts = array_merge($this->shifts, $shifts);
        }

        $this->shifts[] = $shifts;
    }

    /**
     * This sets the semester start date time for generating timestamps related to this job.
     *
     * These timestamps are used to generate the list of available weekly shifts per location and the generated list of weekly shifts.
     *
     * @param  \DateTime  $semesterStartDate
     *
     * @return void
     */
    public function setSemesterStartDate(\DateTime $semesterStartDate)
    {
        $this->semeseterStartDate = $semesterStartDate;
    }

    /**
     * Retrieves the start date of the semester.
     *
     * This method returns the semester's starting date as a DateTime object.
     *
     * @return \DateTime The start date of the semester.
     */
    public function getSemesterStartDate(): \DateTime
    {
        return $this->semeseterStartDate;
    }

    /**
     * Converts weekly shifts data into an array of start and end timestamps.
     *
     * @param  array  $weeklyShifts  An associative array of weekly shifts, where each day's shifts are provided as input.
     *
     * @return array Returns an array of formatted shifts, each containing start and end timestamps.
     */
    public function formatWeeklyShiftsAsTimestamps(array $weeklyShifts): array
    {
        $weeklyShiftsTimestamps = [];
        foreach ($weeklyShifts as $day => $dayShifts) {
            foreach ($dayShifts['shifts'] as $shift) {
                $weeklyShiftsTimestamps[] = [
                    'start_timestamp' => Job::getShiftStartTimestamp($shift),
                    'end_timestamp'   => Job::getShiftEndTimestamp($shift)
                ];
            }
        }

        return $weeklyShiftsTimestamps;
    }

    /**
     * Determines whether a given shift timestamp conflicts with any unavailable shifts.
     *
     * @param  int  $newShiftTimestamp  The timestamp of the shift to be checked for conflicts.
     * @param  array  $notAvailableShifts  An array of unavailable shifts to compare against.
     *
     * @return bool Returns true if there is a conflict, false otherwise.
     */
    public function doesShiftConflict(int $newShiftTimestamp, array $notAvailableShifts): bool
    {
        foreach ($notAvailableShifts as $shift) {
            // check if the possible shift is within range of an existing shift for the day
            // if so, then we have a conflict
            // check if the possible shift is within range of an existing shift for the day
            // if so, then we have a conflict
            $startTimestamp = Job::getShiftStartTimestamp($shift);
            $endTimestamp   = Job::getShiftEndTimestamp($shift);

            // if the shift has no start or end time, lets assume the new does conflict
            if (empty($startTimestamp) || empty($endTimestamp)) {
                return true;
            }

            if ($this->isShiftBeforeHours($newShiftTimestamp) || $this->isShiftAfterHours($newShiftTimestamp)) {
                return true;
            }

            if ($newShiftTimestamp >= $startTimestamp && $newShiftTimestamp <= $endTimestamp) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generates all possible shifts within the specified office hours for each location and day of the week.
     *
     * The method calculates possible shifts by iterating through each hour within the office hours for all specified days
     * and locations. If weekends are enabled, Saturday and Sunday are included in the days of the week.
     *
     * @return array An associative array where the keys are days of the week, the values are arrays of locations,
     * and each location contains a list of possible shift starting times in Unix timestamp format.
     */
    public function generateAllPossibleWeeklyShifts($notAvailableShifts = []): array
    {
        $shifts = [];
        $officeOpenTimestamp = $this->getOfficeHours()['open_timestamp'];
        $officeCloseTimestamp = $this->getOfficeHours()['close_timestamp'];
        $officeOpenDateTime = $this->getOfficeHours()['open_datetime'];

        // Get the closest monday either to today or to the start of the semester since we start calculating the available
        //shifts starting from Monday since that is how we think of a work week normally
        $dayOfWeek = $officeOpenDateTime->format('w');

        switch ($dayOfWeek) {
            case 0:
                $officeOpenDateTime->modify('+1 days');
                break;
            case 2:
                $officeOpenDateTime->modify('-1 days');
                break;
            case 3:
                $officeOpenDateTime->modify('-2 days');
                break;
            case 4:
                $officeOpenDateTime->modify('-3 days');
                break;
            case 5:
                $officeOpenDateTime->modify('-4 days');
                break;
            case 6:
                $officeOpenDateTime->modify('-5 days');
                break;
            default:
                $officeOpenDateTime->modify('monday this week');
        }

        $officeCloseDateTime = \DateTime::createFromFormat(
            'Y-m-d H:i:s',
            $officeOpenDateTime->format('Y-m-d') . ' ' . date('H:i:s', $officeCloseTimestamp)
        );
        $officeOpenTimestamp = $officeOpenDateTime->getTimestamp();
        $officeCloseTimestamp = $officeCloseDateTime->getTimestamp();

        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
        if ($this->isOpenWeekends()) {
            $daysOfWeek = array_merge($daysOfWeek, ['Saturday', 'Sunday']);
        }

        if (!empty($notAvailableShifts)) {
            $isStoredByDay = false;
            foreach ($daysOfWeek as $day) {
                if (!isset($notAvailableShifts[$day])) {
                    continue;
                }

                $isStoredByDay = true;
                break;
            }

            if ($isStoredByDay) {
                $flatNotAvailableShifts = [];
                foreach ($notAvailableShifts as $day => $shifts) {
                    $flatNotAvailableShifts = array_merge($flatNotAvailableShifts, $notAvailableShifts[$day]);
                }
                $notAvailableShifts = $flatNotAvailableShifts;
            }
        }

        foreach ($daysOfWeek as $day) {
            foreach ($this->location as $location) {
                $possibleShiftTime = $officeOpenTimestamp;
                do {
                    if ($possibleShiftTime >= $officeCloseTimestamp) {
                        break;
                    }

                    // make sure that the list of available shifts does not include shifts that cannot be worked
                    // because we are busy
                    $shiftConflict = $this->doesShiftConflict($possibleShiftTime, $notAvailableShifts);

                    // try the next hour to see if that is available
                    if ($shiftConflict === true && $possibleShiftTime < $officeCloseTimestamp) {
                        $possibleShiftTime = $possibleShiftTime + 3600;
                        continue;
                    }

                    $shifts[$day][$location][] = $possibleShiftTime;
                    // add 1 hour until we reach the closing time
                    $possibleShiftTime = $possibleShiftTime + 3600;
                } while ($possibleShiftTime < $officeCloseTimestamp);
            }

            // Adjust the start and end time by one day
            $officeOpenDateTime->modify('+1 days');
            $officeCloseDateTime = \DateTime::createFromFormat(
                'Y-m-d H:i:s',
                $officeOpenDateTime->format('Y-m-d') . ' ' . date('H:i:s', $officeCloseTimestamp)
            );
            $officeOpenTimestamp = $officeOpenDateTime->getTimestamp();
            $officeCloseTimestamp = $officeCloseDateTime->getTimestamp();
        }

        return $shifts;
    }

    /**
     * Generates a week's work shifts based on office hours, maximum and minimum weekly hours,
     * and available slots within each location for each day of the week.
     *
     * This method considers shifts per day (including single or multiple shifts), ensures the
     * total shift hours do not exceed the weekly maximum hours, and restricts consecutive shifts
     * to a maximum of 4 hours. Shifts are spread across available locations and slots based
     * on predefined configurations and random selection.
     *
     * @return array An array containing generated shifts grouped by day, where each day
     * includes detailed shift information such as location, start time, and end time.
     */
    public function generateWeeklyShifts($alreadyCalculatedPossibleShifts = []): array
    {
        $shiftsPerDay = $this->getShiftsPerDay() === 'multiple' ? rand(1, 4) : 1;
        if (! empty($alreadyCalculatedPossibleShifts)) {
            $allPossibleShifts = $alreadyCalculatedPossibleShifts;
        } else {
            $allPossibleShifts = $this->generateAllPossibleWeeklyShifts();
        }

        $daysOfWeek = array_keys($allPossibleShifts);
        // Generate shifts
        $shifts          = [];
        $totalShiftHours = 0;
        // keep track of the shifts we have assigned so we can avoid overlap
        $allShiftsAssignedPerDay = [];
        do {
            // start assigning shifts in any order to increase randomness of shifts
            shuffle($daysOfWeek);
            foreach ($daysOfWeek as $day) {
                if (('saturday' === strtolower($day) || 'sunday' === strtolower($day)) && !$this->isOpenWeekends()) {
                    continue;
                }

                if (!isset($shifts[$day])) {
                    $shifts[$day] = ['shifts' => []];
                }

                if (! isset($allShiftsAssignedPerDay[$day])) {
                    $allShiftsAssignedPerDay[$day] = [];
                }
                $dayShifts = [];
                for ($i = 0; $i < $shiftsPerDay; $i++) {
                    if ($totalShiftHours >= $this->maximumHours) {
                        break;
                    }
                    $allPossibleShiftsForDay = $allPossibleShifts[$day];
                    $location                = array_rand($allPossibleShiftsForDay, 1);
                    $slotsAvailable          = [];
                    $slotsAvailable  = array_filter($allPossibleShiftsForDay[$location], function ($value) {
                        // look for slots where the values are not empty, meaning these slots can be filled
                        if (! empty($value)) {
                            return $value;
                        }
                    });
                    $randomStartTime = 0;
                    do {
                        shuffle($slotsAvailable);
                        $randomStartTimeIndex = array_rand($slotsAvailable, 1);
                        $randomStartTime      = $slotsAvailable[$randomStartTimeIndex];
                        // if we have assigned a time that is already being worked (i.e. we are working at the different location), remove this time from the list of available slots
                        if (in_array($randomStartTime, $allShiftsAssignedPerDay[$day])) {
                            unset($slotsAvailable[$randomStartTimeIndex]);
                            $slotsAvailable = array_values($slotsAvailable);
                        }
                    } while (in_array($randomStartTime, $allShiftsAssignedPerDay[$day]));
                    $shiftHours = [$randomStartTime];
                    $lastSlot   = end($allPossibleShiftsForDay[$location]);
                    // check if the random start time is the same time as the last slot.
                    // if its the last slot, then we cant select the shifts in between
                    if ($lastSlot !== $randomStartTime && is_numeric($randomStartTime)) {
                        $nextHour = $randomStartTime + 3600;
                        do {
                            // dont add any more shifts if we exceed past the max hours per week
                            if ($totalShiftHours >= $this->maximumHours) {
                                break;
                            }
                            // make sure that the next hour is available and make sure that we are not already working at
                            // that time
                            if (
                                in_array($nextHour, $slotsAvailable, true) &&
                                ! in_array($nextHour, $allShiftsAssignedPerDay[$day]) &&
                                !$this->doesShiftConflict($nextHour, $shifts[$day]['shifts'])
                            ) {
                                $shiftHours[] = $nextHour;
                                // search for the next hour
                                $nextHour = $nextHour + 3600;
                                continue;
                            }
                            // if the next hour is not available, then stop
                            break;
                        } while (count($shiftHours) < 4); // we cannot work more than 4 hours in a row
                    }
                    // empty the shifts from being available in the list of shifts but keep the index
                    // this helps to keep the shifts from being assigned again
                    foreach ($allPossibleShifts[$day][$location] as $index => $value) {
                        if (in_array($value, $shiftHours)) {
                            $allShiftsAssignedPerDay[$day][]            = $value;
                            $allPossibleShifts[$day][$location][$index] = '';
                        }
                    }
                    $totalShiftHours  += count($shiftHours);
                    $shift = $this->formatShift($location, $shiftHours);

                    if (!empty($shift)) {
                        $dayShifts[] = $shift;
                    }
                }

                $shifts[$day]['shifts'] = array_merge($shifts[$day]['shifts'], $dayShifts);
            }
        } while ($totalShiftHours < $this->minimumHours);

        // Sort and store the shifts by the day of the week in the weekday order.
        $dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        uksort($shifts, function ($a, $b) use ($dayOrder) {
            return array_search($a, $dayOrder) <=> array_search($b, $dayOrder);
        });

        $this->shifts = $shifts;
        return $this->getShifts();
    }

    /**
     * Selects a random shift from the available shifts based on the criteria of available hours and locations.
     *
     * This method determines all possible shifts from the available weekly shifts, validates against the
     * provided unavailable shifts, and randomly selects a valid shift location and time slot.
     * The resulting shift will include a start time, end time, and location details.
     *
     * @param  array  $notAvailableShifts  An array of specific shifts that are not available for assignment.
     *                                  Expected to include day, start timestamp, and end timestamp details.
     *
     * @return array The randomly selected shift details including:
     *               - 'location': The selected location for the shift.
     *               - 'start': Start time of the shift in "h:i A" format.
     *               - 'end': End time of the shift in "h:i A" format.
     *               - 'start_timestamp': Start time of the shift as a Unix timestamp.
     *               - 'end_timestamp': End time of the shift as a Unix timestamp.
     *               Returns an empty array if no available shifts are found.
     */
    public function selectRandomShiftFromAvailableShifts(array $notAvailableShifts = [], string|null $byDay = null): array
    {
        $shift                   = [];
        $notAvailableShiftsClone = $notAvailableShifts;
        if (empty($notAvailableShiftsClone)) {
            foreach ($this->shifts as $day => $shifts) {
                foreach ($shifts['shifts'] as $shift) {
                    $notAvailableShiftsClone[$day][] = [
                        'start_timestamp' => Job::getShiftStartTimestamp(['start_timestamp']),
                        'end_timestamp'   => Job::getShiftEndTimestamp($shift['end_timestamp'])
                    ];
                }
            }
        }

        $possibleShifts = $this->generateAllPossibleWeeklyShifts($notAvailableShiftsClone);
        if (empty($possibleShifts)) {
            return $shift;
        }

        $noShiftsAvailableInLocations = [];

        foreach ($possibleShifts as $day => $locations) {
            if (('saturday' === strtolower($day) || 'sunday' === strtolower($day)) && !$this->isOpenWeekends()) {
                continue;
            }

            if (! empty($byDay) && isset($possibleShifts[$byDay])) {
                $day = $byDay;
            }

            $allPossibleShiftsForDay = $possibleShifts[$day];
            // select a random location from the list of possible shifts
            $possibleLocations = array_filter($this->getLocation(), function ($location) use ($noShiftsAvailableInLocations) {
                if (!in_array($location, $noShiftsAvailableInLocations)) {
                    return $location;
                }
            });

            // If none of the locations have any available shifts because we checked all available shifts, bail
            if (empty($possibleLocations)) {
                return [];
            }
            shuffle($possibleLocations);
            $location       = $possibleLocations[0];
            $slotsAvailable = array_filter($allPossibleShiftsForDay[$location], function ($value) {
                // look for slots where the values are not empty, meaning these slots can be filled
                if (!empty($value)) {
                    return $value;
                }
            });

            if (empty($slotsAvailable)) {
                // keep track of the locations with no shifts that we have already checked to prevent an infinite loop
                $noShiftsAvailableInLocations[] = $location;
                continue;
            }

            shuffle($slotsAvailable);
            $randomStartTimeIndex = array_rand($slotsAvailable, 1);
            $randomStartTime      = $slotsAvailable[$randomStartTimeIndex];
            $shiftHours = [];
            $lastSlot   = end($allPossibleShiftsForDay[$location]);
            // check if the random start time is the same time as the last slot.
            // if its the last slot, then we cant select the shifts in between
            if ($lastSlot !== $randomStartTime && is_numeric($randomStartTime) && $this->doesShiftConflict($randomStartTime, $notAvailableShiftsClone) !== true) {
                $shiftHours = [$randomStartTime];
                $nextHour = $randomStartTime + 3600;
                do {
                    // make sure that the next hour is available and
                    // make sure that we are not already working at that time
                    if (!$this->doesShiftConflict($nextHour, $notAvailableShiftsClone) !== true && in_array($nextHour, $slotsAvailable, true)) {
                        $shiftHours[] = $nextHour;
                        // search for the next hour
                        $nextHour = $nextHour + 3600;
                        continue;
                    }
                    // if the next hour is not available, then stop
                    break;
                } while (count($shiftHours) < 4); // we cannot work more than 4 hours in a row
                $shift = $this->formatShift($location, $shiftHours);
            }

            if (!empty($shift)) {
                break;
            }
        }
        return $shift;
    }

    public function selectRandomShiftFromAvailableShiftsForDay($day, array $notAvailableShifts = []): array
    {
        return $this->selectRandomShiftFromAvailableShifts($notAvailableShifts, $day);
    }

    /**
     * Formats shift details including location, start time, and end time.
     *
     * @param  string  $location  The location of the shift.
     * @param  array  $shiftHours  An array containing the shift's start and end timestamps.
     *
     * @return array Returns an associative array containing formatted shift details, including location, start time, end time, and original timestamps.
     */
    private function formatShift(string $location, array $shiftHours): array
    {
        $firstHourInShift = $shiftHours[0];
        $lastHourInShift = end($shiftHours);

        if (is_numeric($firstHourInShift) && is_numeric($lastHourInShift)) {
            // if the first hour and the last hour are the same, that means that this is a 1 hour shift
            if ($firstHourInShift === $lastHourInShift) {
                $lastHourInShift = $lastHourInShift + 3600;
            }

            return [
                'location'        => $location,
                'start'           => date('h:i A', $firstHourInShift),
                'end'             => date('h:i A', $lastHourInShift),
                'start_timestamp' => $firstHourInShift,
                'end_timestamp'   => $lastHourInShift,
            ];
        }

        return [];
    }

    /**
     * Retrieves the start timestamp of a given shift.
     *
     * @param  array  $shift  An associative array representing the shift, which may include a 'start_timestamp' key.
     *
     * @return int|null Returns the shift's start timestamp as an integer if it exists, or null if not present.
     */
    public static function getShiftStartTimestamp(array $shift): int|null
    {
        if (isset($shift['start_timestamp'])) {
            return $shift['start_timestamp'];
        }

        return null;
    }

    /**
     * Retrieves the shift end timestamp from the provided shift data.
     *
     * @param  array  $shift  The array containing shift details, where 'end_timestamp' may be defined.
     *
     * @return int|null Returns the end timestamp as an integer if present, or null if not set.
     */
    public static function getShiftEndTimestamp(array $shift): int|null
    {
        if (isset($shift['end_timestamp'])) {
            return $shift['end_timestamp'];
        }

        return null;
    }
}
