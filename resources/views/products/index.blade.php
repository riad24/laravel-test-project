@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('Products') }}</h1>
    </div>
    <div class="card">
        <form action="{{route('product.filter')}}"  method="GET" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" value="{{old('title', $request->title)}}" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                        <option>{{ __('--Select A Variant--') }}</option>
                    @if(!blank($variants))
                            @foreach($variants as $variantData)
                                <optgroup label="{{  $variantData->title }}">
                                    @if(!blank($variantData->productVariants))
                                    @foreach($variantData->productVariants()->select('variant')->groupBy('variant')->get() as $variantName)
                                        <option value="{{ $variantName->variant }}" {{ old('variant',$request->variant) == $variantName->variant ? 'selected':'' }}>{{ $variantName->variant }}</option>
                                    @endforeach
                                    @endif
                                </optgroup>
                            @endforeach
                            @endif

                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">{{ __('Price Range') }}</span>
                        </div>
                        <input type="text" name="price_from" value="{{old('date', $request->price_from)}}" aria-label="First name" placeholder="From" class="form-control">
                        <input type="text" name="price_to" value="{{old('date', $request->price_to)}}" aria-label="Last name" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" value="{{old('date', $request->date)}}" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>

        <div class="card-body">

            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>{{ __('#') }}</th>
                        <th>{{ __('Title') }}</th>
                        <th>{{ __('Description') }}</th>
                        <th>{{ __('Variant') }}</th>
                        <th width="150px">{{ __('Action') }}</th>
                    </tr>
                    </thead>
                    @if(!blank($products))
                    <tbody>
                    @php($id=1)
                     @foreach($products as $product)
                        <tr>
                        <td>{{$id++}}</td>
                        <td>{{$product->title}} <br> {{ __('Created at') }} : {{$product->date}}</td>
                        <td>{!!  \Illuminate\Support\Str::of($product->description)->limit(30) !!}</td>
                        <td>
                            @if(!blank($product->productVariantPrices))
                                @foreach($product->productVariantPrices as $productVarint)
                                    <dl class="row mb-0" style="height: 50px; overflow: hidden" id="variant">
                                <dt class="col-sm-4 pb-0">
                                    {{$productVarint->variantName}}
                                </dt>
                                <dd class="col-sm-8">
                                    <dl class="row mb-0">
                                        <dt class="col-sm-4 pb-0">{{ __('Price') }} : {{ number_format($productVarint->price,2) }}</dt>
                                        <dd class="col-sm-8 pb-0">{{ __('InStock') }} : {{ number_format($productVarint->stock,2) }}</dd>
                                    </dl>
                                </dd>
                            </dl>
                                @endforeach
                            <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">{{ __('Show more') }}</button>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('product.edit', $product) }}" class="btn btn-success">{{ __('Edit') }}</a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    </tbody>
                    @endif
                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row justify-content-between">
                <div class="col-md-6">
                    <p>    {!! __('Showing') !!}
                        <span>{{ $products->firstItem() }}</span>
                        {!! __('to') !!}
                        <span>{{ $products->lastItem() }}</span>
                        {!! __('of') !!}
                        <span>{{ $products->total() }}</span>
                        {!! __('results') !!}</p>
                </div>
                <div class="col-md-2">
                    {!! $products->links() !!}
                </div>
            </div>
        </div>
    </div>

@endsection
