<?php

namespace Modules\Parasut\Http\Controllers;

use App\Abstracts\Http\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Modules\Parasut\Http\Requests\Setting as Request;

class Settings extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        return view('parasut::edit');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function update(Request $request)
    {
        setting()->set('parasut.client_id', $request['client_id']);
        setting()->set('parasut.client_secret', $request['client_secret']);
        setting()->set('parasut.redirect_uri', $request['redirect_uri']);
        setting()->set('parasut.c_id', $request['c_id']);
        setting()->set('parasut.username', $request['username']);
        setting()->set('parasut.password', $request['password']);
        setting()->save();

        if (config('setting.cache.enabled')) {
            Cache::forget(setting()->getCacheKey());
        }

        $message = trans('messages.success.updated', ['type' => trans('parasut::general.name')]);

        $response = [
            'status' => null,
            'success' => true,
            'error' => false,
            'message' => $message,
            'data' => null,
            'redirect' => route('parasut.settings.edit'),
        ];

        flash($message)->success();

        return response()->json($response);
    }
}
