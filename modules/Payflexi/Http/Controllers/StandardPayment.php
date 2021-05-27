<?php

namespace Modules\Payflexi\Http\Controllers;

use Monolog\Logger;
use GuzzleHttp\Client;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Setting\Setting;
use App\Models\Document\Document;
use App\Abstracts\Http\Controller;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\URL;
use App\Events\Document\PaymentReceived;
use App\Abstracts\Http\PaymentController;
use App\Http\Requests\Portal\InvoicePayment as PaymentRequest;

class StandardPayment extends PaymentController
{
    public $alias = 'payflexi';

    public $type = 'redirect';
    /**
     * Payflexi API base Url
     * @var string
     */
    protected $baseUrl;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->setBaseUrl();
    }

    public function show(Document $invoice, PaymentRequest $request)
    {
        $reference_suffix = Str::random(15);
        $reference = $invoice->id . '_' . $reference_suffix;

        $setting = $this->setting;

        $invoiceItems = $invoice->items;

        $products = '';
        foreach ($invoiceItems as $item_id => $item ) {
            $name     = $item['name'];
            $quantity = $item['quantity'];
            $price    = $item['price'];
            $products .= $name . ' (Qty: ' . $quantity . ')' . ' (Price: ' . $price . ')';
            $products .= ' | ';
        }
        $products = rtrim( $products, ' | ' );

        $callback_url = URL::signedRoute('signed.payflexi.invoices.confirm', [$invoice->id, 'company_id' => company_id()]);

        $html = view('payflexi::standard', compact('setting', 'invoice', 'products', 'reference', 'callback_url'))->render();

        return response()->json([
            'api_public_key' => ($setting['mode'] == 'test') ? $setting['api_test_public_key'] : $setting['api_live_public_key'],
            'name' => $setting['name'],
            'description' => isset($setting['description']) ? $setting['description'] : trans('payflexi::general.description'),
            'redirect' => false,
            'html' => $html,
        ]);
    }
    /**
     * Initiate Transaction and Redirect to PayFlexi for Payment
     */
    public function initiateTransaction(Request $request)
    {
        $alias = $this->alias;
       
        $company_id = (int) $request->route('company_id');

        company($company_id)->makeCurrent();

        $setting = setting($alias);

        $data = [
            'amount' => request()->amount,
            'reference' => request()->reference,
            'currency' => request()->currency,
            'email' => request()->email,
            'name' => request()->name,
            'meta' => request()->meta,
            'callback_url' => request()->callback_url,
			'domain'  => 'global',
        ];

        $url = "merchants/transactions";

        $secretKey = ($setting['mode'] == 'test') ? $setting['api_test_secret_key'] : $setting['api_live_secret_key'];

        try {
            $client = new Client(
                [
                    'base_uri' => $this->baseUrl,
                    'headers' => [
                        'Authorization' => 'Bearer '.  $secretKey,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json'
                    ],
                    'verify' => false
                ]
            );
            $result_temp = $client->request('POST', $this->baseUrl.$url, ['json'=> $data]);
            $result = json_decode($result_temp->getBody());
            if ($result->errors == false) {
                return redirect($result->checkout_url);
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = json_decode($e->getResponse()->getBody(true));
            $response->gateway_response = $response->message;
            return ['status' => 'error', 'data'=> $response];
        }
    }
    /**
     * Return from PayFlexi and Confirm the transaction
     *
     */
    public function confirm(Document $invoice, Request $request)
    {
        $invoice_url = $this->getInvoiceUrl($invoice);

        if ($request->has('pf_cancelled')) {
            $message = trans('messages.error.added', ['type' => trans_choice('general.payments', 1)]);
            flash($message)->warning();
            return redirect($invoice_url);
        }

        if ($request->has('pf_declined')) {
            $message = trans('messages.error.added', ['type' => trans_choice('general.payments', 1)]);
            flash($message)->warning();
            return redirect($invoice_url);
        }

        if ($request->has('pf_approved')) {
            $transactionRef = $request->input('pf_approved');
        }

        $setting = setting('payflexi');

        $url = 'merchants/transactions/'. $transactionRef;
        
        $secretKey = ($setting['mode'] == 'test') ? $setting['api_test_secret_key'] : $setting['api_live_secret_key'];

        try {
            $client = new Client(
                [
                    'base_uri' => $this->baseUrl,
                    'headers' => [
                        'Authorization' => 'Bearer '.  $secretKey,
                        'Content-Type'  => 'application/json',
                        'Accept'        => 'application/json'
                    ],
                    'verify' => false
                ]
            );
           
            $result_temp = $client->request('GET', $this->baseUrl.$url);
            $result = json_decode($result_temp->getBody());
            if ($result->errors == false) {
          
                $total_amount_paid = (double) $result->data->txn_amount;
                
                $request['amount'] = $total_amount_paid;
                $request['reference'] = $result->data->reference;
     
                event(new PaymentReceived($invoice, $request->merge(['type' => 'income'])));
                
                $message = trans('payflexi::general.placed', [
                    'type' => trans_choice('general.payments', 1)
                ]);
                
                flash($message)->success();
                
                return redirect($this->getInvoiceUrl($invoice));
            }
        } catch (\GuzzleHttp\Exception\BadResponseException $e) {
            $response = json_decode($e->getResponse()->getBody(true));
            $response->gateway_response = $response->message;
            return ['status' => 'error', 'data'=> $response];
        }

    }
    /**
     * Get Base Url
     */
    public function setBaseUrl()
    {
        $this->baseUrl = 'https://api.payflexi.co/';
    }

}
