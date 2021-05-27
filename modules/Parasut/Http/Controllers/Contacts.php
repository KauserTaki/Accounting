<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;

use Modules\Parasut\Jobs\Sale\CreateCustomer;
use Modules\Parasut\Jobs\Purchase\CreateVendor;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Date;
use Cache;

class Contacts extends Controller
{
    use Remote, CustomFields;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // Add CRUD permission check
        $this->middleware('permission:create-sales-customers')->only(['create', 'store', 'duplicate', 'import']);
        $this->middleware('permission:read-sales-customers')->only(['index', 'show', 'edit', 'export', 'count']);
        $this->middleware('permission:update-sales-customers')->only(['update', 'enable', 'disable']);
        $this->middleware('permission:delete-sales-customers')->only('destroy');
    }

    public function count()
    {
        Cache::forget('parasut_contacts');

        $total = 0;

        $apps = [
            'custom_fields' => $this->checkCustomFields('contacts')
        ];

        $type = trans_choice('parasut::general.types.contacts', 2);

        $contacts = $this->getContacts();

        if (!isset($contacts->error) && $contacts) {
            $total = $contacts->meta->total_count;
        }

        $html = view('parasut::partial.sync', compact('type', 'total', 'apps'))->render();

        $json = [
            'apps' => $apps,
            'errors' => isset($contacts->error) ? $contacts->error_description : false,
            'success' => !isset($contacts->error) ? true : false,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $total = 0;
        $contacts = $steps  = [];

        do {
            $parameters['page'] = [
                'size' => 15,
                'number' => $page
            ];

            $results = $this->getContacts($parameters);

            if (!isset($results->error) && $results) {
                $total = $results->meta->total_count;
            }

            if (!empty($results) && !empty($results->data)) {
                foreach ($results->data as $result) {
                    $contacts[$result->id] = $result;

                    $type = ($result->attributes->account_type == 'customer') ? 'customers' : 'supplier';

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', [
                            'type' => trans_choice('parasut::general.types.' . $type, 1),
                            'value' => $result->attributes->name
                        ]),
                        'url'  => route('parasut.contacts.store', $result->id)
                    ];
                }
            }

            $page++;
        } while (!empty($results->data));

        // Set parasut customers
        session(['parasut_contacts' => $contacts]);
        Cache::put('parasut_contacts', $contacts, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($contacts),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    public function store($id)
    {
        $contacts = Cache::get('parasut_contacts');

        if (empty($contacts)) {
            $contacts = session('parasut_contacts');
        }

        $contact = $contacts[$id];

        switch ($contact->attributes->account_type) {
            case 'customer':
                $response = $this->ajaxDispatch(new CreateCustomer($contact));
                break;
            case 'supplier':
                $response = $this->ajaxDispatch(new CreateVendor($contact));
                break;
        }

        $json = [
            'errors' => false,
            'success' => true,
        ];

        $last_customer = end($contacts)->id;

        if ($last_customer == $id) {
            $json['finished'] = trans('parasut::general.finished', ['type' => trans_choice('general.customers', 2)]);

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('parasut.contact_last_check', $timestamp);
            setting()->save();
        }

        return response()->json($json);
    }
}
