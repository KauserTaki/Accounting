<?php

namespace Modules\Parasut\Jobs\Purchase;
use App\Abstracts\Job;

use App\Http\Requests\Purchase\Bill as Request;
use App\Http\Requests\Banking\Transaction as TransactionRequest;
use App\Jobs\Banking\CreateDocumentTransaction;
use App\Jobs\Purchase\CreateBill as BaseCreateBill;
use App\Jobs\Purchase\UpdateBill as BaseUpdateBill;
use App\Jobs\Banking\CreateTransaction;

use App\Models\Banking\Account;
use App\Models\Purchase\Bill;
use App\Models\Setting\Currency;
use App\Models\Setting\Tax;
use App\Models\Setting\Category;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Modules\Parasut\Jobs\Common\CreateItem;
use Modules\Parasut\Jobs\Setting\CreateCategory;
use Modules\Parasut\Jobs\Setting\CreateTax;

use Illuminate\Database\Eloquent\Collection;

use Date;

class CreateBill extends Job
{
    use Remote, CustomFields;

    protected $bill;

    protected $currency;

    /**
     * Create a new job instance.
     *
     * @param  $bill
     */
    public function __construct($bill)
    {
        $this->bill = $bill;
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

        $code = $codes[$this->bill->attributes->currency];

        $this->currency = Currency::where('code', $code)->first();

        if (empty($this->currency)) {
            $this->currency = Currency::where('code', setting('default.currency'))->first();
        }

        $parameters['include'] = 'category,spender,details,details.product,payments,payments.transaction,tags,recurrence_plan,active_e_document,pay_to';

        $bill = $this->getBill($this->bill->id, $parameters);

        if (empty($this->bill->relationships->details->data)) {
            return $this->dispatch(new CreatePayment($bill));
        }

        return $this->createBill($this->getVendor(), $bill);
    }

    protected function getVendor()
    {
        $vendor = false;

        $relation = $this->bill->relationships;

        if (!empty($relation->pay_to->data)) {
            $contact = $this->getContact($relation->pay_to->data->id);

            $vendor = $this->dispatch(new CreateVendor($contact->data));
        }

        return $vendor;
    }

