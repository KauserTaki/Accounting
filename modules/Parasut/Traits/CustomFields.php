<?php

namespace Modules\Parasut\Traits;

use App\Models\Module\Module;

trait CustomFields
{
    public function isCustomFields()
    {
        if (!module('custom-fields')) {
            return false;
        }

        $module = Module::alias('custom-fields')->enabled()->first();

        if ($module) {
            return true;
        }

        return false;
    }

    public function checkCustomFields($method, $type = null)
    {
        if (empty($method) && $type == 'all') {
            $this->getAllCheckCustomFields();
        }

        $result = false;
        $fields = [];
        $locations = [];

        $module = $this->isCustomFields();

        switch ($method) {
            case 'contacts':
                $fields = $this->getContactsByCustomFields();
                $locations = $this->getContactsByLocations();
                break;
            case 'products':
                $fields = $this->getProductsByCustomFields();
                $locations = $this->getProductsByLocations();
                break;
            case 'accounts':
                $fields = $this->getAccountsByCustomFields();
                $locations = $this->getAccountsByLocations();
                break;
            case 'employees':
                $fields = $this->getEmployeesByCustomFields();
                $locations = $this->getEmployeesByLocations();
                break;
        }

        if ($module) {
            foreach ($fields as $field) {
                foreach ($locations as $location) {
                    $custom_field = \Modules\CustomFields\Models\Field::where('code', $field)
                                                                        ->enabled()
                                                                        ->orderBy('name')
                                                                        ->byLocation($location)
                                                                        ->first();

                    if (empty($custom_field)) {
                        $result[$field] = trans('parasut::general.custom_fields.' . $method .  '.' . $field);
                    }
                }
            }
        } elseif (!$module) {
            foreach ($fields as $field) {
                $result[] = trans('parasut::general.custom_fields.' . $method .  '.' . $field);
            }
        }

        return $result;
    }

    protected function getAllCheckCustomFields()
    {
        $result = false;
        $fields = [];
        $locations = [];

        $module = $this->isCustomFields();

        $methods = [
            'contacts',
            'products',
            'accounts',
            'employees',
        ];

        foreach ($methods as $method) {
            switch ($method) {
                case 'contacts':
                    $fields = $this->getContactsByCustomFields();
                    $locations = $this->getContactsByLocations();
                    break;
                case 'products':
                    $fields = $this->getProductsByCustomFields();
                    $locations = $this->getProductsByLocations();
                    break;
                case 'accounts':
                    $fields = $this->getAccountsByCustomFields();
                    $locations = $this->getAccountsByLocations();
                    break;
                case 'employees':
                    $fields = $this->getEmployeesByCustomFields();
                    $locations = $this->getEmployeesByLocations();
                    break;
            }

            if ($module) {
                foreach ($fields as $field) {
                    foreach ($locations as $location) {
                        $custom_field = \Modules\CustomFields\Models\Field::where('code', $field)
                            ->enabled()
                            ->orderBy('name')
                            ->byLocation($location)
                            ->first();

                        if (empty($custom_field)) {
                            $result[$field] = trans('parasut::general.custom_fields.' . $method .  '.' . $field);
                        }
                    }
                }
            } elseif (!$module) {
                foreach ($fields as $field) {
                    $result[] = trans('parasut::general.custom_fields.' . $method .  '.' . $field);
                }
            }
        }

        return $result;
    }

    protected function getContactsByCustomFields()
    {
        return [
            'short_name',
            'contact_type',
            'tax_office',
            'city',
            'district',
            'fax',
            'iban',
            'ibans',
        ];
    }

    protected function getContactsByLocations()
    {
        return [
            '5', // Customers
            '8' // Vendors
        ];
    }

    protected function getProductsByCustomFields()
    {
        return [
            'barcode',
        ];
    }

    protected function getProductsByLocations()
    {
        return [
            '2', // Items
        ];
    }

    protected function getAccountsByCustomFields()
    {
        return [
            'account_type',
            'bank_branch',
            'iban',
        ];
    }

    protected function getAccountsByLocations()
    {
        return [
            '9', // Accounts
        ];
    }

    protected function getEmployeesByCustomFields()
    {
        return [
            'iban',
        ];
    }

    protected function getEmployeesByLocations()
    {
        return [
            '8' // Vendors
        ];
    }
}
