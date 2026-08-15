<?php 
namespace App\Services;

use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Exception;

class HallService
{

    protected string $disk = 'public';
    public function createHall(array $data): Hall
    {
        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            $data['image'] = $this->storeImage($data['image']);
        }

        return Hall::create($data);
    }

    // 2. تابع تعديل قاعة
    public function updateHall(int $hallId, array $data): Hall
    {
        $hall = Hall::findOrFail($hallId);

        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        if (isset($data['image']) && $data['image'] instanceof \Illuminate\Http\UploadedFile) {
            if ($hall->image) {
                Storage::disk($this->disk)->delete('halls/' . $hall->image);
            }

            $data['image'] = $this->storeImage($data['image']);
        }

        $hall->update($data);
        return $hall;
    }

    // تابع مساعد موحّد لرفع الصورة
    protected function storeImage(\Illuminate\Http\UploadedFile $image): string
    {
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        $image->storeAs('halls', $imageName, $this->disk);
        return $imageName;
    }

    public function getAllHalls(): Collection
    {
        $locale = request()->header('Accept-Language', 'ar');
        $halls = Hall::select(['hall_id', 'name_en', 'name_ar', 'image', 'details_en', 'details_ar', 'price', 'capacity'])->get();

        return $halls->map(function ($hall) use ($locale) {
            return [
                'hall_id'  => $hall->hall_id,
                'image'    => $this->buildImageUrl($hall->image),
                'name'     => $locale === 'en' ? $hall->name_en : $hall->name_ar,
                'details'  => $locale === 'en' ? $hall->details_en : $hall->details_ar,
                'price'    => $hall->price,
                'capacity' => $hall->capacity,
            ];
        });
    }

    // تابع مساعد لبناء رابط الصورة (يشتغل صح مع public أو s3)
    public function buildImageUrl(?string $imagePath): ?string
    {
        if (!$imagePath) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($imagePath, ['http://', 'https://'])) {
            return $imagePath;
        }

        return Storage::disk($this->disk)->url('halls/' . $imagePath);
    }

    public function createReservation(int $customerId, array $data): Reservation
    {
        $startTime = Carbon::parse($data['date'] . ' ' . $data['start_hour']);
        $endTime = (clone $startTime)->addHours((int) $data['duration_hours']);

        $exists = Reservation::where('hall_id', $data['hall_id'])
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                      ->where('end_time', '>', $startTime);
            })
            ->exists();

        if ($exists) {
            throw new Exception(__('messages.The hall is reserved'));
        }

        return Reservation::create([
            'customer_id' => $customerId,
            'hall_id'     => $data['hall_id'],
            'start_time'  => $startTime,
            'end_time'    => $endTime,
            'status'      => 'pending',
        ]);
    }

    public function getAllReservations(): Collection
    {
        return Reservation::get();
    }

    // عرض حجوزات قاعة معينة
    public function getAllReservation($hallId): Collection
    {
        $reservations = Reservation::with('customer')
            ->where('hall_id', $hallId)
            ->get();

        return $reservations->map(function ($reservation) {
            return [
                'resev_id'      => $reservation->resev_id,
                'customer_id'   => $reservation->customer_id,
                'customer_name' => $reservation->customer->name,
                'start_time'    => $reservation->start_time,
                'end_time'      => $reservation->end_time,
                'status'        => $reservation->status,
            ];
        });
    }

    // 3. احصائيات الشهرية لقاعة محددة
    public function countMonthlyReservationsByHall(int $hallId)
    {
        return Reservation::select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                DB::raw('count(*) as reservations_count')
            )
            ->where('hall_id', $hallId)
            ->whereIn('status', ['confirmed', 'pending'])
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->toArray();
    }

    public function cancelReservation(int $customerId, int $reservationId): bool
    {
        $reservation = Reservation::where('customer_id', $customerId)
                                  ->where('resev_id', $reservationId)
                                  ->first();

        if (!$reservation) {
            return false;
        }

        return $reservation->update([
            'status' => 'canceled'
        ]);
    }
}