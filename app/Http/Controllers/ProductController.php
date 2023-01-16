<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ProductController extends Controller
{
    public $data = [];
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $this->data['products'] = Product::with('productImages','productVariants','productVariantPrices')->orderByDesc('id')->paginate(5);
        $this->data['request'] = $request;
        $this->data['variants'] = Variant::with('productVariants')->get();
        return view('products.index',$this->data);
    }

    public function filter(Request $request){
        $this->data['products'] = Product::with('productImages','productVariants','productVariantPrices')->where(function($query)use($request){
            if($request->title){
                $query->where('title', 'like', '%' . $request->title . '%');
            }
            if($request->variant) {
                $query->whereHas('productVariants', function ($vq) use ($request) {
                    $vq->where('variant', 'like', '%' . $request->variant . '%');
                });
            }
            if($request->date){
                $query->whereDate('created_at', '=', date('Y-m-d', strtotime( $request->date)));
            }
            if($request->price_from && $request->price_to ) {
                $query->whereHas('productVariantPrices', function ($sq) use ($request) {
                    $sq->where([['price', '>=', $request->price_from], ['price', '<=', $request->price_to]
                    ]);
                });
            }

        })->orderByDesc('id')->paginate(5);
        $this->data['request'] = $request;
        $this->data['variants'] = Variant::with('productVariants')->get();
        return view('products.index',$this->data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

        $validator = new ProductRequest();
        $validator = Validator::make($request->all(), $validator->rules());
        if (!$validator->fails()) {
            try {
                DB::transaction(function () use ($request) {
                    $product = new Product;
                    $product->title = $request->title;
                    $product->sku = $request->sku;
                    $product->description = $request->description;
                    $product->save();

                    $dataOptions = [];
                    if (!blank($request->product_variant)) {
                        foreach ($request->product_variant as $variant) {
                            if (!blank($variant['tags'])) {
                                foreach ($variant['tags'] as $tag) {
                                    $variants = new ProductVariant;
                                    $variants->variant = $tag;
                                    $variants->variant_id = $variant['option'];
                                    $variants->product_id = $product->id;
                                    $variants->save();
                                    $dataOptions[$variant['option']][] = $variants->id;
                                }
                            }
                        }
                    }

                    $variantData = array_values($dataOptions);
                    $variantDataMatrix = $this->crossJoin($variantData);

                    if (!blank($request->product_variant_prices)) {
                        foreach ($request->product_variant_prices as $key => $product_variant_price) {
                            $productVariantPrice = new ProductVariantPrice;
                            $productVariantPrice->product_variant_one = isset($variantDataMatrix[$key][0]) ? $variantDataMatrix[$key][0] : null;
                            $productVariantPrice->product_variant_two = isset($variantDataMatrix[$key][1]) ? $variantDataMatrix[$key][1] : null;
                            $productVariantPrice->product_variant_three = isset($variantDataMatrix[$key][2]) ? $variantDataMatrix[$key][2] : null;
                            $productVariantPrice->price = $product_variant_price['price'] ?? 0;
                            $productVariantPrice->stock = $product_variant_price['stock'] ?? 0;
                            $productVariantPrice->product_id = $product->id;
                            $productVariantPrice->save();
                        }

                    }
                });
                return response()->json(['success' => 'The product inserted successfully!']);
            } catch (\Exception $exception) {
                DB::rollBack();
                return response()->json(['errors' => $exception->getMessage()]);
            }
        }else {
            return response()->json(['errors' => $validator->errors()]);
        }

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $this->data['variants'] = Variant::all();
        $this->data['product'] = $product->load('productImages','productVariants','productVariantPrices');
        return view('products.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(ProductRequest $request, Product $product)
    {
        try {
            DB::transaction(function () use ($request,$product) {

                $product->title = $request->title;
                $product->sku = $request->sku;
                $product->description = $request->description;
                $product->save();

                $dataOptions = [];
                if (!blank($request->product_variant)) {
                    foreach ($product->productVariants as $post) {
                        $post->delete();
                    }

                    foreach ($request->product_variant as $variant) {
                        if (!blank($variant['tags'])) {
                            foreach ($variant['tags'] as $tag) {
                                $variants = new ProductVariant;
                                $variants->variant = $tag;
                                $variants->variant_id = $variant['option'];
                                $variants->product_id = $product->id;
                                $variants->save();
                                $dataOptions[$variant['option']][] = $variants->id;
                            }
                        }
                    }
                }

                $variantData = array_values($dataOptions);
                $variantDataMatrix = $this->crossJoin($variantData);

                if (!blank($request->product_variant_prices)) {
                    foreach ($product->productVariantPrices as $postPrice) {
                        $postPrice->delete();
                    }
                    foreach ($request->product_variant_prices as $key => $product_variant_price) {
                        $productVariantPrice = new ProductVariantPrice;
                        $productVariantPrice->product_variant_one = isset($variantDataMatrix[$key][0]) ? $variantDataMatrix[$key][0] : null;
                        $productVariantPrice->product_variant_two = isset($variantDataMatrix[$key][1]) ? $variantDataMatrix[$key][1] : null;
                        $productVariantPrice->product_variant_three = isset($variantDataMatrix[$key][2]) ? $variantDataMatrix[$key][2] : null;
                        $productVariantPrice->price = $product_variant_price['price'] ?? 0;
                        $productVariantPrice->stock = $product_variant_price['stock'] ?? 0;
                        $productVariantPrice->product_id = $product->id;
                        $productVariantPrice->save();
                    }

                }
            });
            return response()->json(['success' => 'The product update successfully!']);
        } catch (\Exception $exception) {
            DB::rollBack();
            return response()->json(['errors' => $exception->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }

    public function imageUpload($request,$product)
    {

        if($request->hasfile('product_image'))
        {
            foreach($request->file('product_image') as $file)
            {
                $name = time().rand(1,100).'.'.$file->extension();
                $file->move(public_path('files'), $name);
                $file= new ProductImage();
                $file->file_path = $name;
                $file->product_id = $product->id;
                $file->save();
            }
        }

    }

    private function crossJoin($arrays)
    {
        $results = [[]];
        foreach ($arrays as $index => $array) {
            $append = [];
            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

}
