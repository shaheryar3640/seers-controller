<?php

namespace App\Http\Controllers\Business;

use App\DomainScript;
use App\ScriptCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ScriptManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!request()->ajax()) {
            return response([ 'message' => 'Bad request'], 400);
        }

        $domain_id = request()->query('dom_id', 0);

        if ($domain_id <= 0) {
            return response([ 'message' => 'Domain id cannot be zero or negative'], 400);
        }

        $scripts = DomainScript::getScripts($domain_id);
        $script_categories = ScriptCategory::select('id', 'name', 'slug')->get();

        return response([
            'script_categories' => $script_categories,
            'scripts' => $scripts
        ], 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!$request->ajax()) {
            return response([ 'message' => 'Bad request'], 400);
        }
        $query = $request->query();

        if (((int) $query['domain_id']) <= 0) {
            return response(['message' => 'Domain Id must be greater than 0'], 400);
        }

        if (((int) $query['script_category_id'] <= 0)) {
            return response(['message' => 'Category Id must be greater than 0'], 400);
        }

        $script = new DomainScript;
        $script->title = $query['title'] ?? null;
        $script->src = $query['src'] ?? null;
        $script->dataset = $query['dataset'] ?? null;
        $script->type = $query['type'] ?? null;
        $script->slug = $this->removeProtocol($query['src']);
        $script->_id = $query['_id'] ?? null;
        $script->body = $query['body'] ?? null;
        $script->purpose = $query['purpose'] ?? null;
        $script->policy = $query['policy'] ?? null;
        $script->policy_url = $query['policy_url'] ?? null;
        $script->enabled = (boolean) $query['enabled'];
        $script->domain_id = (int) $query['domain_id'];
        $script->deprecated_at = now();
        $script->script_category_id = (int) $query['script_category_id'];

        $script->save();

        return response(['message' => 'Script added successfully!', 'script' => $script], 200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        if (!$request->ajax()) {
            return response(['message' => 'Bad request'], 400);
        }
        $query = $request->all();

        if (((int) $query['script_category_id'] <= 0)) {
            return response(['message' => 'Category Id must be greater than 0'], 400);
        }

        $script = DomainScript::find((int) $query['id']);
        $script->title = $query['title'] ?? null;
        $script->src = $query['src'] ?? null;
        $script->slug = $this->removeProtocol($query['src']);
        $script->dataset = $query['dataset'] ?? null;
        $script->type = $query['type'] ?? null;
        $script->_id = $query['_id'] ?? null;
        $script->body = $query['body'] ?? null;
        $script->purpose = $query['purpose'] ?? null;
        $script->policy = $query['policy'] ?? null;
        $script->policy_url = $query['policy_url'] ?? null;
        $script->script_category_id = (int) $query['script_category_id'];

        $script->save();

        return response(['message' => 'Script updated successfully!'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        if (!request()->ajax()) {
            return response(['message' => 'Bad Request'], 400);
        }

        $id = (int) request()->query('id');

        if ($id <= 0) {
            return response(['message' => 'Script ID must be greater than 0'], 400);
        }

        DomainScript::destroy($id);

        return response(['message' => 'Script removed successfully'], 200);
    }

    public function toggleScript(Request $request)
    {
        if (!$request->ajax()) {
            return response(['message' => 'Bad Request'], 400);
        }

        $queryParams = $request->query();
        $id = (int) $queryParams['id'];

        if ($id <= 0) {
            return response(['message' => 'Script ID must be greater than 0'], 400);
        }

        $script = DomainScript::find($id);
        $script->enabled = $queryParams['enabled'] === 'true' ? 1 : 0;
        $script->save();

        return response(['message' => 'Script ' . ($queryParams['enabled'] === 'true' ? 'enabled' : 'disabled') .' successfully!'], 200);
    }

    public function updateCategory(Request $request) {
        if (!$request->ajax()) {
            return response(['messsage' => 'Bad Request'], 400);
        }

        $queryParams = $request->query();
        $id = (int) $queryParams['id'];



        $script = DomainScript::find($id);
        if ($script) {
            $script->script_category_id = (int) $queryParams['category_id'];
            $script->save();
            return response(['message' => 'Category updated successfully'], 200);
        }
    }

    private function removeProtocol($URL) {
        $remove = ["http://", "https://", "www.", "WWW."];
        $final_url =  str_replace($remove, "", $URL);
        if (strpos($final_url, '/') !== false) {
            $final_url = explode('/', $final_url);
            return $final_url[0];
        } else {
            return $final_url;
        }
    }
}
