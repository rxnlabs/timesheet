<?php

// set the default timezone to Eastern time zone since my college was on the east coast
date_default_timezone_set('America/New_York');
include __DIR__ . '/../vendor/autoload.php';

use rxnlabs\Timesheet\Timesheet;
use rxnlabs\Timesheet\Job;
use rxnlabs\Timesheet\Semester;
$timesheet = new Timesheet(__DIR__ . '/../timesheets');
\rxnlabs\Timesheet\updateHistoricalTimesheets($timesheet);

$parkingJob = new Job(
    name:'Parking Office',
    shiftsPerDay: '1',
    openWeekends: false,
    shiftsStartMinute: 0,
    officeHours: '08:00 - 17:00',
    location: ['Parking Office']
);

$computerLabJob = new Job(
    name:'Computer Lab',
    shiftsPerDay: 'multiple',
    openWeekends: true,
    shiftsStartMinute: 45,
    officeHours: '07:45 - 23:45',
    location: ['Helpdesk', 'Blackwell', 'UC', 'Fulton', 'Devilbiss', 'Henson']
);
// Save the original locations of the places I could work in the computer lab since the list of locations change
// based on the semester, my role at the time, and new buildings.
$computerJobOriginalLocations = $computerLabJob->getLocation();

$semesterFall2007 = new Semester(
    name: 'Fall 2007',
    startDate: '09/04/2007',
    endDate: '12/14/2007',
    closedDays: ['09/03/2007', '11/21/2007 - 11/25/2007']
);
$parkingJob->minimumHours = 10;
$parkingJob->maximumHours = 14;
$semesterFall2007->addJob($parkingJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterFall2007, $timesheet);

$semesterSpring2008 = new Semester(
    name: 'Spring 2008',
    startDate: '01/03/2008',
    endDate: '05/23/2008',
    closedDays: ['1/15/2008', '3/17/2008 - 3/25/2008'],
);
$parkingJob->minimumHours = 14;
$parkingJob->maximumHours = 18;
$semesterSpring2008->addJob($parkingJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterSpring2008, $timesheet);

$semesterFall2008 = new Semester(
    name: 'Fall 2008',
    startDate: '09/02/2008',
    endDate: '12/19/2008',
    closedDays: ['09/01/2008', '11/26/2008 - 11/28/2008'],
);
$parkingJob->minimumHours = 10;
$parkingJob->maximumHours = 12;
// In Fall 2008, I started working at the Computer Labs
$computerLabJob->minimumHours = 10;
$computerLabJob->maximumHours = 20;
// TETC was not built until Summer 2009 and I was a Lab Tech in Fall 2008, so I could not work the Helpdesk. Remove
// these locations
$computerLabJob->removeLocation('TETC');
$computerLabJob->removeLocation('Helpdesk');
$semesterFall2008->addJob($parkingJob, $computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterFall2008, $timesheet);

$semesterSpring2009 = new Semester(
    name: 'Spring 2009',
    startDate: '01/26/2009',
    endDate: '05/20/2009',
    closedDays: ['3/16/2009 - 3/22/2009'],
);
$parkingJob->minimumHours = 10;
$parkingJob->maximumHours = 12;
$computerLabJob->minimumHours = 10;
$computerLabJob->maximumHours = 20;
$semesterSpring2009->addJob($parkingJob, $computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterSpring2009, $timesheet);

$semesterSummer2009 = new Semester(
    name: 'Summer 2009',
    startDate: '06/8/2009',
    endDate: '08/30/2009',
    closedDays: [],
);
$computerLabJob->minimumHours = 20;
$computerLabJob->maximumHours = 32;
// I was a consultant and could work the Helpdesk in Summer 2009,
// only the Helpdesk was open in the summer session and there were no lab techs.
$computerLabJob->setLocation(['Helpdesk']);
// I did not work at the Parking Office in the summer and winter sessions. There were no student workers at the
// Parking Office during the summer and winter sessions.
$semesterSummer2009->addJob($computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterSummer2009, $timesheet);

$semesterFall2009 = new Semester(
    name: 'Fall 2009',
    startDate: '08/31/2009',
    endDate: '12/18/2009',
    closedDays: ['09/07/2009', '11/25/2009 - 11/29/2010'],
);
$computerLabJob->minimumHours = 16;
$computerLabJob->maximumHours = 20;
// all locations for the computer job were open in fall 2009 and I could work any location as a Consultant and Lab Tech
$computerLabJob->setLocation($computerJobOriginalLocations);
$semesterFall2009->addJob($parkingJob, $computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterFall2009, $timesheet);

$semesterWinter2010 = new Semester(
    name: 'Winter 2010',
    startDate: '1/4/2010',
    endDate: '1/22/2010',
    closedDays: ['1/18/2010'],
);
// Only the Helpdesk is open in the Winter session
$computerLabJob->minimumHours = 25;
$computerLabJob->maximumHours = 35;
$computerLabJob->setLocation(['Helpdesk']);
$semesterWinter2010->addJob($computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterWinter2010, $timesheet);

$semesterSpring2010 = new Semester(
    name: 'Spring 2010',
    startDate: '01/25/2010',
    endDate: '05/20/2010',
    closedDays: ['03/15/2010 - 3/21/2010'],
);
$computerLabJob->minimumHours = 20;
$computerLabJob->maximumHours = 25;
$computerLabJob->setLocation($computerJobOriginalLocations);
$semesterSpring2010->addJob($parkingJob, $computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterSpring2010, $timesheet);

$semesterSummer2010 = new Semester(
    name: 'Summer 2010',
    startDate: '06/1/2010',
    endDate: '08/25/2010',
    closedDays: [],
);
// Only the Helpdesk was open in the summer session
// By Summer 2010, I was the Intern, so I was no longer working at the Parking Office
$computerLabJob->minimumHours = 28;
$computerLabJob->maximumHours = 36;
$computerLabJob->setLocation(['Helpdesk']);
$semesterSummer2010->addJob($computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterSummer2010, $timesheet);

$semesterFall2010 = new Semester(
    name: 'Fall 2010',
    startDate: '08/26/2010',
    endDate: '12/17/2010',
    closedDays: ['11/25/2010 - 11/28/2010'],
);
$computerLabJob->setLocation($computerJobOriginalLocations);
// By 2010, I was only working at the Helpdesk as the Intern
$computerLabJob->minimumHours = 25;
$computerLabJob->maximumHours = 35;
$semesterFall2010->addJob($computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterFall2010, $timesheet);

$semesterWinter2011 = new Semester(
    name: 'Winter 2011',
    startDate: '01/3/2011',
    endDate: '01/23/2011',
    closedDays: ['1/17/2011'],
);
$computerLabJob->minimumHours = 30;
$computerLabJob->maximumHours = 35;
$computerLabJob->setLocation(['Helpdesk']);
$semesterWinter2011->addJob($computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterWinter2011, $timesheet);

$semesterSpring2011 = new Semester(
    name: 'Spring 2011',
    startDate: '01/24/2011',
    endDate: '05/18/2011',
    closedDays: ['3/21/2011 - 3/27/2011'],
);
$computerLabJob->minimumHours = 25;
$computerLabJob->maximumHours = 35;
$computerLabJob->setLocation($computerJobOriginalLocations);
$semesterSpring2011->addJob($computerLabJob);
\rxnlabs\Timesheet\generateTimesheetForSemester($semesterSpring2011, $timesheet);
