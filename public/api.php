<?php

require __DIR__ . '/../vendor/autoload.php';
use rxnlabs\Timesheet\Timesheet as Timesheet;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Http\Factory\DecoratedResponseFactory;

$nyholmFactory = new Psr17Factory();
$responseFactory = new DecoratedResponseFactory($nyholmFactory, $nyholmFactory);
$timesheetObj = new Timesheet(__DIR__ . '/../timesheets');

if ($_GET['endpoint'] === 'gettimesheet') {
    $year = isset($_GET['year']) ? $_GET['year'] : null;
    $week = isset($_GET['week']) ? $_GET['week'] : null;

    try {
        if (!empty($year) && !empty($week) && is_numeric($year) && is_numeric($week)) {
            $timesheetData = $timesheetObj->getTimesheetData(intval($year), intval($week));
        } else {
            $timesheetData = $timesheetObj->getTimesheetData();
        }
    } catch (\Exception $e) {
        $timesheetData = ['error' => true, 'message' => $e->getMessage()];
    }

    if (isset($timesheetData['error'])) {
        $response = $responseFactory->createResponse(400, 'Internal Server Error');
    } else {
        $response = $responseFactory->createResponse(200, 'OK');
    }

    $response = $response->withJson($timesheetData);
    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
    exit;
}

if ($_GET['endpoint'] === 'gettotalhours') {
    try {
        $timesheetData = $timesheetObj->getTimesheetData();
        $timesheetData = $timesheetData['totalHours'];
    } catch (\Exception $e) {
        $timesheetData = ['error' => true, 'message' => $e->getMessage()];
    }

    if (isset($timesheetData['error'])) {
        $response = $responseFactory->createResponse(400, 'Internal Server Error');
    } else {
        $response = $responseFactory->createResponse(200, 'OK');
    }

    $response = $response->withJson($timesheetData);
    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
    exit;
}

if ($_GET['endpoint'] === 'addtimesheetentry') {
    $successMessage = 'Shift added successfully';
    try {
        $day = $_POST['day'];
        $location = $_POST['location'];
        $clockIn = $_POST['clock-in'];
        $clockOut = null;

        if (isset($_POST['clock-out'])) {
            $clockOut = $_POST['clock-out'];
        }

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $id = $_POST['id'];
            $result = $timesheetObj->editTimeEntry($id, $day, $location, $clockIn, $clockOut);
            $successMessage = 'Shift updated successfully';
        } else {
            $workWeekStartDateThursday = $timesheetObj->getThisWorkWeek()['start_day'];
            $entryDate                 = clone $workWeekStartDateThursday;
            switch (strtolower($day)) {
                case 'monday':
                    $entryDate->modify('+4 days');
                    break;
                case 'tuesday':
                    $entryDate->modify('+5 days');
                    break;
                case 'wednesday':
                    $entryDate->modify('+6 days');
                    break;
                case 'friday':
                    $entryDate->modify('+1 days');
                    break;
                case 'saturday':
                    $entryDate->modify('+2 days');
                    break;
                case 'sunday':
                    $entryDate->modify('+3 days');
                    break;
            }

            $result = $timesheetObj->addTimesheetEntry($day, $location, $clockIn, $clockOut, $entryDate);
        }

        if ($result) {
            $entry = ['message' => $successMessage];
        }
    } catch (\Exception $e) {
        $entry = ['error' => true, 'message' => $e->getMessage()];
    }

    if (isset($entry['error'])) {
        $response = $responseFactory->createResponse(400, 'Internal Server Error');
    } else {
        $response = $responseFactory->createResponse(201, 'OK');
    }

    $response = $response->withJson($entry);
    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
    exit;
}

if ($_GET['endpoint'] === 'gethisworkweek') {
    $yearweek = $timesheetObj->getThisWorkWeek();
    $response = $responseFactory->createResponse(200, 'OK');
    $response = $response->withJson($yearweek);
    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
    exit;
}

if ($_GET['endpoint'] === 'getlogweeks') {
    $data = $timesheetObj->getTimesheetLogWeeks();
    $response = $responseFactory->createResponse(200, 'OK');
    $response = $response->withJson($data);
    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
    exit;
}
