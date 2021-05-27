<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;

use Modules\Parasut\Jobs\Setting\CreateCategory;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;

use Date;
use Cache;

class Categories extends Controller
{
    use Remote, CustomFields;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // Add CRUD permission check
        $this->middleware('permission:create-settings-categories')->only(['create', 'store', 'duplicate', 'import']);
        $this->middleware('permission:read-settings-categories')->only(['index', 'show', 'edit', 'export', 'count']);
        $this->middleware('permission:update-settings-categories')->only(['update', 'enable', 'disable']);
        $this->middleware('permission:delete-settings-categories')->only('destroy');
    }

    public function count()
    {
        Cache::forget('parasut_categories');

        $total = 0;

        $apps = [
            'custom_fields' => false
        ];

        $type = trans_choice('general.categories', 2);

        $categories = $this->getCategories();

        if (!isset($categories->error) && $categories) {
            $total = $categories->meta->total_count;
        }

        $html = view('parasut::partial.sync', compact('type', 'total', 'apps'))->render();

        $json = [
            'apps' => $apps,
            'errors' => isset($categories->error) ? $categories->error_description : false,
            'success' => !isset($categories->error) ? true : false,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $categories = $steps = [];

        do {
            $parameters['page'] = [
                'size' => 15,
                'number' => $page
            ];

            $results = $this->getCategories($parameters);

            if (!empty($results) && !empty($results->data)) {
                foreach ($results->data as $result) {
                    if ($result->attributes->category_type == 'Contact' || $result->attributes->category_type == 'Employee') {
                        continue;
                    }

                    $categories[$result->id] = $result;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', [
                            'type' => trans_choice('general.categories', 1),
                            'value' => $result->attributes->name
                        ]),
                        'url'  => route('parasut.categories.store', $result->id)
                    ];
                }
            }

            $page++;
        } while (!empty($results->data));

        // Set parasut categories
        session(['parasut_categories' => $categories]);
        Cache::put('parasut_categories', $categories, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($categories),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    public function store($id)
    {
        $categories = Cache::get('parasut_categories');

        if (empty($categories)) {
            $categories = session('parasut_categories');
        }

        $category = $categories[$id];

        $response = $this->ajaxDispatch(new CreateCategory($category));

        $json = [
            'errors' => false,
            'success' => true,
        ];

        $last_category = end($categories)->id;

        if ($last_category == $id) {
            $json['finished'] = trans('parasut::general.finished', ['type' => trans_choice('general.categories', 2)]);

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('parasut.category_last_check', $timestamp);
            setting()->save();
        }

        return response()->json($json);
    }
}
