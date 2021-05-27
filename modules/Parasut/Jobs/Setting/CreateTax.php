<?php

namespace Modules\Parasut\Jobs\Setting;

use App\Abstracts\Job;

use App\Models\Setting\Tax;

use App\Jobs\Setting\CreateTax as CoreCreateTax;
use App\Jobs\Setting\UpdateTax as CoreUpdateTax;

class CreateTax extends Job
{
    protected $tax;

    /**
     * Create a new job instance.
     *
     * @param  $tax
     */
    public function __construct($tax)
    {
        $this->tax = $tax;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $data = [
            'company_id' => company_id(),
            'name'       => $this->tax['name'],
            'rate'       => $this->tax['rate'],
            'type'       => $this->tax['type'],
            'enabled'    => $this->tax['enabled'],
        ];

        $request = request();
        $request->merge($data);

        $tax = Tax::where('name', $data['name'])->where('rate', $data['rate'])->first();

        if (empty($tax)) {
            $tax = $this->dispatch(new CoreCreateTax($request));
        } else {
            $this->dispatch(new CoreUpdateTax($tax, $request));
        }

        return $tax;
    }
}
