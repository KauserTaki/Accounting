<?php

namespace Modules\Parasut\Http\Middleware;

use App\Traits\Jobs;

use Closure;

use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Jobs\CustomFields\Create as CreateCustomFields;
use Modules\Parasut\Jobs\Setting\CreateTax;

class Product
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
        $taxes = $this->getTaxes();

        foreach ($taxes as $tax) {
            $this->ajaxDispatch(new CreateTax($tax));
        }

        if ($this->isCustomFields() && !setting('parasut.custom_fields.product', false)) {
            $custom_fields = $this->getCustomFields();

            if ($custom_fields) {
                foreach ($custom_fields as $custom_field) {
                    $this->ajaxDispatch(new CreateCustomFields($custom_field));
                }
            }

            setting()->set('parasut.custom_fields.product', true);
            setting()->save();
        }

        return $next($request);
    }

    protected function getTaxes()
    {
        $taxes = [];

        $taxes[] = [
            'name' => '%18 KDV',
            'rate' => '18',
            'type' => 'normal',
            'enabled' => '1',
        ];

        $taxes[] = [
            'name' => '%8 KDV',
            'rate' => '8',
            'type' => 'normal',
            'enabled' => '1',
        ];

        $taxes[] = [
            'name' => '%1 KDV',
            'rate' => '1',
            'type' => 'normal',
            'enabled' => '1',
        ];

        $taxes[] = [
            'name' => '%0 KDV',
            'rate' => '0',
            'type' => 'normal',
            'enabled' => '1',
        ];

        return $taxes;
    }

    protected function getCustomFields()
    {
        $fields = [];

        # Barcode
        $fields[] = [
            'name' => trans('parasut::custom_fields.products.barcode'),
            'code' => 'barcode',
            'type_id' => '4',
            'rule' => '',
            'value' => '',
            'location_id' => '2',
            'sort' => 'sku',
            'order' => 'input_end',
            'icon' => 'barcode',
            'class' => 'col-md-6',
            'required' => '0',
            'enabled' => '1',
        ];

        return $fields;
    }
}
