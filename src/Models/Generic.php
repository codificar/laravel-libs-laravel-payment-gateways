<?php

namespace Codificar\PaymentGateways\Models;

use Illuminate\Database\Eloquent\Relations\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Carbon\Carbon;
use Eloquent;
use Finance;
use DB;


class Generic extends Eloquent
{

    // protected $table = 'admin';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;


    public static function getAdminList()
    {

        $query = DB::table('admin')
            ->select('username')
            ->get();

        return $query;
    }
}
