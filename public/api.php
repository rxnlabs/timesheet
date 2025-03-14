<?php

require __DIR__ . '/../vendor/autoload.php';
use rxnlabs\Timesheet\Timesheet as Timesheet;
use Nyholm\Psr7\Factory\Psr17Factory;
use Slim\Http\Factory\DecoratedResponseFactory;

$nyholmFactory = new Psr17Factory();
$responseFactory = new DecoratedResponseFactory($nyholmFactory, $nyholmFactory);
$timesheetObj = new Timesheet(__DIR__ . '/../timesheets');

if ($_GET['endpoint'] === 'gettimesheet') {
    try {
        $timesheetData = $timesheetObj->getTimesheetData();
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
            $result = $timesheetObj->addTimesheetEntry($day, $location, $clockIn, $clockOut);
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
}

if ($_GET['endpoint'] === 'gethisworkweek') {
    $yearweek = $timesheetObj->getThisWorkWeek();
    $response = $responseFactory->createResponse(200, 'OK');
    $response = $response->withJson($yearweek);
    (new \Laminas\HttpHandlerRunner\Emitter\SapiEmitter())->emit($response);
}
