<?php

namespace Modules\Payflexi\Jobs;

use App\Events\Document\PaidAmountCalculated;
use App\Models\Banking\Transaction;
use App\Traits\Currencies;
use Date;
use App\Jobs\Banking\CreateBankingDocumentTransaction;

class DocumentTransaction extends CreateBankingDocumentTransaction
{
    use Currencies;

    protected $model;

    protected $request;

    protected $transaction;

    /**
     * Create a new job instance.
     *
     * @param  $model
     * @param  $request
     */
    public function __construct($model, $request)
    {
        parent::__construct($model, $request);

        $this->model = $model;
        $this->request = $this->getRequestInstance($request);
    }

    /**
     * Execute the job.
     *
     * @return Transaction
     */
    public function handle()
    {
        parent::handle();
      
        $this->prepareRequest();
    
    }

    protected function prepareRequest()
    {
        if (!isset($this->request['amount'])) {
            $this->model->paid_amount = $this->model->paid;
            event(new PaidAmountCalculated($this->model));

            $this->request['amount'] = $this->model->amount - $this->model->paid_amount;
        }

        $currency_code = !empty($this->request['currency_code']) ? $this->request['currency_code'] : $this->model->currency_code;

        $this->request['company_id'] = $this->model->company_id;
        $this->request['currency_code'] = isset($this->request['currency_code']) ? $this->request['currency_code'] : $this->model->currency_code;
        $this->request['paid_at'] = isset($this->request['paid_at']) ? $this->request['paid_at'] : Date::now()->format('Y-m-d');
        $this->request['currency_rate'] = isset($this->request['currency_rate']) ? $this->request['currency_rate'] : $this->model->currency_rate;
        $this->request['account_id'] = isset($this->request['account_id']) ? $this->request['account_id'] : setting('default.account');
        $this->request['document_id'] = isset($this->request['document_id']) ? $this->request['document_id'] : $this->model->id;
        $this->request['contact_id'] = isset($this->request['contact_id']) ? $this->request['contact_id'] : $this->model->contact_id;
        $this->request['category_id'] = isset($this->request['category_id']) ? $this->request['category_id'] : $this->model->category_id;
        $this->request['payment_method'] = isset($this->request['payment_method']) ? $this->request['payment_method'] : setting('default.payment_method');
        $this->request['notify'] = isset($this->request['notify']) ? $this->request['notify'] : 0;
    }
}