    // $data == bill
    protected function createBill($vendor, $data)
    {
        $prepaire_bill = $data->data->attributes;
        $prepaire_bill_included = $data->included;

        $bill_number = $prepaire_bill->invoice_no;

        if (empty($bill_number)) {
            $bill_number = $data->data->id;

            $column = 'order_number';
        } else {
            $column = 'bill_number';
        }

        $bill = Bill::where($column, $bill_number)->first();

        if ($bill) {
            $this->deleteRelationships($bill, ['items', 'itemTaxes', 'histories', 'transactions', 'recurring', 'totals']);
        }

        $items = [];
        $bill_details = [];
        $products = [];
        $payments = [];
        $otv = $oiv = $kdv = 0;
        $discount = 0;
        $discount_amount = 0;
        $category = false;

        if ($prepaire_bill_included) {
            foreach ($prepaire_bill_included as $bill_included) {
                switch ($bill_included->type) {
                    case 'item_categories':
                        $category = $bill_included;
                        break;
                    case 'purchase_bill_details':
                        $bill_details[] = $bill_included;
                        break;
                    case 'products':
                        $products[] = $bill_included;
                        break;
                    case 'payments':
                        $payments[] = $bill_included;
                        break;
                    case 'transactions':
                        break;
                    #Already get Vendor.
                    /*case 'contacts':
                        $vendor = $bill_included->attributes;
                        break;*/
                }
            }
        }

        $bill_products = $this->getBillProducts($products);

        if ($bill_details) {
            foreach ($bill_details as $bill_detail) {
                $bill_detail_attributes = $bill_detail->attributes;

                $item = $bill_products[$bill_detail->relationships->product->data->id];

                $taxes= [];

                if ($bill_detail_attributes->excise_duty) {
                    $otv += $bill_detail_attributes->excise_duty_value;

                    $taxes[] = $this->getOTV($bill_detail_attributes->excise_duty);

                    $taxes[] = $this->getTax($bill_detail_attributes->vat_rate);

                    $kdv += (($bill_detail_attributes->unit_price + $bill_detail_attributes->excise_duty_value) * $bill_detail_attributes->vat_rate) / 100;
                } else {
                    $taxes[] = $this->getTax($bill_detail_attributes->vat_rate);

                    $kdv += ($bill_detail_attributes->unit_price * $bill_detail_attributes->vat_rate) / 100;
                }

                if ($bill_detail_attributes->communications_tax) {
                    $taxes[] = $this->getOIV($bill_detail_attributes->communications_tax_rate);

                    $oiv += $bill_detail_attributes->communications_tax;
                }

                $items[] = [
                    'name' => $item->name,
                    'item_id' => $item->id,
                    //'sku' => $item->sku,
                    'price' => $item->purchase_price,
                    'quantity' => $bill_detail_attributes->quantity,
                    'currency' => $this->currency->code,
                    'tax_id' => $taxes,
                ];

                if ($bill_detail_attributes->discount_value > 0) {
                    $discount_amount += $bill_detail_attributes->discount;

                    if ($bill_detail_attributes->discount_type == 'percentage') {
                        $discount = $bill_detail_attributes->discount_value;
                    } else {
                        $discount = 0;
                    }
                }
            }
        }

        $billed_at = Date::parse($prepaire_bill->issue_date)->format('Y-m-d H:i:s');
        $due_at = Date::parse($prepaire_bill->due_date)->format('Y-m-d H:i:s');

        $currency_rate = $prepaire_bill->net_total / $prepaire_bill->net_total_in_trl;

        $bill_data =  [
            'company_id' => company_id(),
            'contact_id' => $vendor->id,
            'amount' => $prepaire_bill->net_total,
            'billed_at' => $billed_at,
            'due_at' => $due_at,
            'bill_number' => $bill_number,
            'order_number' => $data->data->id,
            'currency_code' => $this->currency->code,
            'currency_rate' => $currency_rate,
            'items' => $items,
            'discount' => $discount,
            'notes' => $prepaire_bill->description,
            'category_id' => $this->getCategoryId($category),
            'recurring_frequency' => false,
            'contact_name' =>  $vendor->name,
            'contact_email' => $vendor->email,
            'contact_tax_number' => $vendor->tax_number,
            'contact_phone' =>  $vendor->phone,
            'contact_address' =>  $vendor->address,
            'status' => 'draft',
            'totals' => $this->getTotals($prepaire_bill, $discount_amount, $otv, $oiv, $kdv),
        ];

        $bill_request = new Request();
        $bill_request->merge($bill_data);

        if ($bill) {
            $this->dispatch(new BaseUpdateBill($bill, $bill_request));
        } else {
            $bill = $this->dispatch(new BaseCreateBill($bill_request));
        }

        $bill->amount = $prepaire_bill->net_total;
        $bill->save();

        // Mark paid
        $paid = 0;
        if ($payments) {
            if ($prepaire_bill->payment_status == 'paid') {
                $bill->status = 'paid';
            } else {
                $bill->status = 'partial';
            }

            $bill->save();

            $account_id = setting('default.account');

            foreach ($payments as $payment) {
                $payment_attributes = $payment->attributes;

                $date = Date::parse($payment_attributes->date)->format('Y-m-d H:i:s');

                $payment_data = [
                    'company_id'     => $bill->company_id,
                    'type'           => 'expense',
                    'account_id'     => $account_id,
                    'currency_code'  => $this->currency->code,
                    'currency_rate'  => $currency_rate,
                    'document_id'    => $bill->id,
                    'amount'         => $payment_attributes->amount,
                    'paid_at'        => $date,
                    'payment_method' => setting('default.payment_method'),
                    'description'    => $payment_attributes->notes,
                    'reference'      => 'payable-id:' . $payment->id,
                ];

                $payment_request = new TransactionRequest();
                $payment_request->merge($payment_data);

                $bill_payment = $this->dispatch(new CreateDocumentTransaction($bill, $payment_request));

                $paid += $payment->amount;
            }
        }

        if ($paid == $bill->amount) {
            $bill->status = 'paid';

            $bill->save();
        }

        return $bill;
    }

