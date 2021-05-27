<?php

namespace Modules\Parasut\Traits;

use App\Models\Module\Module;

trait Payroll
{
    protected function isPayroll()
    {
        if (!module('payroll')) {
            return false;
        }

        $module = Module::alias('payroll')->enabled()->first();

        if ($module) {
            return true;
        }

        return false;
    }
}
