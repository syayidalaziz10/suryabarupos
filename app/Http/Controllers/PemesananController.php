<?php

namespace App\Http\Controllers;

use App\Models\Pemesanan;
use App\Models\DetailPemesanan;
use App\Models\Menu;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PemesananController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function index(Request $request)
    // {
    //     $data['title'] = 'Menu';
    //     $data['q'] = $request->get('q');
    //     $data['menu'] = Menu::where('nama_menu', 'like', '%' .$data['q']. '%')->join('kategori', 'menu.id_kategori', '=', 'kategori.id_kategori')->get();
    //     return view('pemesanan.index', $data);
    // }

    //SUM PENDAPATAN PERBULAN
    // SELECT SUM(dp.jumlah*mn.harga) FROM detail_pemesanan as dp
    // INNER JOIN pemesanan as ps, menu as mn
    // WHERE dp.id_menu = mn.id_menu 
    // AND ps.id_pemesanan = dp.id_pemesanan 
    // AND ps.tanggal_pemesanan 
    // BETWEEN  "2022-12-14" AND "2022-12-25"

    //GET DATA PEMESANAN PERBULAN
    // SELECT * FROM detail_pemesanan as dp
    // INNER JOIN pemesanan as ps, menu as mn
    // WHERE dp.id_menu = mn.id_menu 
    // AND ps.id_pemesanan = dp.id_pemesanan 
    // AND ps.tanggal_pemesanan 
    // BETWEEN  "2022-12-14" AND "2022-12-25"

    //GET JUMLAH MENU YANG DIPESAN PERBULAN
    // SELECT SUM(dp.jumlah) FROM detail_pemesanan as dp
    // INNER JOIN pemesanan as ps, menu as mn
    // WHERE dp.id_menu = mn.id_menu 
    // AND ps.id_pemesanan = dp.id_pemesanan 
    // AND dp.id_menu = 8
    // AND ps.tanggal_pemesanan 
    // BETWEEN  "2022-12-14" AND "2022-12-21";



    public function index(Request $request)
    {
        // ddd($request);
        date_default_timezone_set('Asia/Jakarta');
        $now = date('Y-m-d');
        $data = [
            'title' => 'Data Penjualan Hari Ini',
            'page'  => 'hariini',
            'q'     => $request->get('q'),
            'dataDetail' => []
        ];

        $totalPendapatan = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->join('menu AS mn', 'mn.id_menu', '=', 'dp.id_menu')
            ->where('ps.tanggal_pemesanan', '=', '2023-06-13')
            ->select(DB::raw('SUM(dp.jumlah * mn.harga) AS total'))
            ->first();

        $data['totalPendapatan'] = $totalPendapatan->total;



        $jumlahMenu = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->where('ps.tanggal_pemesanan', '=', '2023-06-13')
            ->select(DB::raw('SUM(dp.jumlah) AS jumlah'))
            ->first();

        $data['jumlahMenu'] = $jumlahMenu->jumlah;

        $banyakMenu = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->where('ps.tanggal_pemesanan', '=', '2023-06-13')
            ->select(DB::raw('COUNT(DISTINCT dp.id_menu) AS banyak'))
            ->first();

        $data['banyakMenu'] = $banyakMenu->banyak;


        $data['totalPesanan'] = Pemesanan::where('tanggal_pemesanan', 'like', '%' . $now . '%')->count();

        $dataNow = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->where('ps.tanggal_pemesanan', '=', '2023-06-13')
            ->select('dp.id_menu AS idMenu')
            ->distinct()
            ->get();

        $data['dataDetail'] = [];

        foreach ($dataNow as $dn) {
            $jmlMenu = DB::table('detail_pemesanan AS dp')
                ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
                ->where('dp.id_menu', '=', $dn->idMenu)
                ->where('ps.tanggal_pemesanan', '=', $now)
                ->select(DB::raw('SUM(dp.jumlah) AS jml'))
                ->first();

            $menu = Menu::where('id_menu', '=', $dn->idMenu)->get();

            $dataCount = [
                "jmlMenu" => $jmlMenu->jml,
                "menu" => $menu
            ];

            array_push($data['dataDetail'], $dataCount);
        }



        // ddd($data["dataDetail"]);
        // if ($data['q'] === null ) {
        //     $data['q'] = Carbon::today()->toDateString();
        // }
        $data['pemesanan']       = Pemesanan::where('tanggal_pemesanan', 'like', '%' . $now . '%')->join('user', 'pemesanan.id_user', '=', 'user.id_user')->get();
        // $data['jumlahPenjualan'] = Pemesanan::where('tanggal_pemesanan','like','%' .$data['q']. '%')->get()->count();
        // return view('pemesanan.report', $data);
        // ddd($data["pemesanan"]);
        return view('admin.hariini', $data);
    }

    public function reportBulan(Request $request)
    {
        // ddd($request);
        $data = [
            'title' => 'Data Penjualan Per Bulan',
            'page'  => 'bulanan',
            'q'     => $request->get('q'),
            'r'     => $request->get('r'),
            'dataDetail' => []
        ];

        if ($data['q'] === null &&  $data['r'] === null) {
            $data['q'] = Carbon::today()->startOfMonth()->toDateString();
            $data['r'] = Carbon::today()->endOfMonth()->toDateString();
        }

        $data['totalPendapatan'] = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->join('menu AS mn', 'mn.id_menu', '=', 'dp.id_menu')
            ->whereBetween('ps.tanggal_pemesanan', [$data['q'], $data['r']])
            ->select(DB::raw('SUM(dp.jumlah * mn.harga) AS total'))
            ->first()->total;

        $data['jumlahMenu'] = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->whereBetween('ps.tanggal_pemesanan', [$data['q'], $data['r']])
            ->select(DB::raw('SUM(dp.jumlah) AS jumlah'))
            ->first()->jumlah;

        $data['banyakMenu'] = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->whereBetween('ps.tanggal_pemesanan', [$data['q'], $data['r']])
            ->select(DB::raw('COUNT(DISTINCT dp.id_menu) AS banyak'))
            ->first()->banyak;

        $data['totalPesanan'] = Pemesanan::whereBetween('tanggal_pemesanan', [$data['q'], $data['r']])->count();

        $dataNow = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->whereBetween('ps.tanggal_pemesanan', [$data['q'], $data['r']])
            ->select('dp.id_menu AS idMenu')
            ->distinct()
            ->get();

        $data['dataDetail'] = [];

        foreach ($dataNow as $dn) {
            $jmlMenu = DB::table('detail_pemesanan AS dp')
                ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
                ->where('dp.id_menu', '=', $dn->idMenu)
                ->whereBetween('ps.tanggal_pemesanan', [$data['q'], $data['r']])
                ->select(DB::raw('SUM(dp.jumlah) AS jml'))
                ->first()->jml;

            $menu = Menu::where('id_menu', '=', $dn->idMenu)->get();

            $dataCount = [
                "jmlMenu" => $jmlMenu,
                "menu" => $menu
            ];

            array_push($data['dataDetail'], $dataCount);
        }


        // ddd($data);

        $data['pemesanan'] = Pemesanan::whereBetween('tanggal_pemesanan', [$data['q'], $data['r']])->join('user', 'pemesanan.id_user', '=', 'user.id_user')->get();
        // ddd($data['pemesanan']);
        // return view('pemesanan.reportbulan', $data);
        return view('admin.report', $data);
    }

    public function reportHarian(Request $request)
    {
        // ddd($request);
        $data = [
            'title' => 'Data Penjualan Per Hari',
            'page'  => 'harian',
            'q'     => $request->get('q'),
            'dataDetail' => []
        ];

        if ($data['q'] === null) {
            $data['q'] = Carbon::today()->toDateString();
        }

        $data['totalPendapatan'] = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->join('menu AS mn', 'mn.id_menu', '=', 'dp.id_menu')
            ->where('ps.tanggal_pemesanan', '=', $data['q'])
            ->select(DB::raw('SUM(dp.jumlah * mn.harga) AS total'))
            ->first()->total;

        $data['jumlahMenu'] = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->where('ps.tanggal_pemesanan', '=', $data['q'])
            ->select(DB::raw('SUM(dp.jumlah) AS jumlah'))
            ->first()->jumlah;

        $data['banyakMenu'] = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->where('ps.tanggal_pemesanan', '=', $data['q'])
            ->select(DB::raw('COUNT(DISTINCT dp.id_menu) AS banyak'))
            ->first()->banyak;

        $data['totalPesanan'] = Pemesanan::where('tanggal_pemesanan', $data['q'])->count();

        $dataNow = DB::table('detail_pemesanan AS dp')
            ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
            ->where('ps.tanggal_pemesanan', '=', $data['q'])
            ->select('dp.id_menu AS idMenu')
            ->distinct()
            ->get();

        $data['dataDetail'] = [];

        foreach ($dataNow as $dn) {
            $jmlMenu = DB::table('detail_pemesanan AS dp')
                ->join('pemesanan AS ps', 'ps.id_pemesanan', '=', 'dp.id_pemesanan')
                ->where('dp.id_menu', '=', $dn->idMenu)
                ->where('ps.tanggal_pemesanan', '=', $data['q'])
                ->select(DB::raw('SUM(dp.jumlah) AS jml'))
                ->first()->jml;

            $menu = Menu::where('id_menu', '=', $dn->idMenu)->get();

            $dataCount = [
                "jmlMenu" => $jmlMenu,
                "menu" => $menu
            ];

            array_push($data['dataDetail'], $dataCount);
        }


        // ddd($data);

        $data['pemesanan'] = Pemesanan::where('tanggal_pemesanan', $data['q'])->join('user', 'pemesanan.id_user', '=', 'user.id_user')->get();
        // ddd($data['pemesanan']);
        // return view('pemesanan.reportbulan', $data);
        return view('admin.report_hari', $data);
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
        $row = explode(',', $request->nama_menu);
        $raw = explode(',', $request->jumlah);
        $pemesanan = new Pemesanan([
            'id_user' => Auth::user()->id_user,
            'tanggal_pemesanan' => Carbon::today()->toDateString(),
            'total_biaya' => 0
        ]);
        $pemesanan->save();
        // $validatedData->id_pemesanan;
        // $pemesanan = Pemesanan::create($validatedData);
        $pemesanan->id_pemesanan;
        for ($i = 0; $i < count($row); $i++) {
            $detailPemesanan = new DetailPemesanan([
                'id_pemesanan' => $pemesanan->id_pemesanan,
                'id_menu' => "" . $row[$i],
                'jumlah' => $raw[$i],

            ]);
            $detailPemesanan->save();

            $menu = Menu::find($row[$i]);

            if ($menu) {
                // Subtract the quantity from the stock
                $menu->stok -= $raw[$i];
                $menu->save();
            }
        }
        return redirect()->route('kasir.detail', ['pemesanan' => $pemesanan->id_pemesanan])->with('success', 'Success Creating pemesanan');
    }
}
