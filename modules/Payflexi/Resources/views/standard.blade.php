<div>
    <div class="d-none">
        @if (!empty($setting['name']))
            <h2>{{ $setting['name'] }}</h2>
        @endif

        @if ($setting['mode'] == 'test')
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {{ trans('payflexi-payments::general.test_mode') }}</div>
        @endif

        <div class="well well-sm">
            {{ trans('payflexi-payments::general.description') }}
        </div>
    </div>
    <br>

    <div class="buttons">
        <div class="pull-right">
        {!! Form::open([
                'url' => route("portal.payflexi.invoices.initiate.transaction", $invoice->id),
                'id' => 'payflexi-redirect-form',
                'role' => 'form',
                'autocomplete' => "off",
                'novalidate' => 'true'
            ]) !!}
                <button type="submit" id="button-confirm" class="btn btn-success">
                    {{ trans('general.confirm') }}
                </button>
                {!! Form::hidden('reference', $reference) !!}
                {!! Form::hidden('email', $invoice->contact_email) !!}
                {!! Form::hidden('name',  $invoice->contact_name) !!}
                {!! Form::hidden('amount', $invoice->amount_due) !!}
                {!! Form::hidden('currency', $invoice->currency_code) !!}
                {!! Form::hidden('callback_url', $callback_url) !!}
                {!! Form::hidden('meta', json_encode(['title' => $products, 'invoice_id' => $invoice->id, 'type' => $invoice->type, 'address' => $invoice->contact_address, 'phone' => $invoice->contact_phone])) !!}
                
            {!! Form::close() !!}
        </div>
    </div>
</div>
