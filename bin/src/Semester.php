<?php

declare(strict_types=1);

namespace rxnlabs\Timesheet;

/**
 * Class Semester
 *
 * Represents a semester, including details about its name, start and end dates,
 * closed days, jobs, weekly shifts, and all semester shifts. Provides functionalities
 * to manage jobs, check schedule constraints, generate weekly shifts for jobs, and
 * create a semester-wide shift schedule while considering closures and conflicts.
 */
class Semester
{
    // Properties
    private string $name;
    private string $startDate;
    private string $endDate;
    private array $closedDays;
    private array $jobs;

    private array $weeklyShifts = [];
    private array $allSemesterShifts = [];

    // Constructor to initialize a semester
    public function __construct(
        string $name,
        string $startDate,
        string $endDate,
        array $closedDays = []
    ) {
        $this->name       = $name;
        $this->startDate  = $startDate;
        $this->endDate    = $endDate;
        $this->closedDays = $closedDays;
    }

    /**
     * Get the name of the semester. Usually in the format of Season + Year (e.g. Fall 2007))
     *
     * @return string The name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Retrieves the start date for the semester.
     *
     * @return string The start date.
     */
    public function getStartDate(): string
    {
        return $this->startDate;
    }

    /**
     * Retrieves the end date for the semester, when the last class ends and before graduation/commencement.
     *
     * @return string The end date.
     */
    public function getEndDate(): string
    {
        return $this->endDate;
    }

    /**
     * Retrieves the closed days. These are teh days that the jobs at the campus is closed.
     *
     * @return array An array containing the closed days.
     */
    public function getClosedDays(): array
    {
        return $this->closedDays;
    }

    /**
     * Retrieves all jobs.
     *
     * @return array An array containing all jobs.
     */
    public function getJobs(): array
    {
        return $this->jobs;
    }

    /**
     * Retrieves the weekly shifts.
     *
     * @return array An array containing the weekly shifts.
     */
    public function getWeeklyShifts(): array
    {
        return $this->weeklyShifts;
    }

    /**
     * Sets the weekly shifts.
     *
     * @param  array  $weeklyShifts  An array containing the weekly shift details.
     *
     * @return void
     */
    public function setWeeklyShifts(array $weeklyShifts): void
    {
        $this->weeklyShifts = $weeklyShifts;
    }

    /**
     * Retrieves all semester shifts.
     *
     * @return array An array containing all semester shifts.
     */
    public function getAllSemesterShifts(): array
    {
        return $this->allSemesterShifts;
    }

    /**
     * Checks if the given date falls within the defined semester duration.
     *
     * @param  string  $date  The date to be checked, in a parsable string format.
     *
     * @return bool True if the date is within the semester, false otherwise.
     */
    public function isWithinSemester(string $date): bool
    {
        $start       = strtotime($this->startDate);
        $end         = strtotime($this->endDate);
        $currentDate = strtotime($date);

        return $currentDate >= $start && $currentDate <= $end;
    }


