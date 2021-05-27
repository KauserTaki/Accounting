<?php

namespace Modules\Parasut\Jobs\CustomFields;

use App\Abstracts\Job;

use Modules\CustomFields\Models\Field;
use Modules\CustomFields\Models\FieldLocation;
use Modules\CustomFields\Models\FieldTypeOption;

class Create extends Job
{

    protected $request;

    /**
     * Create a new job instance.
     *
     * @param  $request
     */
    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->request['locations'] = $this->request['location_id'];

        $request = $this->request;

        $request['company_id'] = company_id();

        unset($request['value']);
        unset($request['values']);
        unset($request['location_id']);
        unset($request['sort']);
        unset($request['order']);

        $field = Field::firstOrCreate($request);

        $field_location = FieldLocation::firstOrCreate([
            'company_id' => company_id(),
            'field_id' => $field->id,
            'location_id' => $this->request['location_id'],
            'sort_order' => $this->request['sort'] . '_' . $this->request['order'],
        ]);

        $value = '';

        if (isset($this->request['value'])) {
            $value = $this->request['value'];
        }

        $values = '';

        if (isset($this->request['values'])) {
            $values = $this->request['values'];
        }

        if (!empty($value)) {
            $values[] = $value;
        }

        if ($values) {
            foreach ($values as $value) {
                $field_type_option = FieldTypeOption::firstOrCreate([
                    'company_id' => company_id(),
                    'field_id' => $field->id,
                    'type_id' => $this->request['type_id'],
                    'value' => $value,
                ]);
            }
        } else {
            $field_type_option = FieldTypeOption::firstOrCreate([
                'company_id' => company_id(),
                'field_id' => $field->id,
                'type_id' => $this->request['type_id'],
                'value' => '',
            ]);
        }
    }
}
