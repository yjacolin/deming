<?php

namespace App\Http\Controllers;

use App\Control;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActionplanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $actions =
            DB::select('
                select
                    c2.measure_id,
                    c2.id,
                    c2.clause,
                    c2.action_plan,
                    c2.score,
                    c2.name,
                    c2.plan_date,
                    c3.id as next_id,
                    c3.plan_date as next_date
                from
                    (
                    select 
                        measure_id,
                        max(id) as id
                    from 
                        controls
                    where
                        realisation_date is not null
                    group by measure_id
                    ) as c1,                
                    controls c2,
                    controls c3
                where
                    c1.id = c2.id and
                    (c2.score=1 or c2.score=2) and
                    (c3.measure_id = c2.measure_id and c3.id > c2.id)
                order by id
                    ;');

        // return
        return view('actions.index')
            ->with('actions', $actions);
    }

    /**
     * Save a Action plan
     *
     * @param  \App\Domain $domain
     *
     * @return \Illuminate\Http\Response
     */
    public function save(Request $request)
    {
        $id = (int) $request->get('id');

        // save control
        $control = Control::find($id);
        $control->action_plan = request('action_plan');
        $control->plan_date = request('plan_date');
        $control->update();

        return redirect('/actions');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Domain $domain
     *
     * @return \Illuminate\Http\Response
     */
    public function show(int $id)
    {
        $action = DB::table('controls as c1')
            ->select(
                'c1.id',
                'c1.measure_id',
                'c1.clause',
                'c1.name',
                'c1.objective',
                'c1.observations',
                'c1.action_plan',
                'c1.plan_date',
                'c1.score',
                'c1.realisation_date',
                'c1.score',
                'c2.plan_date as next_date',
                'c2.id as next_id'
            )
            ->leftJoin('controls as c2', function ($join) {
                $join->on('c1.id', '<>', 'c2.id');
                $join->on('c1.measure_id', '=', 'c2.measure_id');
                $join->whereNull('c2.realisation_date');
            })
            ->where('c1.id', '=', $id)
            ->first();

        // return
        return view('actions.show')
            ->with('action', $action);
    }
}
