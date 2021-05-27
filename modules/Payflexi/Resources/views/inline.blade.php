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
            <form id="payflexi-checkout-form" action="#" method="post">
                <?php $i = 1; ?>
                @foreach ($invoice->items as $item)
                    <input type="hidden" name="item_name_{{ $i }}" value="{{ $item->name }}" />
                    <input type="hidden" name="amount_{{ $i }}" value="{{ $item->price }}" />
                    <input type="hidden" name="quantity_{{ $i }}" value="{{ $item->quantity }}" />
                    <?php $i++; ?>
                @endforeach
                <input type="hidden" name="currency" value="{{ $invoice->currency_code}}" />
                <input type="hidden" name="name" value="{{ $invoice->first_name }} {{ $invoice->last_name }}" />
                <input type="hidden" name="address1" value="{{ $invoice->customer_address }}" />
                <input type="hidden" name="address_override" value="0" />
                <input type="hidden" name="email" value="{{ $invoice->customer_email }}" />
                <input type="hidden" name="invoice" value="{{ $invoice->id . '-' . $invoice->customer_name }}" />
                <input type="hidden" name="meta" value="{{ json_encode(['title' => 'Samsung Galaxy S7 Phone']) }}" />
                <input type="hidden" name="no_note" value="1" />
                <input type="hidden" name="no_shipping" value="1" />
       

                <button type="button" id="payflexi-checkout-btn" class="btn btn-success">Pay with PayFlexi</button>
            </form>
        </div>
    </div>
</div>
