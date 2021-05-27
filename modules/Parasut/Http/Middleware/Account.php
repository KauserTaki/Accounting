<?php

namespace Modules\Parasut\Http\Middleware;

use App\Traits\Jobs;

use Closure;

use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Jobs\CustomFields\Create as CreateCustomFields;

class Account
{
    use CustomFields, Jobs;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($this->isCustomFields() && !setting('parasut.custom_fields.account', false)) {
            $custom_fields = $this->getCustomFields();

            if ($custom_fields) {
                foreach ($custom_fields as $custom_field) {
                    $this->ajaxDispatch(new CreateCustomFields($custom_field));
                }
            }

            setting()->set('parasut.custom_fields.account', true);
            setting()->save();
        }

        return $next($request);
    }

    protected function getCustomFields()
    {
        // Locations {9: Accounts}
        $fields = [];

        // Accounts
        # Bank Branch
        $fields[] = [
            'name' => trans('parasut::custom_fields.accounts.bank_branch'),
            'code' => 'bank_branch',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '9',
            'sort' => 'bank_name',
            'order' => 'input_end',
            'icon' => 'map-marker',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Iban
        $fields[] = [
            'name' => trans('parasut::custom_fields.accounts.iban'),
            'code' => 'iban',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '9',
            'sort' => 'bank_address',
            'order' => 'input_end',
            'icon' => 'hashtag',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Account Type
        $fields[] = [
            'name' => trans('parasut::custom_fields.accounts.account_type'),
            'code' => 'account_type',
            'type_id' => '1',
            'rule' => '',
            'values' => [
                '0' => trans('parasut::custom_fields.accounts.account_types.cash'),
                '1' => trans('parasut::custom_fields.accounts.account_types.bank'),
                '2' => trans('parasut::custom_fields.accounts.account_types.sys'),
            ],
            'location_id' => '9',
            'sort' => 'iban',
            'order' => 'input_end',
            'icon' => 'gavel',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        return $fields;
    }
}
