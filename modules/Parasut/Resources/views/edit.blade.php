@extends('layouts.admin')

@section('title', trans('parasut::general.name'))

@section('content')
    <div class="card">
        {!! Form::open([
            'id' => 'parasut',
            'route' => 'parasut.settings.update',
            '@submit.prevent' => 'onSubmit',
            '@keydown' => 'form.errors.clear($event.target.name)',
            'files' => true,
            'role' => 'form',
            'class' => 'form-loading-button',
            'novalidate' => true,
        ]) !!}

            <div class="card-body">
                <div class="row">
                    {{ Form::textGroup('client_id', trans('parasut::general.form.client_id'), 'fas fa-barcode', ['required' => 'required'], old('client_id', setting('parasut.client_id'))) }}

                    {{ Form::textGroup('client_secret', trans('parasut::general.form.client_secret'), 'fas fa-key', ['required' => 'required'], old('client_secret', setting('parasut.client_secret'))) }}

                    {{ Form::textGroup('username', trans('parasut::general.form.username'), 'fas fa-user', ['required' => 'required'], old('username', setting('parasut.username'))) }}

                    {{ Form::textGroup('password', trans('parasut::general.form.password'), 'fas fa-key', ['required' => 'required'], old('password', setting('parasut.password'))) }}

                    {{ Form::textGroup('c_id', trans('parasut::general.form.company_id'), 'fas fa-building', ['required' => 'required'], old('c_id', setting('parasut.c_id'))) }}

                    {{ Form::textGroup('redirect_uri', trans('parasut::general.form.redirect_uri'), 'fas fa-undo', ['required' => 'required'], old('redirect_uri', setting('parasut.redirect_uri'))) }}
                </div>
            </div>

            <div class="card-footer">
                <div class="row save-buttons">
                    <div class="col-md-12">
                        <a href="{{ route('settings.index') }}" class="btn btn-icon btn-outline-secondary header-button-top">
                            <span class="btn-inner--icon"><i class="fas fa-times"></i></span>
                            <span class="btn-inner--text">{{ trans('general.cancel') }}</span>
                        </a>
                
                        {!! Form::button(
                        '<div v-if="form.loading" class="aka-loader-frame"><div class="aka-loader"></div></div> <span v-if="!form.loading" class="btn-inner--icon"><i class="fas fa-save"></i></span>' . '<span v-if="!form.loading" class="btn-inner--text">' . trans('general.save') . '</span>',
                        [':disabled' => 'form.loading', 'type' => 'submit', 'class' => 'btn btn-icon btn-success button-submit header-button-top', 'data-loading-text' => trans('general.loading')]) !!}

                        <div class="dropup header-drop-top">
                        <button {{ setting('parasut.client_id', false) ? '' : 'disabled="true"' }} type="button" class="btn btn-primary header-button-top" data-toggle="dropdown" aria-expanded="false">
                                <i class="fa fa-chevron-up"></i>&nbsp; {{ trans('parasut::general.form.sync.title') }}
                            </button>
                            <div class="dropdown-menu" role="menu">
                                @permission('create-sales-invoices')
                                    <a href="#" class="dropdown-item" @click="count('contacts')">{{ trans('parasut::general.form.sync.contact') }}</a>
                                @endpermission

                                @permission('create-settings-categories')
                                    <div class="dropdown-divider"></div>
                                    <a href="#" class="dropdown-item" @click="count('categories')">{{ trans('parasut::general.form.sync.category') }}</a>
                                @endpermission

                                @permission('create-common-items')
                                    <div class="dropdown-divider"></div>
                                    <a href="#" class="dropdown-item" @click="count('products')">{{ trans('parasut::general.form.sync.product') }}</a>
                                @endpermission

                                @permission('create-banking-accounts')
                                    <div class="dropdown-divider"></div>
                                    <a href="#" class="dropdown-item" @click="count('accounts')">{{ trans('parasut::general.form.sync.account') }}</a>
                                @endpermission

                                @permission('create-sales-invoices')
                                    <div class="dropdown-divider"></div>
                                    <a href="#" class="dropdown-item" @click="count('invoices')">{{ trans('parasut::general.form.sync.invoice') }}</a>
                                @endpermission

                                @permission('update-sales-invoices')
                                    <div class="dropdown-divider"></div>
                                    <a href="#" class="dropdown-item" @click="count('employees')">{{ trans('parasut::general.form.sync.employee') }}</a>
                                @endpermission

                                @permission('create-purchases-bills')
                                    <div class="dropdown-divider"></div>
                                    <a href="#" class="dropdown-item" @click="count('bills')">{{ trans('parasut::general.form.sync.bill') }}</a>
                                @endpermission
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        {!! Form::close() !!}
    </div>

    <component v-bind:is="sync_html" @sync="sync($event)"></component>
@endsection

@push('scripts_start')
    <script src="{{ asset('modules/Parasut/Resources/assets/js/parasut.min.js?v=' . version('short')) }}"></script>
@endpush

