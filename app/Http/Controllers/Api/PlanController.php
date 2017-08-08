<?php

namespace Acelle\Http\Controllers\Api;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

/**
 * /api/v1/plans - API controller for managing plans.
 */
class PlanController extends Controller
{
    /**
     * Display all plans.
     *
     * GET /api/v1/plans
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = \Auth::guard('api')->user();
        
        // authorize
        if (!$user->can('read', new \Acelle\Model\Plan())) {
            return \Response::json(array('message' => 'Unauthorized'), 401);
        }
        
        $rows = \Acelle\Model\Plan::getAll()->limit(100)
            ->get();
            
        $plans = $rows->map(function ($plan) {
            return [
                'uid' => $plan->uid,                
                'name' => $plan->name,
                'price' => $plan->price,
                'currency_code' => $plan->currency->code,
                'frequency_amount' => $plan->frequency_amount,
                'frequency_unit' => $plan->frequency_unit,
                'options' => $plan->getOptions(),
                'status' => $plan->status,
                'color' => $plan->color,
                'quota' => $plan->quota,
                'custom_order' => $plan->custom_order,
                'created_at' => $plan->created_at,
                'updated_at' => $plan->updated_at,
            ];
        });

        return \Response::json($plans, 200);
    }
}
