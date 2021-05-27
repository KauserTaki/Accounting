<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;

use Modules\Parasut\Jobs\Banking\CreateAccount;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Date;
use Cache;

class Accounts extends Controller
{
    use Remote, CustomFields;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // Add CRUD permission check
        $this->middleware('permission:create-banking-accounts')->only(['create', 'store', 'duplicate', 'import']);
        $this->middleware('permission:read-banking-accounts')->only(['index', 'show', 'edit', 'export', 'count']);
        $this->middleware('permission:update-banking-accounts')->only(['update', 'enable', 'disable']);
        $this->middleware('permission:delete-banking-accounts')->only('destroy');
    }

    public function count()
    {
        Cache::forget('parasut_accounts');

        $total = 0;

        $apps = [
            'custom_fields' => $this->checkCustomFields('accounts')
        ];

        $type = trans_choice('general.accounts', 2);

        $accounts = $this->getAccounts();

        if (!isset($accounts->error) && $accounts) {
            $total = $accounts->meta->total_count;
        }

        $html = view('parasut::partial.sync', compact('type', 'total', 'apps'))->render();

        $json = [
            'apps' => $apps,
            'errors' => isset($accounts->error) ? $accounts->error_description : false,
            'success' => !isset($accounts->error) ? true : false,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $accounts = $steps = [];

        do {
            $parameters['page'] = [
                'size' => 15,
                'number' => $page
            ];

            $results = $this->getAccounts($parameters);

            if (!empty($results) && !empty($results->data)) {
                foreach ($results->data as $result) {
                    $accounts[$result->id] = $result;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', [
                            'type' => trans_choice('general.accounts', 1),
                            'value' => $result->attributes->name
                        ]),
                        'url'  => route('parasut.accounts.store', $result->id)
                    ];
                }
            }

            $page++;
        } while (!empty($results->data));

        // Set parasut customers
        session(['parasut_accounts' => $accounts]);
        Cache::put('parasut_accounts', $accounts, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($accounts),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    public function store($id)
    {
        $accounts = Cache::get('parasut_accounts');

        if (empty($accounts)) {
            $accounts = session('parasut_accounts');
        }

        $account = $accounts[$id];

        $response = $this->ajaxDispatch(new CreateAccount($account));

        $json = [
            'errors' => false,
            'success' => true,
        ];

        $last_customer = end($accounts)->id;

        if ($last_customer == $id) {
            $json['finished'] = trans('parasut::general.finished', ['type' => trans_choice('general.accounts', 2)]);

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('parasut.account_last_check', $timestamp);
            setting()->save();
        }

        return response()->json($json);
    }
}
