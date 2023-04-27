<?php

namespace App\Http\Controllers;

use App\Http\Response\GeneralResponse;
use App\Models\Kios;
use Illuminate\Http\Request;
use DB;
use Validator;
use Illuminate\Support\Facades\Route;

class KiosController extends Controller
{
    public function index()
    {
        $page_title = 'Ayyub Tani';
        $page_description = 'Dashboard Admin Ayyub Tani';
        $breadcrumbs = ['Daftar Kios'];

        return view('kios.index', compact('page_title', 'page_description', 'breadcrumbs'));
    }

    public function list()
    {
        $response = (new KiosController)->getList();
        return $response;
    }

    public function getList()
    {
        $kios = DB::table("kios")
            ->get();

        if ($kios) {
            return (new GeneralResponse)->default_json(true, 'success', $kios, 200);
        } else {
            return (new GeneralResponse)->default_json(false, 'error', null, 401);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kios' => 'required',
            'pemilik' => 'required',
            'kabupaten' => 'required',
            'alamat' => 'required',
            'npwp' => 'required',
            'nik' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['invalid' => $validator->errors()]);
        }

        $data = $request->all();

        $filtered = array_filter(
            $data,
            function ($key) {
                if (!in_array($key, ['_token', 'id'])) {
                    return $key;
                };
            },
            ARRAY_FILTER_USE_KEY
        );

        $request = Request::create("/api/kios/create", 'POST', $filtered);
        $response = Route::dispatch($request);

        return $response;
    }

    public function create(Request $request)
    {
        $data = new Kios();
        $data->nama_kios = $request->nama_kios;
        $data->pemilik = $request->pemilik;
        $data->kabupaten = $request->kabupaten;
        $data->alamat = $request->alamat;
        $data->npwp = $request->npwp;
        $data->nik = $request->nik;
        $data->save();
        // return $data;

        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 403);
        }
    }

    public function show(Request $request, $id)
    {
        $data = Kios::where('id', $id)->first();
        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 404);
        }
    }

    public function update(Request $request)
    {
        $id = request('id');
        $validator = Validator::make($request->all(), [
            'nama_kios' => 'required',
            'pemilik' => 'required',
            'kabupaten' => 'required',
            'alamat' => 'required',
            'npwp' => 'required',
            'nik' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['invalid' => $validator->errors()]);
        }

        $data = $request->all();

        $filtered = array_filter(
            $data,
            function ($key) {
                if (!in_array($key, ['_token', 'id'])) {
                    return $key;
                };
            },
            ARRAY_FILTER_USE_KEY
        );

        $request = Request::create("/api/kios/edit/$id", 'POST', $filtered);
        $response = Route::dispatch($request);

        return $response;
    }

    public function edit(Request $request, $id)
    {
        $data = Kios::where('id', $id)->first();
        $data->nama_kios = $request->nama_kios;
        $data->pemilik = $request->pemilik;
        $data->kabupaten = $request->kabupaten;
        $data->alamat = $request->alamat;
        $data->npwp = $request->npwp;
        $data->nik = $request->nik;
        $data->save();
        // return $data;

        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 403);
        }
    }

    public function delete(Request $request, $id)
    {
        $data = Kios::where('id', $id)->first();
        $data->delete();

        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 404);
        }
    }
}
