<?php

namespace Modules\Parasut\Jobs\Common;

use App\Abstracts\Job;

use App\Models\Common\Item;
use App\Models\Setting\Category;
use App\Models\Setting\Tax;

use App\Jobs\Common\CreateItem as CoreCreateItem;
use App\Jobs\Common\UpdateItem as CoreUpdateItem;

use Modules\Parasut\Traits\Remote;
use Modules\Parasut\Traits\CustomFields;
use Modules\Parasut\Traits\Inventory;

use Modules\Parasut\Jobs\Setting\CreateCategory;

class CreateItem extends Job
{
    use Remote, CustomFields, Inventory;

    protected $item;

    /**
     * Create a new job instance.
     *
     * @param  $item
     */
    public function __construct($item)
    {
        $this->item = $item;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $parasut_item = $this->item->attributes;

        $data = [
            'company_id'     => company_id(),
            'name'           => $parasut_item->name,
            'barcode'        => $parasut_item->barcode,
            'description'    => '',
            'sale_price'     => ($parasut_item->list_price) ? $parasut_item->list_price : 0,
            'purchase_price' => ($parasut_item->buying_price) ? $parasut_item->buying_price : 0,
            'quantity'       => ($parasut_item->unit) ? $parasut_item->unit : 0,
            'category_id'    => $this->getCategoryId(),
            'tax_id'         => $this->getTaxId($parasut_item),
            'enabled'        => !($parasut_item->archived) ? 1 : 0 ,
        ];

        $item_model = Item::where('name', $data['name']);

        if ($this->isInventory()) {
            $data['sku'] = empty($item->code) ? kebab_case($item->name) : $item->code;

            if ($parasut_item->inventory_tracking) {
                $data['track_inventory'] = true;

                $data['opening_stock'] = ($parasut_item->unit) ? $parasut_item->unit : 0;
                $data['opening_stock_value'] = $parasut_item->initial_stock_count;
                $data['reorder_level'] = 0;
            }

            $item_model->where('sku', $data['sku']);
        }

        $item = $item_model->first();

        if (!empty($item->sale_price)) {
            $data['sale_price'] = empty($data['sale_price']) ? $item->sale_price : $data['sale_price'];
        }

        if (!empty($item->purchase_price)) {
            $_data['purchase_price'] = empty($data['purchase_price']) ? $item->purchase_price : $data['purchase_price'];
        }

        $request = request();
        $request->merge($data);

        if (empty($item)) {
            $item = $this->dispatch(new CoreCreateItem($request));
        } else {
            $this->dispatch(new CoreUpdateItem($item, $request));

            if ($this->isCustomFields()) {
                $update = new \Modules\CustomFields\Observers\Common\Item();

                $update->updated($item);
            }
        }

        return $item;
    }

    protected function getCategoryId()
    {
        $category_id = Category::type('item')->pluck('id')->first();

        $relation = $this->item->relationships;

        if (!empty($relation->category->data)) {
            $_category = $this->getCategory($relation->category->data->id);

            $category = $this->dispatch(new CreateCategory($_category->data));

            $category_id = $category->id;
        }

        return $category_id;
    }

    protected function getTaxId($item)
    {
        $rate = (int) $item->vat_rate;

        $tax = Tax::where('rate', $rate)->first();

        if (empty($tax)) {
            return 0;
        }

        return $tax->id;
    }
}
