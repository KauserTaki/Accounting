<div class="card">
    <div class="card-header">
        <h3>{{ trans('parasut::general.total', ['type' => $type, 'count' => $total]) }}</h3>
    </div>

    <div class="card-body">
        <el-progress :text-inside="true" :stroke-width="24" :percentage="progress.total" :status="progress.status"></el-progress>

        <div id="progress-text" class="mt-3" v-html="progress.text"></div>

        <!-- Custom-fields is check -->
        <div class="card-body" v-if="apps.custom_fields.status || apps.inventory.status || apps.payroll.status">
            <div>
                <p v-if="apps.custom_fields.status">
                    {{ trans('parasut::general.custom_fields.title') }}
                </p>

                <ul v-if="apps.custom_fields.status">
                    <li v-for="field in apps.custom_fields.fields">
                        <p v-text='field'></p>
                    </li>
                </ul>

                <p v-if="apps.custom_fields.status">
                    {!! trans('parasut::custom_fields.coupon.description') !!}
                    <b>{{ trans('parasut::general.coupon.code') }}</b>
                </p>

                <p v-if="apps.inventory.status">
                    {!! trans('parasut::general.coupon.inventory') !!}
                    <b>{{ trans('parasut::general.coupon.code') }}</b>
                </p>

                <p v-if="apps.payroll.status">
                    {!! trans('parasut::general.coupon.payroll') !!}
                    <b>{{ trans('parasut::general.coupon.code') }}</b>
                </p>

                <a v-if="apps.custom_fields.status" href="https://akaunting.com/tr/apps/custom-fields?utm_source=Suggestion&utm_medium=App&utm_campaign=Parasut&redirect={{ base64_encode(env('APP_URL')) }}" target="_blank" class="btn btn-success">
                    <span class="fa fa-shopping-cart"></span> &nbsp; {{ trans('parasut::general.buttons.buy.custom_fields') }}
                </a>

                <a v-if="apps.inventory.status" href="https://akaunting.com/tr/apps/inventory?utm_source=Suggestion&utm_medium=App&utm_campaign=Parasut&redirect={{ base64_encode(env('APP_URL')) }}" target="_blank" class="btn btn-success">
                    <span class="fa fa-shopping-cart"></span> &nbsp; {{ trans('parasut::general.buttons.buy.inventory') }}
                </a>

                <a v-if="apps.payroll.status" href="https://akaunting.com/tr/apps/payroll?utm_source=Suggestion&utm_medium=App&utm_campaign=Parasut&redirect={{ base64_encode(env('APP_URL')) }}" target="_blank" class="btn btn-success">
                    <span class="fa fa-shopping-cart"></span> &nbsp; {{ trans('parasut::general.buttons.buy.payroll') }}
                </a>

                <a href="#" class="btn btn-icon" @click="sync()">
                    <span class="fa fa-play"></span> &nbsp; {{ trans('parasut::general.buttons.continue') }}
                </a>
            </div>
        </div>
    </div>
</div>
