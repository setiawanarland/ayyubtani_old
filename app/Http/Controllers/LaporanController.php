<?php

namespace App\Http\Controllers;

use App\Http\Response\GeneralResponse;
use DB;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

setlocale(LC_ALL, 'IND');

class LaporanController extends Controller
{
    public function stok()
    {
        $page_title = 'Ayyub Tani';
        $page_description = 'Dashboard Admin Ayyub Tani';
        $breadcrumbs = ['Laporan Stok'];

        return view('laporan.stok', compact('page_title', 'page_description', 'breadcrumbs'));
    }

    public function list(Request $request)
    {
        $data = [];
        $bulan = request('bulan');
        $stokBeli = 0;
        $stokJual = 0;

        $produks = DB::table('produks')->orderBy('nama_produk')->get();

        foreach ($produks as $key => $value) {
            $stokBeli = 0;
            $stokJual = 0;
            $stokBeliLalu = 0;
            $stokJualLalu = 0;

            $pembelian = DB::table('detail_pembelians')
                ->join('pembelians', 'detail_pembelians.pembelian_id', 'pembelians.id')
                ->where('produk_id', $value->id)
                ->where('tahun', session('tahun'))
                // ->whereRaw("$whereBeli")
                ->when($bulan, function ($query, $bulan) {
                    if ($bulan !== 'all') {
                        return $query->whereMonth('tanggal_beli', "$bulan");
                    }
                })
                ->get();

            if (count($pembelian) > 0) {
                foreach ($pembelian as $index => $val) {
                    $stokBeli += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $penjualan = DB::table('detail_penjualans')
                ->join('penjualans', 'detail_penjualans.penjualan_id', 'penjualans.id')
                ->where('produk_id', $value->id)
                ->where('tahun', session('tahun'))
                ->when($bulan, function ($query, $bulan) {
                    if ($bulan !== 'all') {
                        return $query->whereMonth('tanggal_jual', "$bulan");
                    }
                })
                ->get();

            if (count($penjualan) > 0) {
                foreach ($penjualan as $index => $val) {
                    $stokJual += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $value->pembelian = $stokBeli;
            $value->penjualan = $stokJual;

            $firstBeli = DB::table('pembelians')
                ->min('tanggal_beli');
            $firstJual = DB::table('penjualans')
                ->min('tanggal_jual');
            $currDate = ($bulan == 'all') ? date(session('tahun') + 1 . "-m-d", strtotime('01/01')) : date(session('tahun') . "-$bulan-01");
            $lastDate = date($currDate, strtotime('last day of -1 month'));
            // return $currDate;
            // return $lastDate;

            $pembelianLalu = DB::table('detail_pembelians')
                ->join('pembelians', 'detail_pembelians.pembelian_id', 'pembelians.id')
                ->where('produk_id', $value->id)
                ->whereBetween('tanggal_beli', [$firstBeli, $lastDate])
                ->get();

            if (count($pembelianLalu) > 0) {
                foreach ($pembelianLalu as $index => $val) {
                    $stokBeliLalu += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $penjualanLalu = DB::table('detail_penjualans')
                ->join('penjualans', 'detail_penjualans.penjualan_id', 'penjualans.id')
                ->where('produk_id', $value->id)
                ->whereBetween('tanggal_jual', [$firstJual, $lastDate])
                ->get();


            if (count($penjualanLalu) > 0) {
                foreach ($penjualanLalu as $index => $val) {
                    $stokJualLalu += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $value->stokAwal = $stokBeliLalu - $stokJualLalu;
            $value->stok_bulanan = $value->stokAwal + ($stokBeli - $stokJual);
            // $value->stok_bulanan = $value->stok;
            $value->harga = $value->stok_bulanan * $value->harga_perdos;
            $value->dpp = $value->harga / 1.1;
            $value->ppn = $value->harga - $value->dpp;

            $data[] = $value;
            // if ($stokBeli !== 0 || $stokJual !== 0) {
            //     $data[] = $value;
            // }
        }

        return (new GeneralResponse)->default_json(true, 'success', $data, 200);
    }

    public function rekap(Request $request)
    {
        $bulan = request('bulan');
        $jenis = request('jenis');
        $data = [];
        $temp = [];
        $stokBeli = 0;
        $stokJual = 0;
        $stokBeliLalu = 0;
        $stokJualLalu = 0;

        $produks = DB::table('produks')->orderBy('nama_produk')->get();

        foreach ($produks as $key => $value) {
            $stokBeli = 0;
            $stokJual = 0;
            $stokBeliLalu = 0;
            $stokJualLalu = 0;

            $pembelian = DB::table('detail_pembelians')
                ->join('pembelians', 'detail_pembelians.pembelian_id', 'pembelians.id')
                ->where('produk_id', $value->id)
                ->where('tahun', session('tahun'))
                // ->whereRaw("$whereBeli")
                ->when($bulan, function ($query, $bulan) {
                    if ($bulan !== 'all') {
                        return $query->whereMonth('tanggal_beli', "$bulan");
                    }
                })
                ->get();

            if (count($pembelian) > 0) {
                foreach ($pembelian as $index => $val) {
                    $stokBeli += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $penjualan = DB::table('detail_penjualans')
                ->join('penjualans', 'detail_penjualans.penjualan_id', 'penjualans.id')
                ->where('produk_id', $value->id)
                ->where('tahun', session('tahun'))
                ->when($bulan, function ($query, $bulan) {
                    if ($bulan !== 'all') {
                        return $query->whereMonth('tanggal_jual', "$bulan");
                    }
                })
                ->get();

            if (count($penjualan) > 0) {
                foreach ($penjualan as $index => $val) {
                    $stokJual += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $value->pembelian = $stokBeli;
            $value->penjualan = $stokJual;


            $firstBeli = DB::table('pembelians')
                ->min('tanggal_beli');
            $firstJual = DB::table('penjualans')
                ->min('tanggal_jual');

            $currDate = date(session('tahun') . "-$bulan-01");
            $lastDate = date($currDate, strtotime('last day of -1 month'));

            $pembelianLalu = DB::table('detail_pembelians')
                ->join('pembelians', 'detail_pembelians.pembelian_id', 'pembelians.id')
                ->where('produk_id', $value->id)
                ->whereBetween('tanggal_beli', [$firstBeli, $lastDate])
                ->get();

            if (count($pembelianLalu) > 0) {
                foreach ($pembelianLalu as $index => $val) {
                    $stokBeliLalu += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $penjualanLalu = DB::table('detail_penjualans')
                ->join('penjualans', 'detail_penjualans.penjualan_id', 'penjualans.id')
                ->where('produk_id', $value->id)
                ->whereBetween('tanggal_jual', [$firstJual, $lastDate])
                ->get();


            if (count($penjualanLalu) > 0) {
                foreach ($penjualanLalu as $index => $val) {
                    $stokJualLalu += intval(preg_replace("/\D/", "", $val->ket));
                }
            }

            $value->stokAwal = $stokBeliLalu - $stokJualLalu;
            $value->stok_bulanan = $value->stokAwal + ($stokBeli - $stokJual);

            // $value->stok_bulanan = $stokBeli - $stokJual;
            // $value->stok_bulanan = $value->stok;
            $value->harga = $value->stok_bulanan * $value->harga_perdos;
            $value->dpp = $value->harga / 1.1;
            $value->ppn = $value->harga - $value->dpp;

            $temp[] = $value;
            // if ($stokBeli !== 0 || $stokJual !== 0) {
            //     $temp[] = $value;
            // }
        }

        $data['bulan'] = $bulan;
        $data['produks'] = $temp;
        // return $data;

        return $this->laporanRekapStok($data, $bulan, $jenis);
    }

    public function laporanRekapStok($data, $bulan, $jenis)
    {
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()->setCreator('CV AYYUB TANI')
            ->setLastModifiedBy('CV AYYUB TANI')
            ->setTitle('Laporan Rekap Stok Bulanan')
            ->setSubject('Laporan Rekap Stok Bulanan')
            ->setDescription('Laporan Rekap Stok Bulanan')
            ->setKeywords('pdf php')
            ->setCategory('Laporan Rekap Stok Bulanan');

        $sheet = $spreadsheet->getActiveSheet();
        // $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO);

        $sheet->getRowDimension(1)->setRowHeight(17);
        $sheet->getRowDimension(2)->setRowHeight(17);
        $sheet->getRowDimension(3)->setRowHeight(7);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(false);

        // //Margin PDF
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.3);

        $tahun = ""  . session('tahun') . "-" . $bulan . "";
        $periode = ($bulan != 'all') ? strtoupper(strftime('%B %Y', mktime(0, 0, 0, $bulan + 1, 0, (int)session('tahun')))) : (int)session('tahun');

        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI STOK BARANG')->mergeCells('A1:J1');
        $sheet->setCellValue('A2', 'CV. AYYUB TANI')->mergeCells('A2:I2');
        $sheet->setCellValue('A3', "PERIODE $periode")->mergeCells('A3:I3');

        $sheet->setCellValue('A5', 'No')->mergeCells('A5:A5');
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->setCellValue('B5', 'Nama Produk');
        $sheet->getColumnDimension('B')->setWidth(30);
        $sheet->setCellValue('C5', 'Kemasan');
        $sheet->getColumnDimension('C')->setWidth(25);
        $sheet->setCellValue('D5', 'Stok Awal');
        $sheet->getColumnDimension('D')->setWidth(8);
        $sheet->setCellValue('E5', 'Pembelian');
        $sheet->getColumnDimension('E')->setWidth(13);
        $sheet->setCellValue('F5', 'Penjualan');
        $sheet->getColumnDimension('F')->setWidth(13);
        $sheet->setCellValue('G5', 'Stok Akhir');
        $sheet->getColumnDimension('F')->setWidth(8);
        $sheet->setCellValue('H5', 'Harga');
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->setCellValue('I5', 'DPP');
        $sheet->getColumnDimension('I')->setWidth(16);
        $sheet->setCellValue('J5', 'PPN');
        $sheet->getColumnDimension('J')->setWidth(16);

        $cell = 5;

        $sheet->getStyle('A1:A3')->getFont()->setSize(12);
        $sheet->getStyle('A:J')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A5:J5')->getFont()->setBold(true);
        $sheet->getStyle('A5:J5')->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('A5:A' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('B5:B' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center');
        $sheet->getStyle('B5:B' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center');
        $sheet->getStyle('C5:C' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('D5:D' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('E5:E' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('F5:F' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('G5:G' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('H5:H5')->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('H6:H' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('right');
        $sheet->getStyle('H6:H' . (count($data['produks']) + $cell))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('I5:I5')->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('I6:I' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('right');
        $sheet->getStyle('I6:I' . (count($data['produks']) + $cell))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('I5:J5')->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('I6:J' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('right');
        $sheet->getStyle('I6:J' . (count($data['produks']) + $cell))->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle('A1:A3')->getAlignment()->setVertical('center')->setHorizontal('center');


        foreach ($data['produks'] as $index => $value) {
            // return $value;

            $cell++;

            $sheet->setCellValue('A' . $cell, $index + 1);
            $sheet->setCellValue('B' . $cell, strtoupper($value->nama_produk));
            $sheet->setCellValue('C' . $cell, strtoupper($value->kemasan));
            $sheet->setCellValue('D' . $cell, $value->stokAwal);
            $sheet->setCellValue('E' . $cell, $value->pembelian);
            $sheet->setCellValue('F' . $cell, $value->penjualan);
            $sheet->setCellValue('G' . $cell, $value->stok_bulanan);
            // $sheet->setCellValue('G' . $cell, number_format($value->harga));
            $sheet->setCellValue('H' . $cell, $value->harga);
            $sheet->setCellValue('I' . $cell, $value->dpp);
            $sheet->setCellValue('J' . $cell, $value->ppn);
        }

        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A5:J' . $cell)->applyFromArray($border);

        if ($jenis == 'excel') {
            // Untuk download 
            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Rekapitulasi Laporan Stok CV. AYYUB TANI ' . $periode . '.xlsx"');
        } else {
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddHeader('&C&H' . url()->current());
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddFooter('&L&B &RPage &P of &N');
            $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        }

        $writer->save('php://output');
    }

    public function produk()
    {
        $page_title = 'Ayyub Tani';
        $page_description = 'Dashboard Admin Ayyub Tani';
        $breadcrumbs = ['Laporan Produk'];

        return view('laporan.produk', compact('page_title', 'page_description', 'breadcrumbs'));
    }

    public function listProduk(Request $request)
    {
        $data = [];
        $bulan = request('bulan');
        // return $bulan;
        $stokBeli = 0;
        $stokJual = 0;

        $produks = DB::table('produks')->orderBy('nama_produk')->get();

        foreach ($produks as $key => $value) {
            $stokBeli = 0;
            $stokJual = 0;

            $detailPenjualan = DB::table('detail_penjualans')
                ->join('penjualans', 'detail_penjualans.penjualan_id', 'penjualans.id')
                ->where('produk_id', $value->id)
                ->where('tahun', session('tahun'))
                // ->whereRaw("$whereBeli")
                ->when($bulan, function ($query, $bulan) {
                    if ($bulan !== 'all') {
                        return $query->whereMonth('tanggal_jual', "$bulan");
                    }
                })
                ->get();

            if (count($detailPenjualan) > 0) {
                foreach ($detailPenjualan as $index => $val) {
                    $stokJual += intval(preg_replace("/\D/", "", $val->ket));
                    $penjualan = DB::table('penjualans')->where('id', $val->penjualan_id)->first();
                    $val->invoice = $penjualan->invoice;
                }
            }

            $value->penjualan = $stokJual;
            $value->detail_penjualan = $detailPenjualan;

            if (count($detailPenjualan) > 0) {
                $data[] = $value;
            }
        }

        return (new GeneralResponse)->default_json(true, 'success', $data, 200);
    }

    public function rekapProduk(Request $request)
    {
        $bulan = request('bulan');
        $jenis = request('jenis');
        $data = [];
        $temp = [];
        $stokBeli = 0;
        $stokJual = 0;

        $produks = DB::table('produks')->orderBy('nama_produk')->get();

        foreach ($produks as $key => $value) {
            $stokBeli = 0;
            $stokJual = 0;

            $detailPenjualan = DB::table('detail_penjualans')
                ->join('penjualans', 'detail_penjualans.penjualan_id', 'penjualans.id')
                ->where('produk_id', $value->id)
                ->where('tahun', session('tahun'))
                // ->whereRaw("$whereBeli")
                ->when($bulan, function ($query, $bulan) {
                    if ($bulan !== 'all') {
                        return $query->whereMonth('tanggal_jual', "$bulan");
                    }
                })
                ->get();

            if (count($detailPenjualan) > 0) {
                foreach ($detailPenjualan as $index => $val) {
                    $stokJual += intval(preg_replace("/\D/", "", $val->ket));
                    $penjualan = DB::table('penjualans')->where('id', $val->penjualan_id)->first();
                    $val->invoice = $penjualan->invoice;
                }
            }

            $value->penjualan = $stokJual;
            $value->detail_penjualan = $detailPenjualan;

            if (count($detailPenjualan) > 0) {
                $temp[] = $value;
            }
        }

        $data['bulan'] = $bulan;
        $data['produks'] = $temp;
        // return $data;

        return $this->laporanRekapProduk($data, $bulan, $jenis);
    }

    public function laporanRekapProduk($data, $bulan, $jenis)
    {
        $spreadsheet = new Spreadsheet();

        $spreadsheet->getProperties()->setCreator('CV AYYUB TANI')
            ->setLastModifiedBy('CV AYYUB TANI')
            ->setTitle('Laporan Rekap Produk Bulanan')
            ->setSubject('Laporan Rekap Produk Bulanan')
            ->setDescription('Laporan Rekap Produk Bulanan')
            ->setKeywords('pdf php')
            ->setCategory('Laporan Rekap Stok Bulanan');

        $sheet = $spreadsheet->getActiveSheet();
        // $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT);
        $sheet->getPageSetup()->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_FOLIO);

        $sheet->getRowDimension(1)->setRowHeight(17);
        $sheet->getRowDimension(2)->setRowHeight(17);
        $sheet->getRowDimension(3)->setRowHeight(7);
        $spreadsheet->getDefaultStyle()->getFont()->setName('Times New Roman');
        $spreadsheet->getDefaultStyle()->getFont()->setSize(10);
        $spreadsheet->getActiveSheet()->getPageSetup()->setHorizontalCentered(true);
        $spreadsheet->getActiveSheet()->getPageSetup()->setVerticalCentered(false);

        // //Margin PDF
        $spreadsheet->getActiveSheet()->getPageMargins()->setTop(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setRight(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setLeft(0.3);
        $spreadsheet->getActiveSheet()->getPageMargins()->setBottom(0.3);

        $tahun = ""  . session('tahun') . "-" . $bulan . "";
        $periode = ($bulan != 'all') ? strtoupper(strftime('%B %Y', mktime(0, 0, 0, $bulan + 1, 0, (int)session('tahun')))) : (int)session('tahun');

        $sheet->setCellValue('A1', 'LAPORAN REKAPITULASI PRODUK')->mergeCells('A1:E1');
        $sheet->setCellValue('A2', 'CV. AYYUB TANI')->mergeCells('A2:E2');
        $sheet->setCellValue('A3', "PERIODE $periode")->mergeCells('A3:E3');

        $sheet->setCellValue('A5', 'No')->mergeCells('A5:A5');
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->setCellValue('B5', 'Nama Produk');
        $sheet->getColumnDimension('B')->setWidth(40);
        $sheet->setCellValue('C5', 'Kemasan');
        $sheet->getColumnDimension('C')->setWidth(35);
        $sheet->setCellValue('D5', 'Invoice');
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->setCellValue('E5', 'Penjualan');
        $sheet->getColumnDimension('E')->setWidth(15);

        $cell = 5;
        $merge = 0;

        // return $data;
        foreach ($data['produks'] as $index => $value) {
            // return $merge;
            $cell++;
            $merge = $cell + (count($value->detail_penjualan) - 1);

            $sheet->setCellValue('A' . $cell, $index + 1)->mergeCells('A' . $cell . ':A' . $merge);
            $sheet->setCellValue('B' . $cell, strtoupper($value->nama_produk))->mergeCells('B' . $cell . ':B' . $merge);
            $sheet->setCellValue('C' . $cell, strtoupper($value->kemasan))->mergeCells('C' . $cell . ':C' . $merge);

            $index = $cell;
            foreach ($value->detail_penjualan as $k => $v) {
                $sheet->setCellValue('D' . $index, $v->invoice);
                $sheet->setCellValue('E' . $index, $v->ket);
                $index++;
            }

            $cell = $merge;
        }

        $sheet->getStyle('A1:A3')->getFont()->setSize(12);
        $sheet->getStyle('A1:A3')->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('A:E')->getAlignment()->setWrapText(true);
        $sheet->getStyle('A5:E5')->getFont()->setBold(true);
        $sheet->getStyle('A5:E5')->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('A5:A' . $cell)->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('B5:B' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center');
        $sheet->getStyle('B5:B' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center');
        $sheet->getStyle('C5:C' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('D5:D' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');
        $sheet->getStyle('E5:E' . (count($data['produks']) + $cell))->getAlignment()->setVertical('center')->setHorizontal('center');

        $border = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '0000000'],
                ],
            ],
        ];

        $sheet->getStyle('A5:E' . $cell)->applyFromArray($border);

        if ($jenis == 'excel') {
            // Untuk download 
            $writer = new Xlsx($spreadsheet);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="Rekapitulasi Laporan Produk CV. AYYUB TANI ' . $periode . '.xlsx"');
        } else {
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddHeader('&C&H' . url()->current());
            $spreadsheet->getActiveSheet()->getHeaderFooter()
                ->setOddFooter('&L&B &RPage &P of &N');
            $class = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class;
            \PhpOffice\PhpSpreadsheet\IOFactory::registerWriter('Pdf', $class);
            header('Content-Type: application/pdf');
            header('Cache-Control: max-age=0');
            $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Pdf');
        }

        $writer->save('php://output');
    }
}
