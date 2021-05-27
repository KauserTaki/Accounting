<?php

namespace Modules\Parasut\Jobs\Purchase;

use App\Abstracts\Job;

use App\Models\Banking\Transaction;
use App\Models\Setting\Category;
use App\Models\Setting\Currency;

use App\Jobs\Banking\CreateTransaction as CoreCreateTransaction;
use App\Jobs\Banking\UpdateTransaction as CoreUpdateTransaction;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Modules\Parasut\Jobs\Setting\CreateCategory;

use Date;

class CreatePayment extends Job
{
    use Remote, CustomFields;

    protected $payment;

    /**
     * Create a new job instance.
     *
     * @param  $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        // Currencies
        $codes = [
            'TRL' => 'TRY',
            'USD' => 'USD',
            'EUR' => 'EUR',
            'GBP' => 'GBP',
        ];

        $code = $codes[$this->payment->data->attributes->currency];

        $currency = Currency::where('code', $code)->first();

        if (empty($currency)) {
            $currency = Currency::where('code', setting('default.currency'))->first();
        }

        $vendor = $this->getVendor();

        $payment = $this->payment->data->attributes;

        $account_id = setting('default.account');
        $payment_method = setting('default.payment_method');
        $currency_rate = $payment->net_total / $payment->net_total_in_trl;
        $paid_at = Date::parse($payment->issue_date)->format('Y-m-d H:i:s');

        $data = [
            'company_id'     => company_id(),
            'type'           => 'expense',
            'account_id'     => $account_id,
            'paid_at'        => $paid_at,
            'amount'         => $payment->net_total,
            'currency_code'  => $currency->code,
            'currency_rate'  => $currency_rate,
            'document_id'    => isset($this->payment->id) ? $this->payment->id : null , //Bill ID
            'contact_id'     => !empty($vendor->id) ? $vendor->id : null,
            'description'    => $payment->description,
            'category_id'    => $this->getCategoryId(),
            'payment_method' => $payment_method,
            'reference'      => $this->payment->data->id,
            'parent_id'      => 0,
        ];

        $request = request();
        $request->merge($data);

        $payment = Transaction::where('reference', $this->payment->data->id)->first();

        if (empty($payment)) {
            $payment = $this->dispatch(new CoreCreateTransaction($request));
        } else {
            $this->dispatch(new CoreUpdateTransaction($payment, $request));

            if ($this->isCustomFields()) {
                $update = new \Modules\CustomFields\Observers\Banking\Transaction();

                $update->updated($payment);
            }
        }

        return $payment;
    }

    protected function getVendor()
    {
        $vendor = false;

        $relation = $this->payment->data->relationships;

        if (!empty($relation->pay_to->data)) {
            $contact = $this->getContact($relation->pay_to->data->id);

            $vendor = $this->dispatch(new CreateVendor($contact->data));
        }

        return $vendor;
    }

    protected function getCategoryId()
    {
        $category_id = Category::type('expense')->pluck('id')->first();

        $relation = $this->payment->data->relationships;

        if (!empty($relation->category->data)) {
            $_category = $this->getCategory($relation->category->data->id);

            $category = $this->dispatch(new CreateCategory($_category->data));

            $category_id = $category->id;
        }

        return $category_id;
    }
}
