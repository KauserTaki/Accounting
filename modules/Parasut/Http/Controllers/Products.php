<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;

use Modules\Parasut\Jobs\Common\CreateItem;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Traits\Inventory;

use Date;
use Cache;

class Products extends Controller
{
    use Remote, CustomFields, Inventory;

    /**
     * Instantiate a new controller instance.
     */
    public function __construct()
    {
        // Add CRUD permission check
        $this->middleware('permission:create-common-items')->only(['create', 'store', 'duplicate', 'import']);
        $this->middleware('permission:read-common-items')->only(['index', 'show', 'edit', 'export', 'count']);
        $this->middleware('permission:update-common-items')->only(['update', 'enable', 'disable']);
        $this->middleware('permission:delete-common-items ')->only('destroy');
    }

    public function count()
    {
        Cache::forget('parasut_products');

        $total = 0;

        $apps = [
            'custom_fields' => $this->checkCustomFields('products'),
            'inventory' => !$this->isInventory()
        ];

        $type = trans_choice('parasut::general.types.products', 2);

        $items = $this->getProducts();

        if (!isset($items->error) && $items) {
            $total = $items->meta->total_count;
        }

        $html = view('parasut::partial.sync', compact('type', 'total', 'apps'))->render();

        $json = [
            'apps' => $apps,
            'errors' => isset($items->error) ? $items->error_description : false,
            'success' => !isset($items->error) ? true : false,
            'count' => $total,
            'html' => $html
        ];

        return response()->json($json);
    }

    public function sync()
    {
        $page = 1;
        $products = $steps = [];

        do {
            $parameters['include'] = 'category';

            $parameters['page'] = [
                'size' => 15,
                'number' => $page
            ];

            $results = $this->getProducts($parameters);

            if (!empty($results) && !empty($results->data)) {
                foreach ($results->data as $result) {
                    $products[$result->id] = $result;

                    $steps[] = [
                        'text' => trans('parasut::general.sync_text', [
                            'type' => trans_choice('parasut::general.types.products', 1),
                            'value' => $result->attributes->name
                        ]),
                        'url'  => route('parasut.products.store', $result->id)
                    ];
                }
            }

            $page++;
        } while (!empty($results->data));

        // Set parasut products
        session(['parasut_products' => $products]);
        Cache::put('parasut_products', $products, Date::now()->addHour(6));

        $json = [
            'errors' => false,
            'success' => true,
            'count' => count($products),
            'steps' => $steps,
        ];

        return response()->json($json);
    }

    public function store($id)
    {
        $products = Cache::get('parasut_products');

        if (empty($products)) {
            $products = session('parasut_products');
        }

        $response = $this->ajaxDispatch(new CreateItem($products[$id]));

        $json = [
            'errors' => false,
            'success' => true,
        ];

        $last_product = end($products)->id;

        if ($last_product == $id) {
            $json['finished'] = trans('parasut::general.finished', ['type' => trans_choice('parasut::general.types.products', 2)]);

            $timestamp = Date::now()->toRfc3339String();

            setting()->set('parasut.product_last_check', $timestamp);
            setting()->save();
        }

        return response()->json($json);
    }
}
