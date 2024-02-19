<?php
declare(strict_types=1);

namespace App\Domain\Reporting;

// Setup our database connections outside of the Persistence directory
require __DIR__ . '/../../../app/Database.php';

/* This is called from /Action/Reporting/ReportingAction.php */
interface ReportingRepository {
    public function createReport($arr);    // Request a report be run
    public function runReport($arr);       // Run a given report and send to database
    public function runPending($arr);      // Run a pending report created by createReports
    public function deleteReport($arr);    // Remove from filesystem a given report
    public function searchReport();
    public function searchTemplate();
    public function findPreviousReports(); // Unused
    public function findReports();         // Return a list of all available or defined reports
    public function findPending();         // Return id of all pending reports

}
