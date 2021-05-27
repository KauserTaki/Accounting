<?php

namespace Modules\Parasut\Traits;

use App\Models\Module\Module;

trait Inventory
{
    protected function isInventory()
    {
        if (!module('inventory')) {
            return false;
        }

        $module = Module::alias('inventory')->enabled()->first();

        if ($module) {
            return true;
        }

        return false;
    }
}
