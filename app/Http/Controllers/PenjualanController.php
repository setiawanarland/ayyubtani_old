<?php

namespace App\Http\Controllers;

use App\Models\Penjualan;
use App\Http\Requests\StorePenjualanRequest;
use App\Http\Requests\UpdatePenjualanRequest;
use App\Http\Response\GeneralResponse;
use App\Models\DetailPenjualan;
use App\Models\DetailPenjualanTemp;
use App\Models\Kios;
use App\Models\LimitPutang;
use App\Models\pajak;
use App\Models\Pembayaran;
use App\Models\Piutang;
use App\Models\Produk;
use DB;
use Illuminate\Http\Request;

class PenjualanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $page_title = 'Ayyub Tani';
        $page_description = 'Dashboard Admin Ayyub Tani';
        $breadcrumbs = ['Penjualan'];

        $kios = DB::table('kios')
            ->select('id', 'nama_kios', 'pemilik')
            ->get();

        $produk = DB::table('produks')
            ->select('id', 'nama_produk', 'kemasan', 'stok', 'harga_jual', 'harga_perdos')
            ->get();

        $pajak = DB::table('pajaks')
            ->select('nama_pajak')
            ->where('active', '1')
            ->first();

        $pembayaran = DB::table('pembayarans')->get();

        $lastPenjualan = Penjualan::where('tahun', session('tahun'))->get();

        $invoice = "AT-" . substr(session('tahun'), -2) . "-" . sprintf("%05s", count($lastPenjualan) + 1);

        return view('penjualan.index', compact('page_title', 'page_description', 'breadcrumbs', 'kios', 'produk', 'pajak', 'pembayaran', 'invoice'));
    }

    public function list()
    {
        $response = (new PenjualanController)->getList();
        return $response;
    }

    public function getList()
    {
        $temp = DetailPenjualanTemp::select('detail_penjualan_temps.*', 'produks.nama_produk', 'produks.kemasan', 'produks.satuan', 'produks.harga_jual', 'harga_perdos')
            ->join('produks', 'detail_penjualan_temps.produk_id', 'produks.id')
            ->get();

        $pajak = pajak::select('nama_pajak', 'satuan_pajak')
            ->where('active', '1')
            ->first();

        foreach ($temp as $key => $value) {
            $value->satuan_pajak = $pajak->satuan_pajak;
        }

        if ($temp) {
            return (new GeneralResponse)->default_json(true, 'success', $temp, 200);
        } else {
            return (new GeneralResponse)->default_json(false, 'error', null, 401);
        }
    }

    public function temp(Request $request)
    {
        if ($request->harga_lama != $request->harga_satuan) {
            $produk = Produk::where('id', $request->produk_id)->first();
            $produk->harga_jual = floatval(preg_replace('/[^\d\.]+/', '', $request->harga_satuan));
            $produk->harga_perdos = floatval(preg_replace('/[^\d\.]+/', '', $request->harga_perdos));
            $produk->save();
        }

        $produk = Produk::where('id', $request->produk_id)->first();
        $qty = $produk->qty_perdos * $request->ket;
        // $hargaSatuan = $produk->harga_jual;
        // $jumlah = $produk->harga_perdos * $request->ket;
        $jumlah = $produk->harga_perdos;
        $jumlahDisc = $request->disc;
        $jumlahAfterDisc = ($jumlah - $jumlahDisc) * $request->ket;
        // return "$jumlah, $jumlahDisc, $jumlahAfterDisc";

        $dataDetail = DetailPenjualanTemp::where('produk_id', $request->produk_id)->first();
        // if ($dataDetail != null) {
        //     return (new GeneralResponse)->default_json(false, "Barang sudah ada!", null, 422);
        // }

        $data = new DetailPenjualanTemp();
        $data->produk_id = $request->produk_id;
        $data->qty = $qty;
        // $data->harga_satuan = $hargaSatuan;
        $data->ket = $request->ket;
        $data->disc = $request->disc;
        $data->jumlah = $jumlahAfterDisc;
        $data->save();
        // return $data;

        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 403);
        }
    }

    public function tempDelete(Request $request, $id)
    {
        $data = DetailPenjualanTemp::where('id', $id)->first();
        $data->delete();

        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 404);
        }
    }

    public function tempReset(Request $request)
    {
        $data = DetailPenjualanTemp::truncate();

        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 404);
        }
    }

    public function preview(Request $request)
    {
        $data = [];

        // get kios information
        $kios = DB::table('kios')
            ->where('id', $request->kios)
            ->first();
        $data['kios'] = $kios;

        // get status pembayaran
        $pembayaran = DB::table('pembayarans')->where('id', $request->pembayaran)->first();

        // get produks information
        $produks = [];
        foreach ($request->produk_id as $key => $value) {
            $produks[] = DB::table('produks')
                ->where('id', $value)
                ->first();
        }

        // add qty, ket, jumlah by produk
        foreach ($produks as $key => $value) {
            $value->qty = $request->qty[$key];
            $value->ket = $request->ket[$key];
            $value->disc = $request->disc[$key];
            $value->jumlah = $request->jumlah[$key];
        }
        $data['produks'] = $produks;

        // set jatuhTempo 
        $jatuhTempo = date('d/m/Y', strtotime('+1 months', strtotime($request->tanggal_jual)));

        // set data
        $data['invoice'] = $request->invoice;
        $data['tanggal_jual'] = $request->tanggal_jual;
        $data['pembayaran'] = $pembayaran->nama_pembayaran;
        $data['jatuh_tempo'] = $jatuhTempo;
        $data['dpp'] = $request->dpp;
        $data['ppn'] = $request->ppn;
        $data['total_disc'] = $request->total_disc;
        $data['grand_total'] = $request->grand_total;

        return $data;
    }

    public function getProduk(Request $request, $id)
    {
        $produk = Produk::where('id', $id)->first();

        return (new GeneralResponse)->default_json(true, "success", $produk, 200);
    }

    public function getStok(Request $request, $id)
    {
        $produk = Produk::select('nama_produk', 'kemasan', 'stok')->where('id', $id)->first();

        if ($produk->stok == 0) {
            return (new GeneralResponse)->default_json(false, "Stok kosong!", $produk, 401);
        }

        if ($produk->stok < $request->qty) {
            return (new GeneralResponse)->default_json(false, "Stok tersisa {$produk->stok}!", $produk, 401);
        }
    }

    public function getLimitPiutang(Request $request, $id)
    {
        $data = [];
        $grand_total = floatval(preg_replace('/[^\d\.]+/', '', $request->grandTotal));

        if ($request->pembayaran == 1) {
            $penjualan = Penjualan::where('kios_id', $id)
                ->where('status', 1)
                ->sum('grand_total');

            $limit = LimitPutang::first();

            $data['total_hutang'] = $penjualan;
            $data['tambahan_hutang'] = $grand_total;

            if (($penjualan + $grand_total) > $limit->limit) {
                return (new GeneralResponse)->default_json(false, "Limit terpenuhi", $data, 403);
            } else {
                return "no";
            }
        }
    }


    public function store(Request $request)
    {
        // return $request->all();
        $dataPenjualan = [];
        $dataPenjualan['bulan'] = date('m', strtotime($request->tanggal_jual));
        $dataPenjualan['tahun'] = date('Y', strtotime($request->tanggal_jual));


        $penjualan = new Penjualan();
        $penjualan->kios_id = $request->kios;
        $penjualan->pembayaran_id = $request->pembayaran;
        $penjualan->invoice = $request->invoice;
        $penjualan->tanggal_jual = date('Y-m-d', strtotime($request->tanggal_jual));
        $penjualan->bulan = $dataPenjualan['bulan'];
        $penjualan->tahun = $dataPenjualan['tahun'];
        $penjualan->dpp = floatval(preg_replace('/[^\d\.]+/', '', $request->dpp));
        $penjualan->ppn = floatval(preg_replace('/[^\d\.]+/', '', $request->ppn));
        $penjualan->grand_total = floatval(preg_replace('/[^\d\.]+/', '', $request->grand_total));
        // $penjualan->dpp = intval(preg_replace("/\D/", "", $request->dpp));
        // $penjualan->ppn = intval(preg_replace("/\D/", "", $request->ppn));
        // $penjualan->grand_total = intval(preg_replace("/\D/", "", $request->grand_total));
        $penjualan->total_disc = floatval(preg_replace('/[^\d\.]+/', '', $request->total_disc));
        $penjualan->status = $request->pembayaran;
        $penjualan->save();

        foreach ($request->produk_id as $key => $value) {
            $detailPenjualan = new DetailPenjualan();
            $detailPenjualan->penjualan_id = $penjualan->id;
            $detailPenjualan->produk_id = $value;
            $detailPenjualan->qty = floatval(preg_replace('/[^\d\.]+/', '', $request->qty[$key]));
            $detailPenjualan->ket = $request->ket[$key];
            $detailPenjualan->disc = floatval(preg_replace('/[^\d\.]+/', '', $request->disc[$key]));
            $detailPenjualan->jumlah = floatval(preg_replace('/[^\d\.]+/', '', $request->jumlah[$key]));
            $detailPenjualan->save();

            $produk = Produk::where('id', $value)->first();
            $stok = $produk->stok;
            $qty = $produk->qty;
            $jumlahPerdos = $produk->jumlah_perdos;
            $stokkeluar = intval(preg_replace("/\D/", "", $request->ket[$key]));
            $qtykeluar = floatval(preg_replace('/[^\d\.]+/', '', $request->qty[$key]));
            $produk->stok = $stok - $stokkeluar;
            $produk->qty = $qty - $qtykeluar;
            $produk->save();
        }

        $dataPiutang = [];
        $dataPiutang['bulan'] = date('m', strtotime($request->tanggal_jual));
        $dataPiutang['tahun'] = date('Y', strtotime($request->tanggal_jual));

        if ($request->pembayaran == 1) {
            $piutang = new Piutang();
            $piutang->penjualan_id = $penjualan->id;
            $piutang->bulan = $dataPiutang['bulan'];
            $piutang->tahun = $dataPiutang['tahun'];
            $piutang->ket = '';
            $piutang->debet = floatval(preg_replace('/[^\d\.]+/', '', $request->grand_total));
            $piutang->kredit = 0;
            $piutang->sisa = floatval(preg_replace('/[^\d\.]+/', '', $request->grand_total)) - $piutang->kredit;
            $piutang->save();
        }

        $temp = DetailPenjualanTemp::truncate();

        if ($temp) {
            return (new GeneralResponse)->default_json(true, "Success", null, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", null, 404);
        }
    }

    public function daftar()
    {
        $page_title = 'Ayyub Tani';
        $page_description = 'Dashboard Admin Ayyub Tani';
        $breadcrumbs = ['Daftar Penjualan'];

        return view('penjualan.daftar', compact('page_title', 'page_description', 'breadcrumbs',));
    }

    public function listPenjualan()
    {
        $response = (new PenjualanController)->getListPenjualan();
        return $response;
    }

    public function getListPenjualan()
    {
        $data = Penjualan::select('penjualans.*', 'kios.nama_kios',)
            ->join('kios', 'penjualans.kios_id', 'kios.id')
            ->where('penjualans.tahun', session('tahun'))
            // ->orderBy('penjualans.tahun', 'ASC')
            ->orderBy('penjualans.id', 'DESC')
            ->get();


        if ($data) {
            return (new GeneralResponse)->default_json(true, 'success', $data, 200);
        } else {
            return (new GeneralResponse)->default_json(false, 'error', null, 401);
        }
    }

    public function show(Request $request, $id)
    {
        $data = [];

        $penjualan = Penjualan::where('id', $id)->first();
        $kios = Kios::select('nama_kios', 'alamat', 'kabupaten')->where('id', $penjualan->kios_id)->first();
        $detailPenjualan = DetailPenjualan::where('penjualan_id', $penjualan->id)->get();

        foreach ($detailPenjualan as $key => $value) {
            $produk = Produk::select('nama_produk', 'kemasan')->where('id', $value->produk_id)->first();
            $value->nama_produk = $produk->nama_produk;
            $value->kemasan_produk = $produk->kemasan;
        }

        $data['penjualan'] = $penjualan;
        $data['kios'] = $kios;
        $data['detailPenjualan'] = $detailPenjualan;

        return $data;
    }

    public function edit(Request $request, $id)
    {
        $page_title = 'Ayyub Tani';
        $page_description = 'Dashboard Admin Ayyub Tani';
        $breadcrumbs = ['Penjualan'];

        $penjualan = Penjualan::with('detailPenjualan', 'kios')
            ->where('penjualans.id', $id)
            ->first();

        $kios = DB::table('kios')
            ->select('id', 'nama_kios',)
            ->get();

        $produk = DB::table('produks')
            ->select('id', 'nama_produk', 'kemasan', 'stok')
            ->get();

        $pajak = DB::table('pajaks')
            ->select('nama_pajak')
            ->where('active', '1')
            ->first();

        // return $penjualan;
        return view('penjualan.edit', compact('page_title', 'page_description', 'breadcrumbs', 'kios', 'produk', 'pajak', 'penjualan'));
    }

    public function print(Request $request)
    {
        $penjualan = Penjualan::where('id', $request->id)
            ->get();

        foreach ($penjualan as $key => $value) {
            $jatuhTempo = date('d/m/Y', strtotime('+1 months', strtotime($value->tanggal_jual)));
            $statusPembayaran = Pembayaran::where('id', $value->status)->first();
            $kios = Kios::where('id', $value->kios_id)->first();
            $detailPenjualan = DetailPenjualan::where('penjualan_id', $value->id)->get();

            foreach ($detailPenjualan as $index => $val) {
                $produk = Produk::where('id', $val->produk_id)->first();
                $val->nama_produk = $produk->nama_produk;
                $val->kemasan = $produk->kemasan;
                $val->harga_jual = $produk->harga_jual;
                $val->harga_perdos = $produk->harga_perdos;
                $val->satuan = $produk->satuan;
            }
            $value->jatuh_tempo = $jatuhTempo;
            $value->pembayaran = $statusPembayaran->nama_pembayaran;
            $value->kios = $kios;
            $value->detail_penjualan = $detailPenjualan;
        }

        return $penjualan;
    }

    public function listEditPenjualan(Request $request)
    {
        $response = (new PenjualanController)->getListEditPenjualan($request->id);
        return $response;
    }

    public function getListEditPenjualan($id)
    {
        $data = DetailPenjualan::select('detail_penjualans.*', 'produks.nama_produk', 'produks.kemasan', 'produks.satuan', 'produks.harga_jual', 'harga_perdos', 'produks.jumlah_perdos')
            ->join('produks', 'detail_penjualans.produk_id', 'produks.id')
            ->where('detail_penjualans.penjualan_id', $id)
            ->get();

        $pajak = pajak::select('nama_pajak', 'satuan_pajak')
            ->where('active', '1')
            ->first();

        foreach ($data as $key => $value) {
            $value->satuan_pajak = $pajak->satuan_pajak;
        }

        if ($data) {
            return (new GeneralResponse)->default_json(true, 'success', $data, 200);
        } else {
            return (new GeneralResponse)->default_json(false, 'error', null, 401);
        }
    }

    public function addEdit(Request $request)
    {
        $produk = Produk::where('id', $request->produk_id)->first();
        $qty = $produk->jumlah_perdos * $request->ket;
        $hargaSatuan = $produk->harga_jual / $produk->jumlah_perdos;
        $jumlah = $hargaSatuan * $qty;
        $jumlahDisc = $jumlah * $request->disc / 100;
        $jumlahAfterDisc = $jumlah - $jumlahDisc;

        $dataDetail = DetailPenjualan::where('produk_id', $request->produk_id)
            ->where('penjualan_id', $request->penjualan_id)->first();
        if ($dataDetail != null) {
            return (new GeneralResponse)->default_json(false, "Produk sudah ada!", null, 422);
        }

        $data = new DetailPenjualan();
        $data->penjualan_id = $request->penjualan_id;
        $data->produk_id = $request->produk_id;
        $data->qty = $qty;
        // $data->harga_satuan = $hargaSatuan;
        $data->ket = "$request->ket Dos";
        $data->disc = $request->disc;
        $data->jumlah = $jumlahAfterDisc;
        $data->save();

        $penjualan = Penjualan::where('id', $request->penjualan_id)->first();
        $penjualan->grand_total = $penjualan->grand_total + $jumlahAfterDisc;
        $penjualan->save();

        if ($penjualan) {
            return (new GeneralResponse)->default_json(true, "Success", $penjualan, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $penjualan, 403);
        }
    }

    public function update(Request $request, Penjualan $penjualan)
    {
        $penjualan = Penjualan::where('id', $request->id)->first();
        $penjualan->tanggal_jual = date('Y-m-d', strtotime($request->tanggal_jual));
        $penjualan->grand_total = intval(preg_replace("/\D/", "", $request->grand_total));
        $penjualan->save();

        foreach ($request->produk_id as $key => $value) {
            $detailPenjualan = DetailPenjualan::where('penjualan_id', $request->id)
                ->where('produk_id', $value)
                ->first();

            $detailPenjualan->qty = $request->qty[$key];
            $detailPenjualan->ket = $request->ket[$key];
            $detailPenjualan->disc = floatval(preg_replace('/[^\d\.]+/', '', $request->disc[$key]));
            $detailPenjualan->jumlah = intval(preg_replace("/\D/", "", $request->jumlah[$key]));
            $detailPenjualan->save();

            $produk = Produk::where('id', $value)->first();
            $marginStok = intval(preg_replace('/([^\-0-9\.,])/i', "", $request->margin_ket[$key]));
            $produk->stok = $produk->stok - $marginStok;
            $produk->save();
        }

        $piutang = Piutang::where('penjualan_id', $request->id)->first();
        $piutang->debet = $piutang->debet + intval(preg_replace('/([^\-0-9\.,])/i', "", $request->margin_grandtotal));
        $piutang->sisa = $piutang->sisa + intval(preg_replace('/([^\-0-9\.,])/i', "", $request->margin_grandtotal));
        $piutang->save();

        if ($piutang) {
            return (new GeneralResponse)->default_json(true, "Success", null, 201);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", null, 404);
        }
    }

    public function destroy(Penjualan $penjualan, $id)
    {
        $penjualan = Penjualan::where('id', $id)->first();
        $detailPenjualan = DetailPenjualan::where('penjualan_id', $penjualan->id)->get();
        $piutang = Piutang::where('penjualan_id', $penjualan->id)->first();

        foreach ($detailPenjualan as $key => $value) {
            $produk = Produk::where('id', $value->produk_id)->first();

            $stokJual = intval(preg_replace("/\D/", "", $value->ket));
            $stok = $produk->stok;
            $qtyJual = floatval(preg_replace('/[^\d\.]+/', '', $value->qty));
            $qty = $produk->qty;
            $stokBaru = $stokJual + $stok;
            $qtyBaru = $qtyJual + $qty;

            $produk->stok = $stokBaru;
            $produk->qty = $qtyBaru;

            $produk->save();

            if ($produk) {
                $detail = DetailPenjualan::where('id', $value->id)->first();
                $detail->delete();
            } else {
                return (new GeneralResponse)->default_json(false, "Error", $produk, 401);
            }
        }

        if ($piutang) {
            $piutang->delete();
        }
        $data = $penjualan->delete();

        if ($data) {
            return (new GeneralResponse)->default_json(true, "Success", $data, 200);
        } else {
            return (new GeneralResponse)->default_json(false, "Error", $data, 401);
        }
    }
}
