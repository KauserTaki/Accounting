<?php

namespace Modules\Payflexi\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use App\Models\Banking\Account;
use App\Models\Setting\Category;
use App\Models\Setting\Setting;
use App\Utilities\Modules as Utility;
use App\Http\Requests\Setting\Module as Request;

class Settings extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        $alias = 'payflexi';
        $company_id = company_id();
        $accounts = Account::enabled()->orderBy('name')->pluck('name', 'id');
        $categories = Category::income()->enabled()->orderBy('name')->pluck('name', 'id');

        $setting = Setting::prefix($alias)->get()->transform(function ($s) use ($alias) {
            $s->key = str_replace($alias . '.', '', $s->key);
            return $s;
        })->pluck('value', 'key');

        $setting['webhook_url'] = route('portal.payflexi.invoices.webhook');

        $module = module($alias);

        return view('settings.modules.edit', compact('setting', 'module', 'accounts', 'categories'));
    }
 
}
