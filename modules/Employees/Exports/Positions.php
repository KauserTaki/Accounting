<?php

namespace Modules\Employees\Exports;

use App\Abstracts\Export;
use Illuminate\Support\Collection;
use Modules\Employees\Models\Position as Model;

class Positions extends Export
{
    public function collection(): Collection
    {
        return Model::usingSearchString(request('search'))->get();
    }

    public function fields(): array
    {
        return [
            'name',
            'enabled',
        ];
    }
}
