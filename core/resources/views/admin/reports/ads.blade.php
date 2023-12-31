@extends('admin.layouts.app')

@section('panel')

    <div class="row">

        <div class="col-lg-12">
            <div class="card b-radius--10 ">
                <div class="card-body p-0">
                    <div class="table-responsive--md  table-responsive">
                        <table class="table table--light style--two" id="datatable5">
                            <thead>
                            <tr>
                                <th scope="col">@lang('Ad Name')</th>
                                <th scope="col">@lang('Redirect Url')</th>
                                <th scope="col">@lang('Type')</th>
                                <th scope="col">@lang('Status')</th>
                                <th scope="col">@lang('Action')</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if(!empty($ads))
                            @foreach($ads as $ad)
                            <tr>
                                <td data-label="@lang('Ad Name')">{{ $ad->ad_name }}</td>
                                <td data-label="@lang('Redirect Url')" class="text--primary">{{ $ad->redirect_url}}</td>
                                <td data-label="@lang('Type')"><span class="badge badge-pill bg--warning"> {{ $ad->ad_type }}</span></td>
                                @if ($ad->status)
                                <td data-label="@lang('Status')"><span class="text--small badge font-weight-normal badge--success">@lang('Active')</span></td>
                                @else
                                <td data-label="@lang('Status')"><span class="text--small badge font-weight-normal badge--warning">@lang('Deactive')</span></td>
                                @endif
                                <td data-label="@lang('Action')">
                                    <a href="{{ route('admin.advertise.details',$ad->id) }}" class="icon-btn" data-toggle="tooltip" title="" data-original-title="Details">
                                        <i class="las la-desktop text--shadow"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                              @endif

                            </tbody>
                        </table><!-- table end -->
                    </div>
                </div>
            </div><!-- card end -->
        </div>
    </div>
@endsection

@push('style')
<style>
    table thead tr th:after {
    top: 14px !important;
}
table thead tr th:before {
    bottom: 14px !important;
}
</style>
@endpush

@push('script')
    <script>

 $('#datatable5').DataTable({
            
            "sDom": 'Lfrtlip',
            "language": {
                "lengthMenu": "Show rows  _MENU_",
                search: "",
                searchPlaceholder: "Search"
            }
           
        });

</script>
@endpush