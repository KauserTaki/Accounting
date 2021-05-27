<?php

namespace Modules\Parasut\Jobs\Purchase;

use App\Abstracts\Job;

use App\Models\Common\Contact;
use App\Models\Setting\Currency;

use App\Jobs\Common\CreateContact as CoreCreateContact;
use App\Jobs\Common\UpdateContact as CoreUpdateContact;

use Modules\Parasut\Traits\CustomFields;

class CreateVendor extends Job
{
    use CustomFields;

    protected $contact;

    protected $currency;

    /**
     * Create a new job instance.
     *
     * @param  $contact
     */
    public function __construct($contact)
    {
        $this->contact = $contact;
    }

    /**
     * Execute the job.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->currency = Currency::where('code', setting('default.currency'))->first();

        return $this->createVendor();
    }

    protected function createVendor()
    {
        $contact = $this->contact->attributes;

        $contact_type = 0;

        if ($this->isCustomFields()) {
            $contact_type_value = trans('parasut::custom_fields.contacts.contact_types.' . $contact->contact_type);

            $custom_field = \Modules\CustomFields\Models\Field::where('locations', '8')
                ->where('code', 'contact_type')
                ->first();

            $custom_field_options = $custom_field->fieldTypeOption->pluck('value', 'id');

            if ($custom_field_options) {
                foreach ($custom_field_options as $id => $value) {
                    if ($value == $contact_type_value) {
                        $contact_type = $id;
                    }
                }
            }
        }

        $data = [
            'company_id' => company_id(),
            'type' => 'vendor',
            'name' => $contact->name,
            'email' => $contact->email,
            'short_name' => $contact->short_name,
            //'balances' => $this->getBalances($contact),
            'currency_code' => $this->currency->code,
            'tax_number' => $contact->tax_number,
            'tax_office' => $contact->tax_office,
            'address' => $this->getAddress($contact),
            'enabled' => true,
            'district' => $contact->district,
            'city' => $contact->city,
            'phone' => $contact->phone,
            'reference' => $this->contact->id,
            'fax' => $contact->fax,
            'iban' => $contact->iban,
            'ibans' => $this->getIbans($contact),
        ];

        $request = request();
        $request->merge($data);

        $vendor = Contact::where('reference', $this->contact->id)->first();

        if (empty($vendor)) {
            $vendor = $this->dispatch(new CoreCreateContact($request));
        } else {
            $this->dispatch(new CoreUpdateContact($vendor, $request));

            if ($this->isCustomFields()) {
                $update = new \Modules\CustomFields\Observers\Common\Contact();

                $update->updated($vendor);
            }
        }

        return $vendor;
    }

    protected function getBalances($contact)
    {
        $balance = $contact->balance;

        if (isset($contact->trl_balance)) {
            $balance .= "\n" .'TRY : ' . $contact->trl_balance;
        }

        if (isset($contact->usd_balance)) {
            $balance .= "\n" .'USD : ' . $contact->usd_balance;
        }

        if (isset($contact->eur_balance)) {
            $balance .= "\n" .'EUR : ' . $contact->eur_balance;
        }

        if (isset($contact->gbp_balance)) {
            $balance .= "\n" .'GBP : ' . $contact->gbp_balance;
        }

        return $balance;
    }

    protected function getAddress($contact)
    {
        $address = $contact->address;

        if (isset($contact->district)) {
            $address .= "\n" . $contact->district;
        }

        if (isset($contact->city)) {
            $address .= "\n" . $contact->city;
        }

        return $address;
    }

    protected function getIbans($contact)
    {
        $ibans = $contact->iban;

        if (isset($contact->ibans)) {
            foreach ($contact->ibans as $iban) {
                $ibans .= "\n" . $iban;
            }
        }

        return $ibans;
    }
}
