<?php
namespace Modules\Employees\Database\Seeds;

use App\Abstracts\Model;
use App\Traits\Permissions as Helper;
use Illuminate\Database\Seeder;

class Permissions extends Seeder
{
    use Helper;

    public $alias = 'employees';

    public function run()
    {
        Model::unguard();

        $this->create();

        Model::reguard();
    }

    private function create()
    {
        $rows = [
            'employee' => [
                'admin-panel' => 'r',
                'common-dashboards' => 'c,r,u,d',
                'common-items' => 'c,r,u,d',
                'common-search' => 'r',
                'common-widgets' => 'c,r,u,d',
                $this->alias . '-widgets-profile' => 'r',
            ],
        ];

        $this->attachPermissionsByRoleNames($rows);
    }
}
