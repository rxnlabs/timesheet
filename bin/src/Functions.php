<?php

declare(strict_types=1);

namespace rxnlabs\Timesheet;

use DirectoryIterator;

/**
 * Generates shifts for all jobs in a given semester and updates the timesheet accordingly.
 *
 * @param  Semester  $semester  The semester object containing job and shift details.
 *
 * @return void
 */
function generateTimesheetForSemester(Semester $semester, Timesheet $timesheet): void
{
    echo "Generating shifts for {$semester->getName()}...\n";
    $semester->adjustJobsStartDate();
    $semester->generateWeeklyShiftsForJobs();
    $semester->generateSemesterShifts();
    $semesterWeek = \DateTime::createFromFormat('m/d/Y', $semester->getStartDate());
    foreach ($semester->getAllSemesterShifts() as $weekNumber => $weekShiftData) {
        $tempShiftHoldRemoveConflicts = [];
        foreach ($weekShiftData as $day => $shiftData) {
            foreach ($shiftData['shifts'] as $index => $shift) {
                $tempShiftHoldRemoveConflicts[] = [
                    'shift_data' => $shift,
                    'start_timestamp' => Job::getShiftStartTimestamp($shift),
                    'end_timestamp' => Job::getShiftEndTimestamp($shift),
                    'shift_index' => $index,
                    'remove_shift' => false
                ];
                $clockInDateTime = new \DateTime();
                $clockInDateTime->setTimestamp(Job::getShiftStartTimestamp($shift));
                $clockOutDateTime = new \DateTime();
                $clockOutDateTime->setTimestamp(Job::getShiftEndTimestamp($shift));
                $clockInTime = $clockInDateTime->format('H:i');
                $clockOutTime = $clockOutDateTime->format('H:i');
                $result = $timesheet->addTimesheetEntry(
                    $clockInDateTime->format('l'),
                    $shift['location'],
                    $clockInTime,
                    $clockOutTime,
                    $clockInDateTime
                );

                if ($result) {
                    echo "Added shift for {$shift['location']} to {$semester->getName()} log.\n";
                }
            }
        }
        $workWeek = $timesheet->getThisWorkWeek($semesterWeek);
        $week = $workWeek['week_number'];
        $year = $workWeek['year'];
        // sort the time entries starting from Thursday to Wednesday since the shifts could have been added any order
        $timesheet->sortTimesheetDataByWorkWeek($year, $week);
        $semesterWeek->modify('+1 week');
        echo "Added shift for {$semester->getName()} week {$weekNumber} log.\n";
    }
}

/**
 * Updates historical timesheets to include missing data and ensure completeness.
 *
 * Earlier versions of the timesheet logs tracked only basic details: the day of the
 * week, clock-in and clock-out times, total minutes, and hours worked. However,
 * critical details like AM/PM distinctions in the times, exact dates for shifts, and
 * unique shift identifiers (IDs) were not recorded. At the time, this minimal data
 * was sufficient for weekly tracking purposes.
 *
 * This method addresses those shortcomings by retroactively adding missing information:
 * - Unique shift IDs for better tracking.
 * - Exact dates using the week number and year from filenames.
 * - AM/PM differentiation with clock times converted to 24-hour format.
 * - ATOM-formatted timestamps for future-proofing.
 *
 * The updates are essential for improving data reliability, enabling integration
 * with a database in future iterations, and ensuring consistency across both past
 * and future timesheets.
 *
 * @param  Timesheet  $timesheet  The timesheet object containing the log directory.
 * @return void
 */
