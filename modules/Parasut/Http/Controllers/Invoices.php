<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;

use Modules\Parasut\Jobs\Sale\CreateInvoice;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Date;
use Cache;

class Invoices extends Controller
{
    use Remote, CustomFields;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // Add CRUD permission check
        $this->middleware('permission:create-sales-invoices')->only(['create', 'store', 'duplicate', 'import']);
        $this->middleware('permission:read-sales-invoices')->only(['index', 'show', 'edit', 'export', 'count']);
        $this->middleware('permission:update-sales-invoices')->only(['update', 'enable', 'disable']);
        $this->middleware('permission:delete-sales-invoices')->only('destroy');
    }

    public function count()
    {
        Cache::forget('parasut_invoices');

        $total = 0;

        $apps = [
            'custom_fields' => $this->checkCustomFields('invoices')
        ];

        $type = trans_choice('general.invoices', 2);

        $invoices = $this->getInvoices();

        if (!isset($invoices->error) && $invoices) {
            $total = $invoices->meta->total_count;
        }

        $html = view('parasut::partial.sync', compact('type', 'total', 'apps'))->render();

        $json = [
            'apps' => $apps,
            'errors' => isset($invoices->error) ? $invoices->error_description : false,
            'success' => !isset($invoices->error) ? true : false,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $invoices = $steps = [];

        do {
            $parameters['per_page'] = 100;
            $parameters['page'] = $page;

            $results = $this->getInvoices($parameters);

            if (!empty($results) && !empty($results->items)) {
                foreach ($results->items as $result) {
                    $invoices[$result->id] = $result;

                    $value = '#' . $result->id;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', [
                            'type' => trans_choice('general.invoices', 1),
                            'value' => $value
                        ]),
                        'url'  => route('parasut.invoices.store', $result->id)
                    ];
                }
            }

            $page++;
        } while (!empty($results->items));

        /*krsort($invoices);
        krsort($steps);
        sort($steps);*/

        // Set shopify invoices
        session(['parasut_invoices' => $invoices]);
        Cache::put('parasut_invoices', $invoices, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($invoices),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    public function store($invoice_number)
    {
        $invoices = Cache::get('parasut_invoices');

        if (empty($invoices)) {
            $invoices = session('parasut_invoices');
        }

        $invoice = $this->ajaxDispatch(new CreateInvoice($invoices[$invoice_number]));

        $json = [
            'errors' => false,
            'success' => true,
        ];

        $last_invoice = end($invoices)->id;

        if ($last_invoice == $invoice_number) {
            $json['finished'] = trans('parasut::general.finished', ['type' => trans_choice('general.invoices', 2)]);

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('parasut.invoice_last_check', $timestamp);
            setting()->save();
        }

        return response()->json($json);
    }
}
