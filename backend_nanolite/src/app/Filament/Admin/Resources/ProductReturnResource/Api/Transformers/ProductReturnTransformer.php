<?php

namespace App\Filament\Admin\Resources\ProductReturnResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use Laravolt\Indonesia\Models\Provinsi;
use Laravolt\Indonesia\Models\Kabupaten;
use Laravolt\Indonesia\Models\Kecamatan;
use Laravolt\Indonesia\Models\Kelurahan;
use App\Models\PostalCode;

class ProductReturnTransformer extends JsonResource
{
    public function toArray($request): array
    {
        // samakan relasi -> 'category'
        $this->resource->loadMissing([
            'department:id,name',
            'employee:id,name',
            'customer:id,name',
            'category:id,name',
        ]);

        $statusLabel = match ($this->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending'  => 'Pending',
            default    => ucfirst((string) $this->status),
        };

        $alamatReadable   = $this->mapAddressesReadable($this->address);
        $productsReadable = $this->mapProductsReadable($this->products);

        // ---- bersihkan path image agar tidak ada %22 / kutip / array json ----
        $imgPath = $this->cleanPath($this->image);

        return [
            'no_return'          => $this->no_return,
            'department'         => $this->department?->name ?? '-',
            'employee'           => $this->employee?->name ?? '-',
            'customer'           => $this->customer?->name ?? '-',
            'customer_category'  => $this->category?->name ?? '-',
            'phone'              => $this->phone,
            'address_text'       => $this->addressText($alamatReadable),
            'address_detail'     => $alamatReadable,
            'amount'             => (int)($this->amount ?? 0),
            'reason'             => $this->reason,
            'note'               => $this->note ?: null,

            // URL gambar publik (null jika kosong)
            'image' => $this->cleanPath($this->image) 
                ? Storage::url($this->cleanPath($this->image)) 
                : null,

            'products'           => $productsReadable,
            'status'             => $statusLabel,

            // file unduhan
            'file_pdf_url'       => $this->return_file ? Storage::url($this->return_file) : null,

            'created_at'         => optional($this->created_at)->format('d/m/Y'),
            'updated_at'         => optional($this->updated_at)->format('d/m/Y'),
        ];
    }

    /* ---------------- Helpers ---------------- */

    /**
     * Normalisasi nilai path gambar yang mungkin:
     * - string dengan kutip:  "product-returns/xxx.jpg"
     * - string JSON array:    ["product-returns/xxx.jpg"]
     * - array PHP:            ['product-returns/xxx.jpg', ...]
     */
    private function cleanPath($raw): ?string
    {
        if (empty($raw)) return null;

        // jika string JSON array
        if (is_string($raw) && str_starts_with(trim($raw), '[')) {
            $arr = json_decode($raw, true);
            $raw = is_array($arr) ? ($arr[0] ?? null) : $raw;
        }

        // jika array PHP
        if (is_array($raw)) {
            $raw = $raw[0] ?? null;
        }

        if (is_string($raw)) {
            // buang kutip berlebih
            $raw = trim($raw, " \t\n\r\0\x0B\"'");
        }

        return $raw ?: null;
    }

    private function addressText(array $items): ?string
    {
        if (empty($items)) return null;
        return collect($items)->map(function ($a) {
            $parts = [
                $a['detail_alamat'] ?? null,
                $a['kelurahan']['name'] ?? null,
                $a['kecamatan']['name'] ?? null,
                $a['kota_kab']['name'] ?? null,
                $a['provinsi']['name'] ?? null,
                $a['kode_pos'] ?? null,
            ];
            return implode(', ', array_filter($parts));
        })->join(' | ');
    }

    private function mapAddressesReadable($address): array
    {
        $items = is_array($address) ? $address : json_decode($address ?? '[]', true);
        if (!is_array($items)) $items = [];

        return array_map(function ($a) {
            $provCode = $a['provinsi']  ?? null;
            $kabCode  = $a['kota_kab']  ?? null;
            $kecCode  = $a['kecamatan'] ?? null;
            $kelCode  = $a['kelurahan'] ?? null;

            return [
                'detail_alamat' => $a['detail_alamat'] ?? null,
                'provinsi'      => ['code' => $provCode, 'name' => $this->nameFromCode(Provinsi::class,  $provCode)],
                'kota_kab'      => ['code' => $kabCode,  'name' => $this->nameFromCode(Kabupaten::class, $kabCode)],
                'kecamatan'     => ['code' => $kecCode,  'name' => $this->nameFromCode(Kecamatan::class, $kecCode)],
                'kelurahan'     => ['code' => $kelCode,  'name' => $this->nameFromCode(Kelurahan::class, $kelCode)],
                'kode_pos'      => $a['kode_pos'] ?? $this->postalByVillage($kelCode),
            ];
        }, $items);
    }

    private function nameFromCode(string $model, ?string $code): ?string
    {
        if (!$code) return null;
        return optional($model::where('code', $code)->first())->name;
    }

    private function postalByVillage(?string $villageCode): ?string
    {
        if (!$villageCode) return null;
        return optional(PostalCode::where('village_code', $villageCode)->first())->postal_code;
    }

    private function mapProductsReadable($products): array
    {
        $items = is_array($products) ? $products : json_decode($products ?? '[]', true);
        if (!is_array($items)) $items = [];

        return array_map(function ($p) {
            $product = isset($p['produk_id'])
                ? Product::with(['brand:id,name', 'category:id,name'])->find($p['produk_id'])
                : null;

            // Ambil warna dari JSON product->colors (bisa array of string/obj)
            $colorName = null;
            if ($product && !empty($p['warna_id'])) {
                $colors = collect($product->colors ?? []);
                // case: [{id,name}] atau [{value,label}] atau ["3000K","4000K"]
                $colorObj = $colors->first(function ($c) use ($p) {
                    if (is_array($c)) {
                        return ($c['id'] ?? null) == $p['warna_id']
                            || ($c['value'] ?? null) == $p['warna_id']
                            || ($c['name'] ?? $c['label'] ?? null) == $p['warna_id'];
                    }
                    return (string) $c === (string) $p['warna_id'];
                });
                if (is_array($colorObj)) {
                    $colorName = $colorObj['name'] ?? $colorObj['label'] ?? $colorObj['value'] ?? $p['warna_id'];
                } elseif (!is_null($colorObj)) {
                    $colorName = (string) $colorObj;
                } else {
                    $colorName = $p['warna_id']; // fallback
                }
            }

            return [
                'brand'    => $product?->brand?->name ?? null,
                'category' => $product?->category?->name ?? null,
                'product'  => $product?->name ?? null,
                'color'    => $colorName,
                'quantity' => (int)($p['quantity'] ?? 0),
            ];
        }, $items);
    }
}