    protected function getTax($rate)
    {
        $tax = Tax::where('rate', (int) $rate)->first();

        if ($tax) {
            return $tax->id;
        }

        $tax = $this->dispatch(new CreateTax([
            'company_id' => company_id(),
            'name' => '%' . (int) $rate . ' KDV',
            'rate' => (int) $rate,
            'type' => 'normal',
            'enabled' => '1',
        ]));

        return $tax->id;
    }

    protected function getOTV($rate)
    {
        $tax = Tax::where('name', '%' . (int) $rate . ' ÖTV')->where('rate', (int) $rate)->first();

        if ($tax) {
            return $tax->id;
        }

        $tax = $this->dispatch(new CreateTax([
            'company_id' => company_id(),
            'name' => '%' . (int) $rate . ' ÖTV',
            'rate' => $rate,
            'type' => 'compound',
            'enabled' => '1',
        ]));

        return $tax->id;
    }

    protected function getOIV($rate)
    {
        $tax = Tax::where('name', '%' . (int) $rate . ' ÖİV')->where('rate', (int) $rate)->first();

        if ($tax) {
            return $tax->id;
        }

        $tax = $this->dispatch(new CreateTax([
            'company_id' => company_id(),
            'name' => '%' . (int) $rate . ' ÖİV',
            'rate' => $rate,
            'type' => 'compound',
            'enabled' => '1',
        ]));

        return $tax->id;
    }

    protected function getCategoryId($category)
    {
        $category_id = Category::type('expense')->pluck('id')->first();

        if (!empty($category)) {
            $get_category = $this->dispatch(new CreateCategory($category));

            $category_id = $get_category->id;
        }

        return $category_id;
    }

    protected function getTotals($bill_data, $discount, $otv, $oiv, $kdv)
    {
        $totals = [];

        // $totals[] = [
        //     'company_id' => company_id(),
        //     'bill_id' => 0,
        //     'code' => 'sub_total',
        //     'name' => 'bills.sub_total',
        //     'amount' => $bill_data->gross_total,
        //     'sort_order' => 1,
        // ];

        // Added bill discount
        if ($discount) {
            $totals[] = [
                'company_id' => company_id(),
                'bill_id' => 0,
                'code' => 'discount',
                'name' => 'bills.discount',
                'amount' => $bill_data->total_discount,
                'sort_order' => 2,
                'operator' => 'subtraction'
            ];
        }

        /*
        if (!empty($otv)) {
            $totals[] = [
                'company_id' => company_id(),
                'bill_id' => 0,
                'code' => 'tax',
                'name' => 'ÖTV',
                'amount' => $otv,
                'sort_order' => 3,
            ];
        }
        */

        /*
        if (!empty($oiv)) {
            $totals[] = [
                'company_id' => company_id(),
                'bill_id' => 0,
                'code' => 'tax',
                'name' => 'ÖİV',
                'amount' => $oiv,
                'sort_order' => 4,
            ];
        }
        */

        // if (!empty($kdv)) {
        //     $totals[] = [
        //         'company_id' => company_id(),
        //         'bill_id' => 0,
        //         'code' => 'tax',
        //         'name' => 'KDV',
        //         'amount' => $kdv,
        //         'sort_order' => 5,
        //     ];
        // }

        // // Added bill total
        // $totals[] = [
        //      'company_id' => company_id(),
        //      'bill_id' =>0,
        //      'code' => 'total',
        //      'name' => 'bills.total',
        //      'amount' => $bill_data->net_total,
        //      'sort_order' => 6,
        //  ];

        return $totals;
    }

    protected function getBillProducts($products)
    {
        $result = [];

        foreach ($products as $product) {
            $item = dispatch_now(new CreateItem($product));

            $result[$product->id] = $item;
        }

        return $result;
    }

    /**
     * Mass delete relationships with events being fired.
     *
     * @param  $model
     * @param  $relationships
     *
     * @return void
     */
    public function deleteRelationships($model, $relationships)
    {
        foreach ((array) $relationships as $relationship) {
            if (empty($model->$relationship)) {
                continue;
            }

            $items = $model->$relationship->all();

            if ($items instanceof Collection) {
                $items = $items->all();
            }

            foreach ((array) $items as $item) {
                $item->delete();
            }
        }
    }
}