function updateHistoricalTimesheets(Timesheet $timesheet): void
{
    $logFolder = $timesheet->getLogDirectory(); // Parent folder (represents year of logs)
    $directories = new \DirectoryIterator($logFolder);

    foreach ($directories as $fileInfo) {
        if ($fileInfo->isFile() || $fileInfo->isDot()) {
            continue;
        }

        $timesheetYearDirectoryName = $fileInfo->getFilename();
        if (!preg_match('/^\d{4}$/', $timesheetYearDirectoryName)) {
            continue;
        }

        $fileSearch = new \DirectoryIterator($logFolder . DIRECTORY_SEPARATOR . $timesheetYearDirectoryName);
        foreach ($fileSearch as $shouldBeFile) {
            $file = $shouldBeFile->getFilename();
            if (!preg_match('/^week-(\d+)\.\d{1,2}-\d{1,2}-\d{1,2}-\d{1,2}\.hours\.txt$/', $file, $matches)) {
                continue; // Skip non-timesheet files
            }

            // Get the week number from the log file name (e.g. 'week-16.4-22-4-28.hours.txt')
            $weekNumber = (int)$matches[1];
            $filePath = $timesheet->getLogDirectory() . DIRECTORY_SEPARATOR . $timesheetYearDirectoryName . DIRECTORY_SEPARATOR . $file;

            $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $updatedLines = [];

            foreach ($lines as $line) {
                $parts = preg_split('/\s+/', $line);

                // Check if the line already has the required ID and DateTime
                if (preg_match('/^shift-[a-f0-9]/', $parts[0])) {
                    $updatedLines[] = $line; // Line is already valid
                    continue;
                }

                // Process a line missing ID and/or DateTime
                $dayOfWeek = array_shift($parts);
                $location = array_shift($parts);
                $clockIn = array_shift($parts);
                $clockOut = array_shift($parts);
                $durationMinutes = array_shift($parts);
                $durationHours = array_shift($parts);

                // Convert clock-in and clock-out to 24-hour format
                $clockIn24 = date("H:i", strtotime($clockIn));
                $clockIn24Hour = (int)date("H", strtotime($clockIn));
                $clockIn24Minute = date("i", strtotime($clockIn));
                $clockOut24 = date("H:i", strtotime($clockOut));

                // if I clocked-in before 8am, assume it was a night shift
                if ($clockIn24Hour < 8) {
                    // add twelve hours to the clock-in and clock-out times to make them PM
                    $clockIn24Hour += 12;
                    $clockIn24 = date("H:i", strtotime($clockOut) + 12 * 60 * 60);
                    $clockOut24 = date("H:i", strtotime($clockOut) + 12 * 60 * 60);
                }

                // Generate unique ID
                $uniqueId = $timesheet->generateEntryID();

                // Calculate ATOM DateTime
                $dayOfWeekMap = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];
                $dayOffset = $dayOfWeekMap[$dayOfWeek] ?? 0;
                $calculateWeekNumber = $weekNumber;
                // since the workweek is Thursday to Wednesday, we need to increase the week number to get the correct date if the
                // date is Sunday - Wednesday. Thurs, Friday, and Saturday are correct.
                // if the day is Thursday, we are on the right week since that is the start of the work week
                if ($dayOffset < 4) {
                    $calculateWeekNumber = $weekNumber + 1;
                }

                $dayOfWeekDateTime = (new \DateTime())
                    ->setISODate((int)$timesheetYearDirectoryName, $calculateWeekNumber, $dayOffset)
                    ->setTime((int)$clockIn24Hour, (int)$clockIn24Minute, 0);
                $timestamp = $dayOfWeekDateTime->format(\DateTime::ATOM);

                // Construct the updated line
                $updatedLine = sprintf(
                    "%s %s %s %s %s %s %s %s",
                    $uniqueId,
                    $dayOfWeek,
                    $location,
                    $clockIn24,
                    $clockOut24,
                    $durationMinutes,
                    $durationHours,
                    $timestamp
                );

                $updatedLines[] = $updatedLine;
            }

            // overwrite all the entries with the updated entries since if one shift does not
            // have an ID, then they all do not have an ID. IDs were not added to much later (i.e. 2025)
            $result = file_put_contents($filePath, implode(PHP_EOL, $updatedLines) . PHP_EOL);

            if ($result) {
                echo "Updated historical timesheet for week {$weekNumber} in {$timesheetYearDirectoryName} with new format.\n";
            }
        }
    }
}
