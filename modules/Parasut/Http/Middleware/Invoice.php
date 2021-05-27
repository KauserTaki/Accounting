<?php

namespace Modules\Parasut\Http\Middleware;

use App\Traits\Jobs;

use Closure;

use Modules\Parasut\Jobs\Setting\CreateTax;

class Invoice
{
    use Jobs;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!setting('parasut.custom_fields.invoice', false)) {
            $taxes = $this->getTaxes();

            foreach ($taxes as $tax) {
                $this->ajaxDispatch(new CreateTax($tax));
            }

            setting()->set('parasut.custom_fields.invoice', true);
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
}
