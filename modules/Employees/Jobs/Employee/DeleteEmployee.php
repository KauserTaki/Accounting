<?php

namespace Modules\Employees\Jobs\Employee;

use App\Abstracts\Job;
use Modules\Employees\Models\Employee;

class DeleteEmployee extends Job
{
    protected $employee;

    public function __construct(Employee $employee)
    {
        $this->employee = $employee;
    }

    public function handle(): bool
    {
        $this->employee->delete();

        return true;
    }
}
