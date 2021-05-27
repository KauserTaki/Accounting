<?php

namespace Modules\Parasut\Http\Middleware;

use App\Traits\Jobs;

use Closure;

use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Jobs\CustomFields\Create as CreateCustomFields;

class Contact
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
        if ($this->isCustomFields() && !setting('parasut.custom_fields.contact', false)) {
            $custom_fields = $this->getCustomFields();

            if ($custom_fields) {
                foreach ($custom_fields as $custom_field) {
                    $this->ajaxDispatch(new CreateCustomFields($custom_field));
                }
            }

            setting()->set('parasut.custom_fields.contact', true);
            setting()->save();
        }

        return $next($request);
    }

    protected function getCustomFields()
    {
        // Locations {5: Customers, 8: Vendors}
        $fields = [];

        // Customers
        # Sort Name
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.short_name'),
            'code' => 'short_name',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'name',
            'order' => 'input_end',
            'icon' => 'info-circle',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Contact Type
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.contact_type'),
            'code' => 'contact_type',
            'type_id' => '1',
            'rule' => '',
            'values' => [
                '0' => trans('parasut::custom_fields.contacts.contact_types.company'),
                '1' => trans('parasut::custom_fields.contacts.contact_types.person'),
            ],
            'location_id' => '5',
            'sort' => 'email',
            'order' => 'input_end',
            'icon' => 'gavel',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Tax Office
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.tax_office'),
            'code' => 'tax_office',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'contact_type',
            'order' => 'input_end',
            'icon' => 'gavel',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Fax
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.fax'),
            'code' => 'fax',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'phone',
            'order' => 'input_end',
            'icon' => 'fax',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Iban
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.iban'),
            'code' => 'iban',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'website',
            'order' => 'input_end',
            'icon' => 'hashtag',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Ibans
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.ibans'),
            'code' => 'ibans',
            'type_id' => '5',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'iban',
            'order' => 'input_end',
            'icon' => 'hashtag',
            'class' => 'col-md-12',
            'required' => '0',
            'enabled' => '1',
        ];

        # Balances
        /*
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.balances'),
            'code' => 'balances',
            'type_id' => '5',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'ibans',
            'order' => 'input_end',
            'icon' => 'hashtag',
            'class' => 'col-md-12',
            'required' => '0',
            'enabled' => '1',
        ];
        */

        # City
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.city'),
            'code' => 'city',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'address',
            'order' => 'input_end',
            'icon' => 'city',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # District
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.district'),
            'code' => 'district',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '5',
            'sort' => 'city',
            'order' => 'input_end',
            'icon' => 'district',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        // Vendors
        # Sort Name
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.short_name'),
            'code' => 'short_name',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'name',
            'order' => 'input_end',
            'icon' => 'info-circle',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Contact Type
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.contact_type'),
            'code' => 'contact_type',
            'type_id' => '1',
            'rule' => '',
            'values' => [
                '0' => trans('parasut::custom_fields.contacts.contact_types.company'),
                '1' => trans('parasut::custom_fields.contacts.contact_types.person'),
                '2' => trans('parasut::custom_fields.contacts.contact_types.employees'),
            ],
            'location_id' => '8',
            'sort' => 'email',
            'order' => 'input_end',
            'icon' => 'gavel',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Tax Office
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.tax_office'),
            'code' => 'tax_office',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'contact_type',
            'order' => 'input_end',
            'icon' => 'gavel',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Fax
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.fax'),
            'code' => 'fax',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'phone',
            'order' => 'input_end',
            'icon' => 'fax',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Iban
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.iban'),
            'code' => 'iban',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'website',
            'order' => 'input_end',
            'icon' => 'hashtag',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # Ibans
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.ibans'),
            'code' => 'ibans',
            'type_id' => '5',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'iban',
            'order' => 'input_end',
            'icon' => 'hashtag',
            'class' => 'col-md-12',
            'required' => '0',
            'enabled' => '1',
        ];

        # Blances
        /*
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.balances'),
            'code' => 'balances',
            'type_id' => '5',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'ibans',
            'order' => 'input_end',
            'icon' => 'hashtag',
            'class' => 'col-md-12',
            'required' => '0',
            'enabled' => '1',
        ];
        */

        # City
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.city'),
            'code' => 'city',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'address',
            'order' => 'input_end',
            'icon' => 'city',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        # District
        $fields[] = [
            'name' => trans('parasut::custom_fields.contacts.district'),
            'code' => 'district',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '8',
            'sort' => 'city',
            'order' => 'input_end',
            'icon' => 'district',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        return $fields;
    }
}
