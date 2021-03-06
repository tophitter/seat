<?php
/*
The MIT License (MIT)

Copyright (c) 2014 eve-seat

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace Seat\EveQueues\Full;

use Carbon\Carbon;
use Seat\EveApi;

class Corporation
{

    public function fire($job, $data)
    {

        $keyID = $data['keyID'];
        $vCode = $data['vCode'];

        $job_record = \SeatQueueInformation::where('jobID', '=', $job->getJobId())->first();

        // Check that we have a valid jobid
        if (!$job_record) {

            // Sometimes the jobs get picked up faster than the submitter could write a
            // database entry about it. So, just wait 5 seconds before we come back and
            // try again
            $job->release(5);
            return;
        }

        // We place the actual API work in our own try catch so that we can report
        // on any critical errors that may have occurred.

        // By default Laravel will requeue a failed job based on --tries, but we
        // dont really want failed api jobs to continually poll the API Server
        try {

            $job_record->status = 'Working';
            $job_record->save();

            $job_record->output = 'Started AccountBalance Update';
            $job_record->save();
            EveApi\Corporation\AccountBalance::Update($keyID, $vCode);

            $job_record->output = 'Started AssetList Update';
            $job_record->save();
            EveApi\Corporation\AssetList::Update($keyID, $vCode);

            $job_record->output = 'Started ContactList Update';
            $job_record->save();
            EveApi\Corporation\ContactList::Update($keyID, $vCode);

            $job_record->output = 'Started Contracts Update';
            $job_record->save();
            EveApi\Corporation\Contracts::Update($keyID, $vCode);

            $job_record->output = 'Started CorporationSheet Update';
            $job_record->save();
            EveApi\Corporation\CorporationSheet::Update($keyID, $vCode);

            $job_record->output = 'Started CustomsOffice Update';
            $job_record->save();
            EveApi\Corporation\CustomsOffices::Update($keyID, $vCode);
            
            $job_record->output = 'Started IndustryJobs Update';
            $job_record->save();
            EveApi\Corporation\IndustryJobs::Update($keyID, $vCode);

            $job_record->output = 'Started KillMails Update';
            $job_record->save();
            EveApi\Corporation\KillMails::Update($keyID, $vCode);

            $job_record->output = 'Started MarketOrders Update';
            $job_record->save();
            EveApi\Corporation\MarketOrders::Update($keyID, $vCode);

            $job_record->output = 'Started Medals Update';
            $job_record->save();
            EveApi\Corporation\Medals::Update($keyID, $vCode);

            $job_record->output = 'Started MemberMedals Update';
            $job_record->save();
            EveApi\Corporation\MemberMedals::Update($keyID, $vCode);

            $job_record->output = 'Started MemberSecurity Update';
            $job_record->save();
            EveApi\Corporation\MemberSecurity::Update($keyID, $vCode);

            $job_record->output = 'Started MemberSecurityLog Update';
            $job_record->save();
            EveApi\Corporation\MemberSecurityLog::Update($keyID, $vCode);

            $job_record->output = 'Started MemberTracking Update';
            $job_record->save();
            EveApi\Corporation\MemberTracking::Update($keyID, $vCode);

            $job_record->output = 'Started Shareholders Update';
            $job_record->save();
            EveApi\Corporation\Shareholders::Update($keyID, $vCode);

            $job_record->output = 'Started Standings Update';
            $job_record->save();
            EveApi\Corporation\Standings::Update($keyID, $vCode);

            $job_record->output = 'Started StarbaseList Update';
            $job_record->save();
            EveApi\Corporation\StarbaseList::Update($keyID, $vCode);

            $job_record->output = 'Started StarbaseDetail Update';
            $job_record->save();
            EveApi\Corporation\StarbaseDetail::Update($keyID, $vCode);

            $job_record->output = 'Started Titles Update';
            $job_record->save();
            EveApi\Corporation\Titles::Update($keyID, $vCode);

            $job_record->output = 'Started WalletJournal Update';
            $job_record->save();
            EveApi\Corporation\WalletJournal::Update($keyID, $vCode);

            $job_record->output = 'Started WalletTransactions Update';
            $job_record->save();
            EveApi\Corporation\WalletTransactions::Update($keyID, $vCode);

            $job_record->status = 'Done';
            $job_record->output = null;
            $job_record->save();

            $job->delete();

        } catch (\Seat\EveApi\Exception\APIServerDown $e) {

            // The API Server is down according to \Seat\EveApi\bootstrap().
            // Due to this fact, we can simply take this job and put it
            // back in the queue to be processed later.
            $job_record->status = 'Queued';
            $job_record->output = 'The API Server appears to be down. Job has been re-queued.';
            $job_record->save();

            // Re-queue the job to try again in 10 minutes
            $job->release(60 * 10);

        } catch (\Exception $e) {

            $job_record->status = 'Error';
            $job_record->output = 'Last status: ' . $job_record->output . PHP_EOL .
                'Error: ' . $e->getCode() . ': ' . $e->getMessage() . PHP_EOL .
                'File: ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL .
                'Trace: ' . $e->getTraceAsString() . PHP_EOL .
                'Previous: ' . $e->getPrevious();
            $job_record->save();

            $job->delete();
        }
    }
}
