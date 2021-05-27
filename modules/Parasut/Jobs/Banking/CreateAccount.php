<?php

namespace Modules\Parasut\Jobs\Banking;

use App\Abstracts\Job;

use App\Models\Banking\Account;
use App\Models\Setting\Currency;

use App\Jobs\Banking\CreateAccount as CoreCreateAccount;
use App\Jobs\Banking\UpdateAccount as CoreUpdateAccount;

use Modules\Parasut\Traits\CustomFields;
use Illuminate\Support\Str;

class CreateAccount extends Job
{
    use CustomFields;

    protected $account;

    protected $currency;

    /**
     * Create a new job instance.
     *
     * @param  $account
     */
    public function __construct($account)
    {
        $this->account = $account;
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

        $code = $codes[$this->account->attributes->currency];

        $this->currency = Currency::where('code', $code)->first();

        if (empty($this->currency)) {
            $this->currency = Currency::where('code', setting('default.currency'))->first();
        }

        return $this->createAccount();
    }

    protected function createAccount()
    {
        $account = $this->account->attributes;

        $account_type = 0;

        if ($this->isCustomFields()) {
            $account_type_value = trans('parasut::custom_fields.accounts.account_types.' . $account->account_type);

            $custom_field = \Modules\CustomFields\Models\Field::where('locations', '9')
                ->where('code', 'account_type')
                ->first();

            $custom_field_options = $custom_field->fieldTypeOption->pluck('value', 'id');

            if ($custom_field_options) {
                foreach ($custom_field_options as $id => $value) {
                    if ($value == $account_type_value) {
                        $account_type = $id;
                    }
                }
            }
        }

        $account_data = [
            'company_id' => company_id(),
            'account_type' => $account_type,
            'name' => $account->name,
            'number' => $this->getNumber($account),
            'currency_code' => $this->currency->code,
            'opening_balance' => $account->balance,
            'bank_name' => $account->bank_name,
            'bank_phone' => '',
            'bank_address' => '',
            'enabled' => !($account->archived) ? 1 : 0 ,
            'bank_branch' => $account->bank_branch,
            'iban' => $account->iban,
        ];

        $account_request = request();
        $account_request->merge($account_data);

        $account = Account::where('name', $account->name)
                    ->where('number', $this->getNumber($account))
                    ->first();

        if (empty($account)) {
            $account = $this->dispatch(new CoreCreateAccount($account_request));
        } else {
            $this->dispatch(new CoreUpdateAccount($account, $account_request));

            if ($this->isCustomFields()) {
                $update = new \Modules\CustomFields\Observers\Banking\Account();

                $update->updated($account);
            }
        }

        return $account;
    }

    protected function getNumber($account)
    {
        $number = $account->bank_account_no;

        if ($number) {
            return $number;
        }

        return Str::kebab($account->name);
    }
}
