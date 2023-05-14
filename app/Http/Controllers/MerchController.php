<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Merch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;

class MerchController extends Controller
{

    public function create()
    {
        return Inertia::render('Menus/Artist/AddMerch');
    }

    public function store(Request $request)
    {
        Validator::make($request->all(), [
            'merch_title' => 'required:unique',
            'merch_category' => 'required',
            'merch_image' => 'required|array',
            'merch_image.*' => 'image|mimes:jpg,jpeg,png,svg|max:2048',
            'merch_description' => 'nullable',
            'merch_price' => 'required|numeric|min:500',
        ])->validate();

        $merch = new Merch([
            'merch_title' => $request->merch_title,
            'merch_category' => $request->merch_category,
            'merch_description' => $request->merch_description,
            'merch_price' => $request->merch_price,
            'merch_user_id' => $request->user()->id,
        ]);

        $merchImages = [];
        foreach ($request->file('merch_image') as $image) {
            $destinationPath = public_path('images/merches/thumbnails');
            $merchImageName = $merch->merch_title . $merch->id . "_" . time() . mt_rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
            $img = Image::make($image->path());
            $img->fit(280, 280, function ($const) {
                $const->aspectRatio();
            })->save($destinationPath . '/thumb_' . $merchImageName, 90);

            $destinationPath = 'images/merches/main';
            $image->move($destinationPath, $merchImageName);
            $merchImages[] = $merchImageName;
        }

        $merch->merch_image = json_encode($merchImages);
        $merch->save();

        return redirect()->route('artist.dashboard');
    }

    public function edit($id)
    {
        $posts  = Merch::with('user')->find($id);

        if ($posts->merch_user_id == Auth::id()) {
            return Inertia::render('Menus/Artist/EditMerch', [
                'merches' => $posts,
            ]);
        } else {
            return redirect("https://http.cat/403");
        }
    }

    public function update(Request $request, $id)
    {
        if ($request->has('data')) {
            $requestData = $request->input('data');

            if (isset($requestData['merch_image']) && is_string($requestData['merch_image'])) {
                $requestData['merch_image'] = json_decode($requestData['merch_image'], true);
            }

            if (isset($requestData['merch_image']) && is_array($requestData['merch_image'])) {
                $request->merge(['data' => array_merge($requestData, ['merch_image' => $requestData['merch_image']])]);
            }
        }

        Validator::make($request->all(), [
            'data.merch_title' => 'required',
            'data.merch_category' => 'required',
            'data.merch_image' => 'nullable|array|min:1',
            'data.merch_image.*' => 'nullable|max:2048',
            'data.merch_description' => 'nullable',
            'data.merch_price' => 'required|numeric|min:500',
        ])->validate();

        $merch = Merch::findOrFail($id);
        $merch->merch_title = $request->input('data')['merch_title'];
        $merch->merch_category = $request->input('data')['merch_category'];
        $merch->merch_description = $request->input('data')['merch_description'];
        $merch->merch_price = $request->input('data')['merch_price'];

        $isImageChanged = false;

        if ($merch) {
            $oldMerchImages = json_decode($merch->merch_image);

            if (!empty($request->file('data.merch_image'))) {
                $merchImages = [];
                foreach ($request->file('data.merch_image') as $image) {
                    $destinationPath = public_path('images/merches/thumbnails');
                    $merchImageName = $merch->merch_title . $merch->id . "_" . time() . mt_rand(1000, 9999) . '.' . $image->getClientOriginalExtension();
                    $img = Image::make($image->path());
                    $img->fit(280, 280, function ($const) {
                        $const->aspectRatio();
                    })->save($destinationPath . '/thumb_' . $merchImageName, 90);

                    $destinationPath = 'images/merches/main';
                    $image->move($destinationPath, $merchImageName);
                    $merchImages[] = $merchImageName;
                }

                $merch->merch_image = json_encode($merchImages);
                $isImageChanged = true;
            }

            if ($isImageChanged && !empty($oldMerchImages)) {
                foreach ($oldMerchImages as $oldImage) {
                    $imagePath = public_path('images/merches/main/' . $oldImage);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                    $thumbImagePath = public_path('images/merches/thumbnails/thumb_' . $oldImage);
                    if (file_exists($thumbImagePath)) {
                        unlink($thumbImagePath);
                    }
                }
            }

            $merch->save();
            return redirect()->route('artist.dashboard');
        }
    }
}
