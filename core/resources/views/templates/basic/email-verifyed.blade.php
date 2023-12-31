@extends($activeTemplate.'layouts.frontendLeadPaid')
@section('content')
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://leadspaid.com/assets/templates/basic/css/all.min.css">
<section class="Rg_advts">
    <div class="container">

        <div class="row text-center">
            <div class="col-lg-12 pt-4">
                <p class="Rg_advts_ttls-1 mb-5">{!! $title !!}</p>
                <p class="Rg_advts_ttls-1 mb-5">{!! $sub_title !!}</p>
            </div>
        </div>

    </div>
 </section>
@endsection
<style>
    .Rg_advts{ padding-top: 60px; }
    .Rg_advts_ttls-1 {
    color: #191f58;
    font-family: Poppins !important;
    font-weight: 600;
    font-size: 38px;
    letter-spacing: 1px;
}
</style>
