<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\Contracts\FileStorageServiceContract;
use App\Services\FileStorageService;
use App\Services\ImagesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductsController extends Controller
{
    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $products = Product::with('category')->paginate(10);

        return view('admin/products/index', compact('products'));
    }

    /**
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin/products/create', compact('categories'));
    }

    /**
     * @param CreateProductRequest $request
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Throwable
     */
    public function store(CreateProductRequest $request)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $images = $data['images'] ?? [];
            $category = Category::find($data['category']);
            $product = $category->products()->create($data); // category_id
            ImagesService::attach($product, 'imageasfasfs', $images);

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('status', "The product #{$product->id} was successfully created!");
        } catch (\Exception $e) {
            DB::rollBack();
            logs()->warning($e);
            return redirect()->back()->with('warn', 'Oops smth wrong. See logs')->withInput();
        }
    }

    /**
     * @param Product $product
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Product $product)
    {
        $categories = Category::all();
        return view('admin/products/edit', compact('product', 'categories'));
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();
            $thumbnail = $request->thumbnail != null ? FileStorageService::upload($request->thumbnail) : $product->thumbnail;
            $images = $request->images ?? [];
            $data['thumbnail'] = $thumbnail;
            ImagesService::attach($product, 'images', $images);
            Product::where('id', '=', $product->id)->update($data);

            DB::commit();

            return redirect()->route('admin.products.index')
                ->with('status', "The product #{$product->id} was successfully updated!");
        }catch (\Exception $e) {
            DB::rollBack();
            logs()->warning($e);
            return redirect()->back()->with('warn', 'Oops smth wrong. See logs')->withInput();
        }
    }

    /**
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Product $product)
    {
        $product->images()->delete();
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('status', "The product #{$product->id} deleted!");
    }
}
