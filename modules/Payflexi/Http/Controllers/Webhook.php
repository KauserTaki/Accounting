<?php

namespace Modules\Payflexi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Setting\Setting;
use App\Models\Document\Document;
use App\Abstracts\Http\Controller;
use App\Models\Banking\Transaction;
use App\Events\Document\PaymentReceived;
use App\Abstracts\Http\PaymentController;
use Modules\Payflexi\Jobs\DocumentTransaction;


class Webhook extends PaymentController
{
    public $alias = 'payflexi';

    public function __construct()
    {
        parent::__construct();
        $this->middleware('csrf', ['except' => ['index']]);
    }

    public function index(Request $request)
    {
        if ((strtoupper($_SERVER['REQUEST_METHOD']) != 'POST') || ! array_key_exists('HTTP_X_PAYFLEXI_SIGNATURE', $_SERVER)) {
            exit;
        }

        $json = file_get_contents('php://input');

        $company_id  = $request->company_id;

        $alias = $this->alias;

        $apiSetting = Setting::where('company_id', $company_id)->prefix($alias)->get()->transform(function ($s) use ($alias) {
            $s->key = str_replace($alias . '.', '', $s->key);
            return $s;
        })->pluck('value', 'key');

        $secret_key = ($apiSetting['mode'] == 'test') ? $apiSetting['api_test_secret_key'] : $apiSetting['api_live_secret_key'];

        $default_key = 'default';

        $defaultSetting = Setting::where('company_id', $company_id)->prefix($default_key)->get()->transform(function ($s) use ($default_key) {
            $s->key = str_replace($default_key . '.', '', $s->key);
            return $s;
        })->pluck('value', 'key');

        // validate event do all at once to avoid timing attack
        if ($_SERVER['HTTP_X_PAYFLEXI_SIGNATURE'] !== hash_hmac('sha512', $json, $secret_key)) {
            exit;
        }

        $event = json_decode($json);

        if ($event->event == 'transaction.approved' && $event->data->status == 'approved') {

            http_response_code(200);

            $reference = $event->data->reference;
            $initial_reference = $event->data->initial_reference;

            $invoice_details = explode('_', $initial_reference);
            $invoice_id = (int) $invoice_details[0];

            $invoice = Document::where('company_id', $company_id)->where('id', $invoice_id)->first();
            $payment = Transaction::where('company_id', $company_id)->where('document_id', $invoice_id)->where('amount', '>', 0)->where('reference', '=', $initial_reference)->first();

            if((!$payment && $reference === $initial_reference) || $reference !== $initial_reference){
            
                $amount_paid = $event->data->txn_amount;

                $requestData = $request->merge([
                    'company_id' => $company_id,
                    'account_id' => $defaultSetting['account'],
                    'amount' => $amount_paid,
                    'payment_method' => $this->alias,
                    'reference' => $event->data->reference,
                    'currency_code' => $invoice->currency_code,
                    'type' => 'income',
                    'notify' => 1
                ]);

                dispatch_now(new DocumentTransaction($invoice, $requestData->all()));

            }

        }

    }
}