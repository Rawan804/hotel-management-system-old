<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except([
            'getByDepartment'
        ]);
    }


    private function transformService($service)
    {
        return [
            'id' => $service->ser_id,

            'dep_id' => $service->dep_id,

            'name' => app()->getLocale() === 'ar'
                ? $service->name_ar
                : $service->name_en,

            'description' => app()->getLocale() === 'ar'
                ? $service->description_ar
                : $service->description_en,

            'weight' => $service->weight,

            'is_active' => (bool) $service->is_active,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | عرض الخدمات المفعلة لقسم محدد
    |--------------------------------------------------------------------------
    */
    public function getByDepartment($dep_id)
    {
        $services = Service::where('dep_id', $dep_id)
            ->where('is_active', true)
            ->get();


        return response()->json([
            'success' => true,

            'data' => $services->map(
                fn($service) => $this->transformService($service)
            )
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | عرض الخدمات حسب صلاحيات المستخدم
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $user = Auth::user();


        if ($user->role === 'employee') {

            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }



        if ($user->role === 'general_manager') {

            $services = Service::all();

        } else {

            $services = Service::where(
                'dep_id',
                $user->dep_id
            )->get();
        }



        return response()->json([
            'success' => true,

            'data' => $services->map(
                fn($service) => $this->transformService($service)
            )
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | إضافة خدمة جديدة
    |--------------------------------------------------------------------------
    */
    public function store(Request $request)
    {
        $user = Auth::user();


        if ($user->role === 'employee') {

            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }



        $request->validate([

            'dep_id' => 'required|exists:departments,dep_id',

            'name_ar' => 'required|string',

            'name_en' => 'required|string',

            'description_ar' => 'nullable|string',

            'description_en' => 'nullable|string',

            'weight' => 'nullable|integer|min:1',

        ]);



        if (
            $user->role !== 'general_manager'
            && $user->dep_id != $request->dep_id
        ) {

            return response()->json([
                'message' => 'You can only add services to your department'
            ], 403);
        }



        $service = Service::create([

            'dep_id' => $request->dep_id,

            'name_ar' => $request->name_ar,

            'name_en' => $request->name_en,

            'description_ar' => $request->description_ar,

            'description_en' => $request->description_en,

            'weight' => $request->weight ?? 1,

            'is_active' => true

        ]);



        return response()->json([

            'success' => true,

            'message' => 'Service created successfully',

            'service' => $this->transformService($service)

        ], 201);
    }



    /*
    |--------------------------------------------------------------------------
    | تفعيل / إلغاء تفعيل خدمة
    |--------------------------------------------------------------------------
    */
    public function toggleActive(Service $service)
    {
        $user = Auth::user();



        if ($user->role === 'employee') {

            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }



        if (
            $user->role !== 'general_manager'
            && $user->dep_id != $service->dep_id
        ) {

            return response()->json([
                'message' => 'Forbidden'
            ], 403);
        }



        $service->is_active = !$service->is_active;

        $service->save();



        return response()->json([

            'success' => true,

            'message' => 'Service status updated successfully',

            'service' => $this->transformService($service)

        ]);
    }
}