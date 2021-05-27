<?php

namespace Modules\Parasut\Http\Middleware;

use App\Traits\Jobs;

use Closure;

use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Jobs\CustomFields\Create as CreateCustomFields;

class Employee
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
        if ($this->isCustomFields() && !setting('parasut.custom_fields.employee', false)) {
            $custom_fields = $this->getCustomFields();

            if ($custom_fields) {
                foreach ($custom_fields as $custom_field) {
                    $this->ajaxDispatch(new CreateCustomFields($custom_field));
                }
            }

            setting()->set('parasut.custom_fields.employee', true);
            setting()->save();
        }

        return $next($request);
    }

    protected function getCustomFields()
    {
        // Locations {8: Vendors}
        $fields = [];

        // Vendors
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

        return $fields;
    }
}
