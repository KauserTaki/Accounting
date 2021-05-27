<?php

namespace Modules\Employees\Jobs\Employee;

use App\Abstracts\Job;
use Modules\Employees\Models\Employee;

class CreateEmployee extends Job
{
    protected $request;

    /**
     * Create a new job instance.
     *
     * @param  $request
     */
    public function __construct($request)
    {
        $this->request = $this->getRequestInstance($request);
    }

    /**
     * Execute the job.
     *
     * @return Employee
     */
    public function handle()
    {
        return Employee::create($this->request->all());
    }
}
