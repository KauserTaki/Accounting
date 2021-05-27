<?php

namespace Modules\Parasut\Jobs\Sale;

use App\Abstracts\Job;

use App\Http\Requests\Sale\Invoice as Request;
use App\Http\Requests\Banking\Transaction as TransactionRequest;

use App\Jobs\Banking\CreateDocumentTransaction;
use App\Jobs\Sale\CreateInvoice as BaseCreateInvoice;
use App\Jobs\Sale\UpdateInvoice as BaseUpdateInvoice;
use App\Jobs\Sale\CreateInvoicePayment;

use App\Models\Banking\Account;
use App\Models\Sale\Invoice;
use App\Models\Setting\Currency;
use App\Models\Setting\Tax;
use App\Models\Setting\Category;

use App\Traits\Sales;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Modules\Parasut\Jobs\Common\CreateItem;
use Modules\Parasut\Jobs\Setting\CreateCategory;
use Modules\Parasut\Jobs\Setting\CreateTax;

use Illuminate\Database\Eloquent\Collection;

use Date;

class CreateInvoice extends Job
{
    use Remote, Sales, CustomFields;

    protected $invoice;

    protected $currency;

    /**
     * Create a new job instance.
     *
     * @param  $invoice
     */
    public function __construct($invoice)
    {
        $this->invoice = $invoice;
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

        $code = $codes[$this->invoice->currency];

        $this->currency = Currency::where('code', $code)->first();

        if (empty($this->currency)) {
            $this->currency = Currency::where('code', setting('default.currency'))->first();
        }

        return $this->createInvoice($this->getCustomer());
    }

    protected function getCustomer()
    {
        $contact = false;

        $relation = $this->invoice->contact;

        if (!empty($relation->id)) {
            $contact = $this->getContact($relation->id);

            $contact = $this->dispatch(new CreateCustomer($contact->data));
        }

        return $contact;
    }

