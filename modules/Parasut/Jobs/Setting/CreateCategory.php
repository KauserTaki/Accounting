<?php

namespace Modules\Parasut\Jobs\Setting;

use App\Abstracts\Job;

use App\Models\Setting\Category;

use App\Jobs\Setting\CreateCategory as CoreCreateCategory;
use App\Jobs\Setting\UpdateCategory as CoreUpdateCategory;

class CreateCategory extends Job
{
    protected $category;

    /**
     * Create a new job instance.
     *
     * @param  $category
     */
    public function __construct($category)
    {
        $this->category = $category;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $category = $this->category->attributes;

        $types = [
            'Expenditure' => 'expense',
            'SalesInvoice' => 'income',
            'Product' => 'item',
            'Contact' => 'other',
            'Employee' => 'other',
        ];

        $category_type = $types[$category->category_type];

        $color = '#' . $category->bg_color;

        $data = [
            'company_id' => company_id(),
            'name' => $category->name,
            'type' => $category_type,
            'color' => $color,
            'enabled' => 1,
        ];

        $request = request();
        $request->merge($data);

        $category = Category::where('name', $category->name)
                    ->where('type', $category_type)
                    ->first();

        if (empty($category)) {
            $response = $this->ajaxDispatch(new CoreCreateCategory($request));

            $category = $response['data'];
        } else {
            $this->ajaxDispatch(new CoreUpdateCategory($category, $request));
        }

        return $category;
    }
}
