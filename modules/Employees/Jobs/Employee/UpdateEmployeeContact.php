<?php

namespace Modules\Employees\Jobs\Employee;

use App\Jobs\Common\CreateDashboard;
use App\Jobs\Common\UpdateContact;
use App\Models\Auth\Role;
use App\Models\Auth\User;
use Modules\Employees\Widgets\Profile;

class UpdateEmployeeContact extends UpdateContact
{
    public function createUser()
    {
        // Check if user exist
        if ($user = User::where('email', $this->request['email'])->first()) {
            $message = trans('messages.error.customer', ['name' => $user->name]);

            throw new \Exception($message);
        }

        $data = $this->request->all();
        $data['locale'] = setting('default.locale', 'en-GB');

        $employee_role = Role::firstWhere('name', 'employee');

        $user = User::create($data);
        $user->roles()->attach($employee_role);
        $user->companies()->attach($data['company_id']);

        $this->dispatch(new CreateDashboard([
            'company_id' => $data['company_id'],
            'name' => trans_choice('general.dashboards', 1),
            'custom_widgets' => [
                Profile::class,
            ],
        ]));

        $this->request['user_id'] = $user->id;
    }
}