    /**
     * Determines if a given date falls within a closed period or matches a specific closed date.
     *
     * @param  string  $date  The date to check, formatted as a string (e.g., 'YYYY-MM-DD').
     *
     * @return bool Returns true if the date is within a closed period or matches a closed date; otherwise, false.
     */
    public function isClosed(string $date): bool
    {
        foreach ($this->getClosedDays() as $closedPeriod) {
            if (strpos($closedPeriod, '-') !== false) {
                [$start, $end] = array_map('trim', explode('-', $closedPeriod));
                if (strtotime($date) >= strtotime($start) && strtotime($date) <= strtotime($end)) {
                    return true;
                }
            } elseif (strtotime($date) === strtotime($closedPeriod)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Adds one or more Job instances to the existing list of jobs.
     *
     * @param  Job  $job  A Job instance to be added.
     *
     * @return void
     */
    public function addJob(Job $job): void
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            if ($arg instanceof Job) {
                $this->jobs[] = $arg;
            }
        }
    }

    /**
     * Adjusts the start date of all associated jobs to match the semester start date.
     * Updates each job's start date to align with the semester's configured
     * start date format.
     *
     * This is used to make sure that the list of available generated shifts match the dates
     * of the current semester.
     *
     * @return void
     */
    public function adjustJobsStartDate($baseDate = null)
    {
        $jobs = $this->getJobs();
        foreach ($jobs as $job) {
            if ($baseDate instanceof \DateTime) {
                $job->setSemesterStartDate($baseDate);
            } else {
                $job->setSemesterStartDate(\DateTime::createFromFormat('m/d/Y', $this->getStartDate()));
            }
        }
    }

    /**
     * Generates weekly shifts for all jobs by iterating through each job, calculating possible
     * shifts while avoiding scheduling conflicts, and consolidating the shifts into a weekly structure.
     *
     * @return array An associative array where each key represents a day, and the value contains
     *               information about the shifts scheduled for that day.
     */
    public function generateWeeklyShiftsForJobs()
    {
        $shifts                = [];
        $alreadyAssignedShifts = [];
        foreach ($this->getJobs() as $job) {
            if (!$job instanceof Job) {
                continue;
            }

            $possibleShifts  = $job->generateAllPossibleWeeklyShifts($alreadyAssignedShifts);
            $generatedShifts = $job->generateWeeklyShifts($possibleShifts);

            foreach ($generatedShifts as $day => $dayShifts) {
                if (! isset($shifts[$day])) {
                    $shifts[$day] = ['shifts' => []];
                }

                $shifts[$day]['shifts'] = array_merge($shifts[$day]['shifts'], $dayShifts['shifts']);

                // take all the shifts that were generated and add them to the list of already assigned shifts
                // so we can use it next time to generate the list of possible shifts for the next job to avoid conflicts
                foreach ($dayShifts['shifts'] as $shift) {
                    $alreadyAssignedShifts[] = [
                        'start_timestamp' => Job::getShiftStartTimestamp($shift),
                        'end_timestamp'   => Job::getShiftEndTimestamp($shift)
                    ];
                }
            }
        }

        $this->setWeeklyShifts($shifts);
        return $this->getWeeklyShifts();
    }

    /**
     * Adjusts the weekly shifts to the next week by modifying the date and time for each shift.
     *
     * The method retrieves the current weekly shifts, increments the datetime for each shift by one week,
     * and updates the start and end timestamps accordingly. The updated shifts are then set back.
     *
     * @return void
     */
    public function adjustWeeklyShiftsToNextWeek()
    {
        $shifts = $this->getWeeklyShifts();
        foreach ($shifts as $day => $dayShifts) {
            foreach ($dayShifts['shifts'] as $index => $shift) {
                $nextWeekShiftStartDateTime = new \DateTime();
                $nextWeekShiftStartDateTime->setTimestamp(Job::getShiftStartTimestamp($shift));
                $nextWeekShiftStartDateTime->modify('+1 week');
                $nextWeekShiftEndDateTime = new \DateTime();
                $nextWeekShiftEndDateTime->setTimestamp(Job::getShiftEndTimestamp($shift));
                $nextWeekShiftEndDateTime->modify('+1 week');
                $shifts[$day]['shifts'][$index]['start_timestamp'] = $nextWeekShiftStartDateTime->getTimestamp();
                $shifts[$day]['shifts'][$index]['end_timestamp']   = $nextWeekShiftEndDateTime->getTimestamp();
            }
        }

        $this->setWeeklyShifts($shifts);
    }

    /**
     * Resets the weekly shifts to align with the beginning of the semester.
     *
     * This method adjusts the start and end timestamps of weekly shifts based on
     * the start date of the semester and ensures that shifts fall on the correct
     * day of the week within the semester's first week. Existing weekly shifts are
     * updated accordingly. This is done after we run adjustWeeklyShiftsToNextWeek to reset to the
     * original dates.
     *
     * @return void
     */
    public function resetWeeklyShiftsToSemesterStart()
    {
        $shifts = $this->getWeeklyShifts();
        $matchWorkWeekDayOfWeek = \DateTime::createFromFormat('m/d/Y', $this->getStartDate());
        $matchWorkWeekDayOfWeek->modify('monday this week');
        $allowedDaysofWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($shifts as $day => $dayShifts) {
            if (in_array(strtolower($day), $allowedDaysofWeek, true)) {
                $matchWorkWeekDayOfWeek->modify("{$day} this week");
            }

            foreach ($dayShifts['shifts'] as $index => $shift) {
                $nextWeekShiftStartDateTime = new \DateTime();
                $nextWeekShiftStartDateTime->setTimestamp(Job::getShiftStartTimestamp($shift));
                $nextWeekShiftStartDateTime->setDate((int)$matchWorkWeekDayOfWeek->format('Y'), (int)$matchWorkWeekDayOfWeek->format('m'), (int)$matchWorkWeekDayOfWeek->format('d'));
                $nextWeekShiftEndDateTime = new \DateTime();
                $nextWeekShiftEndDateTime->setTimestamp(Job::getShiftEndTimestamp($shift));
                $nextWeekShiftEndDateTime->setDate((int)$matchWorkWeekDayOfWeek->format('Y'), (int)$matchWorkWeekDayOfWeek->format('m'), (int)$matchWorkWeekDayOfWeek->format('d'));
                $shifts[$day]['shifts'][$index]['start_timestamp'] = $nextWeekShiftStartDateTime->getTimestamp();
                $shifts[$day]['shifts'][$index]['end_timestamp']   = $nextWeekShiftEndDateTime->getTimestamp();
            }
        }

        $this->setWeeklyShifts($shifts);
    }

    /**
     * Generates shifts for an entire semester, considering weekly shifts, campus closures,
     * and potential modifications (e.g., dropping or picking up shifts).
     *
     * Iterates through all dates within the semester's start and end dates, assigning shifts
     * for each week and day. Shifts may be modified randomly by either dropping existing
     * shifts or picking up new ones. The final schedule is returned as an array of shifts
     * organized by weeks and days.
     *
     * @return array The complete schedule for the semester structured by weeks, where each week
     *               contains daily shifts and their associated details.
     */
    public function generateSemesterShifts(): array
    {
        $allShifts   = []; // To hold all shifts for the semester
        $weeklyHours = []; // To track hours worked per week
        $currentDate = strtotime($this->getStartDate()); // Start from the semester start date
        $endDate     = strtotime($this->getEndDate()); // Semester end date

        // Iterate through all dates from start to end of semester
        while ($currentDate <= $endDate) {
            $currentWeek                  = date('W', $currentDate); // Get the week number of the current day
            $currentDateStr               = date('Y-m-d', $currentDate); // Format the date
            $currentDateTime              = \DateTime::createFromFormat('Y-m-d', $currentDateStr);
            $thisWeeksAssignedShiftTimes = [];
            $thisWeeksAssignedShiftsByDay = [];
            $totalWeeklyHours             = 0;

            if (! isset($allShifts[$currentWeek])) {
                $allShifts[$currentWeek] = [];
            }

            foreach ($this->getWeeklyShifts() as $day => $dayShifts) {
                // if there are no shifts for the day, don't add empty shifts.
                if (empty($dayShifts['shifts'])) {
                    continue;
                }

                // if the campus is closed, then the jobs are closed, so don't add any shifts for this day.
                if ($this->isClosed($currentDateStr)) {
                    continue;
                }

                foreach ($dayShifts['shifts'] as $shift) {
                    $thisWeeksAssignedShiftTimes[] = [
                        'start_timestamp' => Job::getShiftStartTimestamp($shift),
                        'end_timestamp'   => Job::getShiftEndTimestamp($shift)
                    ];
                    $totalWeeklyHours += (Job::getShiftEndTimestamp($shift) - Job::getShiftStartTimestamp($shift)) / 3600;
                }

                $thisWeeksAssignedShiftsByDay[$day]             = $dayShifts;
                $thisWeeksAssignedShiftsByDay[$day]['datetime'] = $currentDateTime;
                $currentDateTime->modify('+1 day');
            }

            // after we know this week's schedule, lets decide if we should modify it
            // we need to know the entire schedule to prevent assigning shifts that could conflict
            foreach ($thisWeeksAssignedShiftsByDay as $day => $dayShiftsData) {
                $modifyShifts = rand(0, 1);
                // Decide if we should modify the list of shifts this week.
                if ($modifyShifts) {
                    $dropShiftOrPickUpShift = rand(0, 1); // 0 = Drop shift. 1 = Pick up shift

                    if ($dropShiftOrPickUpShift === 0) {
                        $shiftToDropIndex = array_rand($dayShiftsData['shifts']);
                        unset($dayShiftsData['shifts'][$shiftToDropIndex]);

                        // if after dropping a shift, check if the shifts for the day are empty
                        if (empty($dayShiftsData['shifts'])) {
                            continue;
                        }
                    } else {
                        $shiftToPickUp = $this->pickUpShift($thisWeeksAssignedShiftTimes, $day);

                        if (!empty($shiftToPickUp)) {
                            $thisWeeksAssignedShiftTimes[] = [
                                'start_timestamp' => Job::getShiftStartTimestamp($shiftToPickUp),
                                'end_timestamp'   => Job::getShiftEndTimestamp($shiftToPickUp)
                            ];
                            $totalWeeklyHours += (Job::getShiftEndTimestamp($shiftToPickUp) - Job::getShiftStartTimestamp($shiftToPickUp)) / 3600;
                            $dayShiftsData['shifts'][] = $shiftToPickUp;
                        }
                    }
                }

                $allShifts[$currentWeek][$day]             = $dayShiftsData;
                $allShifts[$currentWeek][$day]['datetime'] = $currentDateTime;
            }

            // Decide if we work try to pick up 40 hours or more this week.
            // If we work more than 40 hours, we get paid overtime but should avoid doing this for reasons.
            $shouldGo40 = rand(0, 1); // 0 = Dont work 40 hours. 1 = Work 40 hours or more.
            // keep a count of all the days where a random shift is not available
            // if we have looked through all the days and have not found shifts on all days, then use this to stop the do while loop
            // if it hits at least 7, then we have not found at least 7 random shifts
            $noRandomShiftsAvailableDays = 0;
            if ($shouldGo40 && $totalWeeklyHours < 40) {
                do {
                    foreach ($thisWeeksAssignedShiftsByDay as $day => $dayShiftsData) {
                        $newShifts = $this->pickUpShift($thisWeeksAssignedShiftTimes, $day);
                        $shiftDateTime = $dayShiftsData['datetime'];

                        if (!empty($newShifts)) {
                            $thisWeeksAssignedShiftTimes[] = [
                                'start_timestamp' => Job::getShiftStartTimestamp($newShifts),
                                'end_timestamp'   => Job::getShiftEndTimestamp($newShifts)
                            ];
                            $totalWeeklyHours += (Job::getShiftEndTimestamp($newShifts) - Job::getShiftStartTimestamp($newShifts)) / 3600;
                            $allShifts[$currentWeek][$day]['shifts'][] = $newShifts;
                            $allShifts[$currentWeek][$day]['datetime'] = $shiftDateTime;

                            // break the foreach loop which will also cause the do...while loop to stop since we have
                            // more than 40 hours
                            if ($totalWeeklyHours >= 40) {
                                break;
                            }

                            continue;
                        }

                        $noRandomShiftsAvailableDays++;
                    }
                } while ($totalWeeklyHours < 40 && count(array_keys($thisWeeksAssignedShiftsByDay)) > $noRandomShiftsAvailableDays);
            }

            // go to the next week since we already have all the shifts for the current week
            $currentDateTime = $currentDateTime->modify('+1 week');
            $currentDate = $currentDateTime->getTimestamp();
            // adjust the future generation of possible job shifts to be the next week of the semester to adjust start and end timestamps.
            $this->adjustJobsStartDate($currentDateTime);
            $this->adjustWeeklyShiftsToNextWeek();
        }

        // reset job and existing week shifts to reset to the start of the semester after adjusting shifts to new weeks
        $this->adjustJobsStartDate();
        $this->resetWeeklyShiftsToSemesterStart();

        return $this->allSemesterShifts = $allShifts;
    }

    /**
     * Attempts to pick up a shift by generating one that does not conflict
     * with the provided unavailable shifts, if specified.
     *
     * @param  array  $notAvailableShifts  An optional array of timestamps representing shifts that
     *                                   are not available for selection.
     *
     * @return mixed The generated shift from the selected job.
     */
    public function pickUpShift(array $notAvailableShifts = [], string|null $dayOfTheWeek = null): array
    {
        $notAvailableShiftsClone = $notAvailableShifts;
        $whichJob = rand(0, count($this->getJobs()) - 1);
        $job = $this->jobs[$whichJob];
        if (!$job instanceof Job) {
            return [];
        }

        if (empty($notAvailableShiftsClone)) {
            $notAvailableShiftsClone = $job->formatWeeklyShiftsAsTimestamps($this->getWeeklyShifts());
        }

        if (isset($dayOfTheWeek) && !empty($dayOfTheWeek)) {
            $shift = $job->selectRandomShiftFromAvailableShiftsForDay($dayOfTheWeek, $notAvailableShiftsClone);
        } else {
            $shift = $job->selectRandomShiftFromAvailableShifts($notAvailableShiftsClone);
        }

        // the shift may not be assignable
        if (empty($shift)) {
            return [];
        }

        return $shift;
    }
}
