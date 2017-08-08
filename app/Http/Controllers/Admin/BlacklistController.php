<?php

namespace Acelle\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Acelle\Http\Controllers\Controller;

class BlacklistController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->user()->admin->getPermission('report_blacklist') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Acelle\Model\Blacklist::getAll();

        return view('admin.blacklist.index', [
            'items' => $items,
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function listing(Request $request)
    {
        if ($request->user()->admin->getPermission('report_blacklist') == 'no') {
            return $this->notAuthorized();
        }

        $items = \Acelle\Model\Blacklist::search($request)->paginate($request->per_page);

        return view('admin.blacklist._list', [
            'items' => $items,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function delete(Request $request)
    {
        $items = \Acelle\Model\Blacklist::whereIn('email', explode(',', $request->emails));

        foreach ($items->get() as $item) {
            // authorize
            if ($request->user()->admin->getPermission('report_blacklist') == 'no') {
                return;
            }
        }

        foreach ($items->get() as $item) {
            $item->delete();
        }

        // Redirect to my lists page
        echo trans('messages.blacklists.deleted');
    }
}
