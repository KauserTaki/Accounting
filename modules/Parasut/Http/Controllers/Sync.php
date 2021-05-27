<?php

namespace Modules\Parasut\Http\Controllers;


use Illuminate\Routing\Controller;

use Modules\Parasut\Jobs\Banking\CreateAccount;
use Modules\Parasut\Jobs\Purchase\CreateBill;
use Modules\Parasut\Jobs\Setting\CreateCategory;
use Modules\Parasut\Jobs\Sale\CreateCustomer;
use Modules\Parasut\Jobs\Purchase\CreateVendor;
use Modules\Parasut\Jobs\Purchase\CreateEmployee;
use Modules\Parasut\Jobs\Sale\CreateInvoice;
use Modules\Parasut\Jobs\Common\CreateItem;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Date;
use Cache;

class Sync extends Controller
{
    use Remote, CustomFields;

    public function count()
    {
        $account_total =  $this->getTotalAccounts();
        $bill_total =  $this->getTotalBills();
        $category_total =  $this->getTotalCategories();
        $contact_total =  $this->getTotalContacts();
        $employee_total =  $this->getTotalEmployees();
        $invoice_total =  $this->getTotalInvoices();
        $product_total =  $this->getTotalInvoices();

        $total = $account_total + $bill_total + $category_total + $contact_total + $employee_total + $invoice_total + $product_total;

        $custom_fields = $this->checkCustomFields(false, 'all');

        $all = true;

        $html = view('parasut::partial.sync', compact('all', 'account_total', 'bill_total', 'category_total', 'contact_total', 'employee_total', 'invoice_total', 'product_total', 'custom_fields'))->render();

        $json = [
            'custom_fields' => $custom_fields,
            'errors' => false,
            'success' => true,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $customers = $products = $orders = $steps = [];

        do {
            $results = $this->getCustomers('findAll', ['page' => $page]);

            if (!empty($results)) {
                foreach ($results as $result) {
                    $id = $result->getId() * -1;
                    $customers[$id] = $result;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', ['type' => trans_choice('parasut::general.types.customer', 1), 'value' => $result->getFirstName() . ' ' . $result->getLastName()]),
                        'url'  => url('parasut/customers/sync/' . $id)
                    ];
                }
            }

            $page++;
        } while (!empty($results));

        // Set parasut customers
        session(['parasut_customers' => $customers]);
        Cache::put('parasut_customers', $customers, Date::now()->addHour(6));

        // Start Product Steps
        $page = 1;

        do {
            $results = $this->getProducts('findAll', ['page' => $page]);

            if (!empty($results)) {
                foreach ($results as $result) {
                    $products[$result->getHandle()] = $result;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', ['type' => trans_choice('parasut::general.types.product', 1), 'value' => $result->getTitle()]),
                        'url'  => url('parasut/products/sync/' . $result->getHandle())
                    ];
                }
            }

            $page++;
        } while (!empty($results));

        // Set parasut products
        session(['parasut_products' => $products]);
        Cache::put('parasut_products', $products, Date::now()->addHour(6));

        // Start Order Steps
        $page = 1;

        do {
            $results = $this->getOrders('findAll', ['page' => $page]);

            if (!empty($results)) {
                foreach ($results as $result) {
                    $orders[$result->getOrderNumber()] = $result;

                    $order_steps[] = [
                        'text' => trans('parasut::general.sync_text', ['type' => trans_choice('parasut::general.types.order', 1), 'value' => $result->getName()]),
                        'url'  => url('parasut/orders/sync/' . $result->getOrderNumber())
                    ];
                }
            }

            $page++;
        } while (!empty($results));

        krsort($orders);
        krsort($order_steps);
        sort($order_steps);

        foreach ($order_steps as $order_step) {
            $steps[] = $order_step;
        }

        // Set parasut orders
        session(['parasut_orders' => $orders]);
        Cache::put('parasut_orders', $orders, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($customers),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    protected function getTotalAccounts()
    {
        $total = 0;

        $accounts = $this->getAccounts();

        if ($accounts) {
            $total = $accounts->meta->total_count;
        }

        return $total;
    }

    protected function getTotalBills()
    {
        $total = 0;

        $bills = $this->getBills();

        if ($bills) {
            $total = $bills->meta->total_count;
        }

        return $total;
    }

    protected function getTotalCategories()
    {
        $total = 0;

        $categories = $this->getCategories();

        if ($categories) {
            $total = $categories->meta->total_count;
        }

        return $total;
    }

    protected function getTotalContacts()
    {
        $total = 0;

        $contacts = $this->getContacts();

        if ($contacts) {
            $total = $contacts->meta->total_count;
        }

        return $total;
    }

    protected function getTotalEmployees()
    {
        $total = 0;

        $employees = $this->getEmployees();

        if ($employees) {
            $total = $employees->meta->total_count;
        }

        return $total;
    }

    protected function getTotalInvoices()
    {
        $total = 0;

        $invoices = $this->getInvoices();

        if ($invoices) {
            $total = $invoices->meta->total_count;
        }

        return $total;
    }

    protected function getTotalProducts()
    {
        $total = 0;

        $items = $this->getProducts();

        if ($items) {
            $total = $items->meta->total_count;
        }

        return $total;
    }
}
