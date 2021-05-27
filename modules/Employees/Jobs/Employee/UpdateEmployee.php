<?php

namespace Modules\Employees\Jobs\Employee;

use App\Abstracts\Job;
use Modules\Employees\Models\Employee;

class UpdateEmployee extends Job
{
    protected $employee;

    protected $request;

    public function __construct(Employee $employee, $request)
    {
        $this->employee = $employee;
        $this->request = $this->getRequestInstance($request);
    }

    public function handle(): Employee
    {
        $this->employee->update($this->request->all());

        return $this->employee;
    }
}
