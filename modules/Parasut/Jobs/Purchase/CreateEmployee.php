<?php

namespace Modules\Parasut\Jobs\Purchase;

use App\Abstracts\Job;

use App\Models\Common\Contact;
use App\Models\Setting\Currency;

use App\Jobs\Common\CreateContact as CoreCreateContact;
use App\Jobs\Common\UpdateContact as CoreUpdateContact;

use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Traits\Payroll;

class CreateEmployee extends Job
{
    use CustomFields, Payroll;

    protected $employee;

    protected $currency;

    /**
     * Create a new job instance.
     *
     * @param  $employee
     */
    public function __construct($employee)
    {
        $this->employee = $employee;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->currency = Currency::where('code', setting('default.currency'))->first();

        return $this->createVendor();
    }

    protected function createVendor()
    {
        $employee = $this->employee->attributes;

        $employee_type = 0;

        if ($this->isCustomFields()) {
            $employee_type_value = trans('parasut::custom_fields.contacts.contact_types.' . $this->employee->type);

            $custom_field = \Modules\CustomFields\Models\Field::where('locations', '8')
                ->where('code', 'contact_type')
                ->first();

            $custom_field_options = $custom_field->fieldTypeOption->pluck('value', 'id');

            if ($custom_field_options) {
                foreach ($custom_field_options as $id => $value) {
                    if ($value == $employee_type_value) {
                        $employee_type = $id;
                    }
                }
            }
        }

        $data = [
            'company_id' => company_id(),
            'contact_type' => $employee_type,
            'type' => 'vendor',
            'name' => $employee->name,
            'email' => $employee->email,
            'balances' => $this->getBalances($employee),
            'currency_code' => $this->currency->code,
            'tax_number' => $employee->tckn,
            'tax_office' => null,
            'address' => null,
            'district' => null,
            'city' => null,
            'phone' => null,
            'reference' => 'calisan -' . $this->employee->id,
            'iban' => $employee->iban
        ];

        $request = request();
        $request->merge($data);

        if ($this->isPayroll()) {
            $vendor = Contact::where('reference', 'calisan -' . $this->employee->id)->first();

            if ($vendor) {
                $employee = \Modules\Payroll\Employee\Employee::where('contact_id', $vendor->id)->first();
            }

            if (empty($employee)) {
                $employee = $this->dispatch(new \Modules\Payroll\Jobs\Employee\CreateEmployee($request));
            } else {
                $this->dispatch(new \Modules\Payroll\Jobs\Employee\UpdateEmployee($employee, $request));

                if ($this->isCustomFields()) {
                    $update = new \Modules\CustomFields\Observers\Common\Contact();

                    $update->updated($employee);
                }
            }

            return $employee;
        }

        $vendor = Contact::where('reference', 'calisan -' . $this->employee->id)->first();

        if (empty($vendor)) {
            $vendor = $this->dispatch(new CoreCreateContact($request));
        } else {
            $this->dispatch(new CoreUpdateContact($vendor, $request));

            if ($this->isCustomFields()) {
                $update = new \Modules\CustomFields\Observers\Common\Contact();

                $update->updated($vendor);
            }
        }

        return $vendor;
    }

    protected function getBalances($employee)
    {
        $balance = $employee->balance;

        if (isset($employee->trl_balance)) {
            $balance .= "\n" .'TRY : ' . $employee->trl_balance;
        }

        if (isset($employee->usd_balance)) {
            $balance .= "\n" .'USD : ' . $employee->usd_balance;
        }

        if (isset($employee->eur_balance)) {
            $balance .= "\n" .'EUR : ' . $employee->eur_balance;
        }

        if (isset($employee->gbp_balance)) {
            $balance .= "\n" .'GBP : ' . $employee->gbp_balance;
        }

        return $balance;
    }
}