    protected function createInvoice($contact)
    {
        $invoice_number = $this->invoice->invoice_no;

        if (empty($invoice_number)) {
            $invoice_number = $this->invoice->id;

            $column = 'order_number';
        } else {
            $column = 'invoice_number';
        }

        $invoice = Invoice::where($column, $invoice_number)->first();

        if ($invoice) {
            $this->deleteRelationships($invoice, ['items', 'itemTaxes', 'histories', 'transactions', 'recurring', 'totals']);
        }

        $items = [];
        $otv = $oiv = $kdv = 0;
        $discount = 0;
        $discount_amount = 0;

        if ($this->invoice->details) {
            foreach ($this->invoice->details as $invoice_item) {
                $item = false;

                if ($invoice_item->product) {
                    $product = $this->getProduct($invoice_item->product->id);

                    $item = $this->dispatch(new CreateItem($product->data));
                }

                if ($invoice_item->excise_duty) {
                    $otv += $invoice_item->excise_duty;

                    $taxes[] = $this->getOTV($invoice_item->excise_duty_rate);

                    $taxes[] = $this->getTax($invoice_item->vat_rate);

                    $kdv += (($invoice_item->unit_price + $invoice_item->excise_duty_rate) * $invoice_item->vat_rate) / 100;
                } else {
                    $taxes[] = $this->getTax($invoice_item->vat_rate);

                    $kdv += ($invoice_item->unit_price * $invoice_item->vat_rate) / 100;
                }

                if ($invoice_item->communications_tax) {
                    $taxes[] = $this->getOIV($invoice_item->communications_tax_rate);

                    $oiv += $invoice_item->communications_tax;
                }

                $items[] = [
                    'name' => ($item) ? $item->name : $invoice_item->product->name,
                    'item_id' => ($item) ? $item->id : null,
                   // 'sku' => ($item) ? $item->sku : kebab_case($invoice_item->product->name),
                    'price' => $invoice_item->unit_price,
                    'quantity' => $invoice_item->quantity,
                    'currency' => $this->currency->code,
                    'tax_id' => $taxes,
                ];

                if ($invoice_item->discount_rate > 0) {
                    $discount_amount += $invoice_item->discount;

                    if ($invoice_item->discount_type == 'percentage') {
                        $discount = $invoice_item->discount_rate;
                    } else {
                        $discount = 0;
                    }
                }
            }
        }

        $invoiced_at = Date::parse($this->invoice->issue_date)->format('Y-m-d H:i:s');
        $due_at = Date::parse($this->invoice->due_date)->format('Y-m-d H:i:s');

        if ($this->invoice->invoice_series || $this->invoice->invoice_id) {
            $invoice_number = $this->invoice->invoice_series . ' ' . $this->invoice->invoice_id;
        } else {
            $invoice_number = $this->getNextInvoiceNumber();
        }

        $recurring = ($this->invoice->is_recurring) ? 'true' : 'no';

        $invoice_data =  [
            'company_id' => company_id(),
            'contact_id' => $contact->id,
            'amount' => $this->invoice->net_total,
            'invoiced_at' => $invoiced_at,
            'due_at' => $due_at,
            'invoice_number' => $invoice_number,
            'order_number' => $this->invoice->id,
            'currency_code' => $this->currency->code,
            'currency_rate' => $this->currency->rate,
            'items' => $items,
            'discount' => $discount,
            'notes' => $this->invoice->description,
            'category_id' => $this->getCategoryId(),
            'recurring_frequency' => $recurring,
            'contact_name' =>  $contact->name,
            'contact_email' => $contact->email,
            'contact_tax_number' => $contact->tax_number,
            'contact_phone' =>  $contact->phone,
            'contact_address' =>  $contact->address,
            'status' => 'draft',
            'totals' => $this->getTotals($discount_amount, $otv, $oiv, $kdv),
        ];

        $invoice_request = new Request();
        $invoice_request->merge($invoice_data);

        if ($invoice) {
            $this->dispatch(new BaseUpdateInvoice($invoice, $invoice_request));
        } else {
            $invoice = $this->dispatch(new BaseCreateInvoice($invoice_request));
        }

        $invoice->amount = $this->invoice->net_total;
        $invoice->save();

        // Mark paid
        $paid = 0;

        if ($this->invoice->payments) {
            if ($this->invoice->payment_status == 'paid') {
                $invoice->status = 'paid';
            } else {
                $invoice->status = 'partial';
            }

            $invoice->save();

            foreach ($this->invoice->payments as $payment) {
                $account_id = setting('default.account');

                $account = Account::where('name', $payment->payment_account_name)->first();

                if ($account) {
                    $account_id = $account->id;
                }

                $currency_rate = $payment->matched_amount / $payment->amount;
                $date = Date::parse($payment->date)->format('Y-m-d H:i:s');

                // Currencies
                $codes = [
                    'TRL' => 'TRY',
                    'USD' => 'USD',
                    'EUR' => 'EUR',
                    'GBP' => 'GBP',
                ];

                $code = $codes[$payment->currency];

                $payment_data = [
                    'company_id' =>  $invoice->company_id,
                    'type' => 'income',
                    'document_id' => $invoice->id,
                    'account_id' => $account_id,
                    'currency_code' => $code,
                    'currency_rate' => $currency_rate,
                    'amount' => $payment->amount,
                    'paid_at' => $date,
                    'payment_method' => setting('default.payment_method'),
                    'reference' => 'payable-id:' . $payment->payable_id,
                ];

                $payment_request = new TransactionRequest();
                $payment_request->merge($payment_data);

                $invoice_payment = $this->dispatch(new CreateDocumentTransaction($invoice, $payment_request));

                $paid += $payment->amount;
            }
        }

        if ($paid == $invoice->amount) {
            $invoice->status = 'paid';

            $invoice->save();
        }

        return $invoice;
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

    protected function getCategoryId()
    {
        $category_id = Category::type('income')->pluck('id')->first();

        if (!empty($this->invoice->category)) {
            $relation = $this->invoice->category;

            if (!empty($relation->id)) {
                $_category = $this->getCategory($relation->id);

                $category = $this->dispatch(new CreateCategory($_category->data));

                $category_id = $category->id;
            }
        }

        return $category_id;
    }

    protected function getTotals($discount, $otv, $oiv, $kdv)
    {
        $totals = [];

        /*
        $totals[] = [
            'company_id' => company_id(),
            'invoice_id' => 0,
            'code' => 'sub_total',
            'name' => 'invoices.sub_total',
            'amount' => $this->invoice->gross_total,
            'sort_order' => 1,
        ];
        */

        // Added invoice discount
        if ($discount) {
            $totals[] = [
                'company_id' => company_id(),
                'invoice_id' => 0,
                'code' => 'discount',
                'name' => 'invoices.discount',
                'amount' => $this->invoice->total_discount,
                'sort_order' => 2,
                'operator' => 'subtraction'
            ];
        }

        /*
        if (!empty($otv)) {
            $totals[] = [
                'company_id' => company_id(),
                'invoice_id' => 0,
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
                'invoice_id' => 0,
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
        //         'invoice_id' => 0,
        //         'code' => 'tax',
        //         'name' => 'KDV',
        //         'amount' => $kdv,
        //         'sort_order' => 5,
        //     ];
        // }

        /*
        // Added invoice total
        $totals[] = [
            'company_id' => company_id(),
            'invoice_id' =>0,
            'code' => 'total',
            'name' => 'invoices.total',
            'amount' => $this->invoice->net_total,
            'sort_order' => 6,
        ];
        */

        return $totals;
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
