<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;

use Modules\Parasut\Jobs\Purchase\CreateBill;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Date;
use Cache;

class Bills extends Controller
{
    use Remote, CustomFields;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // Add CRUD permission check
        $this->middleware('permission:create-purchases-bills')->only(['create', 'store', 'duplicate', 'import']);
        $this->middleware('permission:read-purchases-bills')->only(['index', 'show', 'edit', 'export', 'count']);
        $this->middleware('permission:update-purchases-bills')->only(['update', 'enable', 'disable']);
        $this->middleware('permission:delete-purchases-bills')->only('destroy');
    }

    public function count()
    {
        Cache::forget('parasut_bills');

        $total = 0;

        $apps = [
            'custom_fields' => $this->checkCustomFields('bills')
        ];

        $type = trans_choice('general.bills', 2);

        $bills = $this->getBills();

        if (!isset($bills->error) && $bills) {
            $total = $bills->meta->total_count;
        }

        $html = view('parasut::partial.sync', compact('type', 'total', 'apps'))->render();

        $json = [
            'apps' => $apps,
            'errors' => isset($bills->error) ? $bills->error_description : false,
            'success' => !isset($bills->error) ? true : false,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $bills = $steps = [];

        do {
            $parameters['include'] = 'category,spender,details,details.product,payments,payments.transaction,tags,recurrence_plan,active_e_document,pay_to';

            $parameters['page'] = [
                'size' => 15,
                'number' => $page
            ];

            $results = $this->getBills($parameters);

            if (!empty($results) && !empty($results->data)) {
                foreach ($results->data as $result) {
                    $bills[$result->id] = $result;

                    $value = '#' . $result->id;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', [
                            'type' => trans_choice('general.bills', 1),
                            'value' => $value
                        ]),
                        'url'  => route('parasut.bills.store', $result->id)
                    ];
                }
            }

            $page++;
        } while (!empty($results->data));

        /*
        krsort($bills);
        krsort($steps);
        sort($steps);
        */

        // Set shopify bills
        session(['parasut_bills' => $bills]);
        Cache::put('parasut_bills', $bills, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($bills),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    public function store($bill_number)
    {
        $bills = Cache::get('parasut_bills');

        if (empty($bills)) {
            $bills = session('parasut_bills');
        }

        $response = $this->ajaxDispatch(new CreateBill($bills[$bill_number]));

        $json = [
            'errors' => false,
            'success' => true,
        ];

        $last_bill = end($bills)->id;

        if ($last_bill == $bill_number) {
            $json['finished'] = trans('parasut::general.finished', ['type' => trans_choice('general.bills', 2)]);

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('parasut.bill_last_check', $timestamp);
            setting()->save();
        }

        return response()->json($json);
    }
}
