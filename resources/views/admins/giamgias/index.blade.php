{{-- extends: quy định master layout kế thừa  --}}
@extends('layouts.admin')
{{-- section dùng để định nghĩa nội dung của của section --}}




@section('css')
    {{-- Nơi để các đường dẫn file CSS và thư viện CSS dùng riêng cho trang --}}
    {{-- Hàm asset() dùng để trỏ đường dẫn trong laravel --}}
    <link rel="stylesheet" href="{{asset('assets/admins/css/index.css')}}">
@endsection



@section('content')


    <div class="d-flex justify-content-center container" >
        <div id="description" class="tab-content active mt-4 container">
            <h1 class="d-flex justify-content-center">{{$title}}</h1>
            <a href="{{route('giamgia.create')}}"><button class="btn btn-success " >Thêm mới</button></a>

              {{-- HIỂN THỊ THÔNG BÁO --}}
              @if (session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
              
                  
              @endif

            <table class="table table-striped mt-3">
              <thead>
                <tr>
                  <th>Id</th>
                  <th>Tên mã giảm giá</th>
                  <th>Tiền giảm giá</th>
                  <th>Ngày hết hạn</th>
                  <th>Hành động</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($listMa as $item)
                <tr>
                    <td>{{$item->id}}</td>
                    
                    <td>{{$item->code}}</td>
                    <td>{{number_format($item->discount_amount, '0', '.', '')}} đ</td>
                    <td>{{$item->expiry_date}}</td>
                   
                    <td>
                      <form action="{{route('giamgia.destroy', $item->id)}}" method="POST" onclick="return confirm('Bạn có muốn xoá mã giảm giá này không?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger">Xoá</button>
                      </form>
                    </td>
                  </tr>
                
                @endforeach
              </tbody>
            </table>
          
          
        </div>


        


       
    </div>
</div>
    


   
@endsection

@section('js')
    {{-- Nơi để các đường dẫn file JS và thư viện JS dùng riêng cho trang --}}
   <script src="{{asset('assets/admins/js/list.js')}}"></script>
@endsection
