<?php

namespace App\Console\Commands;

use App\Services\Persistence\MigrationProcess;
use App\Services\Persistence\Repo;
use App\Services\Readers\FileReader;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MigrationProcCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:file {--f|file=challenge.json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'read file and writes to the db';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        try {
            // register filter to be applied to "date_of_birth field
            MigrationProcess::registerFilter(["date_of_birth" => function($dob) {
                if ($dob == null) return true;
                else {
                    if (str_contains($dob, '/')) {
                        $dob = date('Y-m-d H:i:s', strtotime($dob));
                    }
                    $birth = new Carbon($dob);
                    /*$ofAge = $birth->age > 18 && $birth->age <= 65;
                    if ($ofAge) {
                        echo "user is of age \n";
                    } else {
                        echo "user is not of age \n";
                    }*/
                    return $birth->age > 18 && $birth->age <= 65;
                }
            }]);
            $file = $this->option('file');
            // check if there are any incomplete jobs with the current name
            Repo::getInstance();
            $job = DB::table(Repo::$jobsTable)->where("name", $file)->first();
            $reader = new FileReader($file);
            if ($job !== null && $job->status == 0) {
                MigrationProcess::start($reader->readAsString()->parse(), $file, true, $job);
            } elseif ($job !== null && $job->status == 1) {
                echo "Job already processed completely:\n Data has been migrated to the database\n";
            } else {
                MigrationProcess::start($reader->readAsString()->parse(), $file, true);
            }
        } catch (\Exception $exception) {
            dd($exception->getTraceAsString());
            var_dump($exception->getMessage(), $exception->getLine());
        }
        return 0;
    }
}
