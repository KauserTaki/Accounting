<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;

use Modules\Parasut\Jobs\Purchase\CreateEmployee;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Traits\Payroll;

use Date;
use Cache;

class Employees extends Controller
{
    use Remote, CustomFields, Payroll;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // Add CRUD permission check
        $this->middleware('permission:create-purchases-vendors')->only(['create', 'store', 'duplicate', 'import']);
        $this->middleware('permission:read-purchases-vendors')->only(['index', 'show', 'edit', 'export', 'count']);
        $this->middleware('permission:update-purchases-vendors')->only(['update', 'enable', 'disable']);
        $this->middleware('permission:delete-purchases-vendors')->only('destroy');
    }

    public function count()
    {
        Cache::forget('parasut_employees');

        $total = 0;

        $apps = [
            'custom_fields' => $this->checkCustomFields('employees'),
            'payroll' => !$this->isPayroll()
        ];

        $type = trans_choice('parasut::general.types.employees', 2);

        $employees = $this->getEmployees();

        if (!isset($employees->error) && $employees) {
            $total = $employees->meta->total_count;
        }

        $html = view('parasut::partial.sync', compact('type', 'total', 'apps'))->render();

        $json = [
            'apps' => $apps,
            'errors' => isset($employees->error) ? $employees->error_description : false,
            'success' => !isset($employees->error) ? true : false,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $employees = $steps = [];

        do {
            $parameters['page'] = [
                'size' => 15,
                'number' => $page
            ];

            $results = $this->getEmployees($parameters);

            if (!empty($results) && !empty($results->data)) {
                foreach ($results->data as $result) {
                    $employees[$result->id] = $result;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', [
                            'type' => trans_choice('parasut::general.types.employees' , 1),
                            'value' => $result->attributes->name
                        ]),
                        'url'  => route('parasut.employees.store', $result->id)
                    ];
                }
            }

            $page++;
        } while (!empty($results->data));

        // Set parasut customers
        session(['parasut_employees' => $employees]);
        Cache::put('parasut_employees', $employees, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($employees),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    public function store($id)
    {
        $employees = Cache::get('parasut_employees');

        if (empty($employees)) {
            $employees = session('parasut_employees');
        }

        $employee = $employees[$id];

        $response = $this->ajaxDispatch(new CreateEmployee($employee));

        $json = [
            'errors' => false,
            'success' => true,
        ];

        $last_customer = end($employees)->id;

        if ($last_customer == $id) {
            $json['finished'] = trans('parasut::general.finished', ['type' => trans_choice('parasut::general.types.customers', 2)]);

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('parasut.employee_last_check', $timestamp);
            setting()->save();
        }

        return response()->json($json);
    }
}
