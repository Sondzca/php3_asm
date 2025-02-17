<?php

namespace App\Http\Controllers\Clients;
 
use App\Models\DonHang;
use App\Models\GiamGias;
use App\Mail\OrderConfirm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\OrderRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $donHangs = Auth::user()->donHang;
        $trangThaiDonHang = DonHang::TRANG_THAI_DON_HANG;
        $type_cho_xac_nhan = DonHang::CHO_XAC_NHAN;
        $type_dang_van_chuyen = DonHang::DANG_VAN_CHUYEN;
        return view('clients.donhangs.index', compact('donHangs', 'trangThaiDonHang', 'type_cho_xac_nhan', 'type_dang_van_chuyen'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
            $carts = session()->get('cart', []);
            if (!empty($carts)) {
                $total = 0;
                $subTotal = 0;
                $discountAmount = 0;
        
                foreach ($carts as $item) {
                    $subTotal += $item['gia'] * $item['so_luong'];
                }
                $shipping = 30000;
                $total = $subTotal + $shipping;
        
                // Kiểm tra mã giảm giá
                $couponCode = session()->get('coupon_code');
                // dd($couponCode);
                if ($couponCode) {
                    $coupon = GiamGias::where('code', $couponCode)->first();
                    if ($coupon) {
                        $discountAmount = $coupon->discount_amount;
                        $total -= $discountAmount;
                    }
                }
        
                return view('clients.donhangs.create', compact('carts', 'subTotal', 'shipping', 'total', 'discountAmount', 'couponCode'));
            }
        
            return redirect()->route('cart.list');
        
        
    }

    public function store(OrderRequest $request)
    {
        if ($request->isMethod('POST')) {
            DB::beginTransaction();
            try {
                $params = $request->except('_token');
                $params['ma_don_hang'] = $this->generateUniqueOrderCode();

                // Kiểm tra mã giảm giá
                $couponCode = session()->get('coupon_code');
                if ($couponCode) {
                    $coupon = GiamGias::where('code', $couponCode)->first();
                    if ($coupon) {
                        $params['discount_code'] = $couponCode;
                        $params['discount_amount'] = $coupon->discount_amount;
                    }
                }

                $donHang = DonHang::query()->create($params);
                $donHangId = $donHang->id;

                // Kiểm tra dữ liệu cart
                $carts = session()->get('cart', []);

                foreach ($carts as $key => $item) {
                    $thanhTien = $item['gia'] * $item['so_luong'];

                    $donHang->chiTietDonHang()->create([
                        'don_hang_id' => $donHangId,
                        'san_pham_id' => $key,
                        'don_gia' => $item['gia'],
                        'so_luong' => $item['so_luong'],
                        'thanh_tien' => $thanhTien,
                    ]);
                }

                DB::commit();

                // Khi thêm sản phẩm thành công thực hiện các công việc dưới này
                // Gửi mail khi đặt hàng thành công
                Mail::to($donHang->email_nguoi_nhan)->queue(new OrderConfirm($donHang));
                session()->put('cart', []);
                session()->forget('coupon_code'); // Xóa mã giảm giá khỏi session

                return redirect()->route('order.index')->with('success', 'Đơn hàng được tạo thành công!');
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->route('cart.list')->with('error', 'Có lỗi khi tạo đơn hàng!');
            }
        }
    }
    

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $donHang = DonHang::query()->findOrFail($id);
        $trangThaiDonHang = DonHang::TRANG_THAI_DON_HANG;
        $trangThaiThanhToan = DonHang::TRANG_THAI_THANH_TOAN;

        return view('clients.donhangs.show', compact('donHang', 'trangThaiDonHang', 'trangThaiThanhToan'));

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $donHang = DonHang::query()->findOrFail($id);
        DB::beginTransaction();

        try{
            if($request->has('huy_don_hang')) {
                $donHang->update(['trang_thai_don_hang' => DonHang::HUY_DON_HANG]);
            } elseif($request->has('da_giao_hang')) {
                $donHang->update(['trang_thai_don_hang' => DonHang::DA_GIAO_HANG]);
            }

            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
        }
        return redirect()->back();

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function generateUniqueOrderCode()
    {
        do{
            $orderCode = 'ORD-' . Auth::id() . '-' . now()->timestamp;
        } while (DonHang::where('ma_don_hang', $orderCode)->exists());
        return $orderCode;
    }
}
