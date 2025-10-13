<?php

namespace App\Filament\Admin\Resources\GaransiResource\Api\Transformers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use App\Models\Product;
use Laravolt\Indonesia\Models\Provinsi;
use Laravolt\Indonesia\Models\Kabupaten;
use Laravolt\Indonesia\Models\Kecamatan;
use Laravolt\Indonesia\Models\Kelurahan;
use App\Models\PostalCode;

class GaransiTransformer extends JsonResource
{
    public function toArray($request): array
    {
        $this->resource->loadMissing([
            'department:id,name',
            'employee:id,name',
            'customer:id,name',
            'customerCategory:id,name',
        ]);

        $statusLabel = match ($this->status) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending'  => 'Pending',
            default    => ucfirst((string)$this->status),
        };

        $alamatReadable   = $this->mapAddressesReadable($this->address);
        $productsReadable = $this->mapProductsReadable($this->products);

        return [
            'no_garansi'        => $this->no_garansi,
            'department'        => $this->department?->name ?? '-',
            'employee'          => $this->employee?->name ?? '-',
            'customer'          => $this->customer?->name ?? '-',
            'customer_category' => $this->customerCategory?->name ?? '-',
            'phone'             => $this->phone,
            'address_text'      => $this->addressText($alamatReadable),
            'address_detail'    => $alamatReadable,
            'purchase_date'     => optional($this->purchase_date)->format('d/m/Y'),
            'claim_date'        => optional($this->claim_date)->format('d/m/Y'),
            'reason'            => $this->reason,
            'note'              => $this->note ?: null,
            'image'             => $this->image ? Storage::url($this->image) : null,
            'products'          => $productsReadable,
            'status'            => $statusLabel,
            'file_pdf_url'      => $this->garansi_file  ? Storage::url($this->garansi_file)  : null,
            'created_at'        => optional($this->created_at)->format('d/m/Y'),
            'updated_at'        => optional($this->updated_at)->format('d/m/Y'),
        ];
    }

    /* ---------- Helpers ---------- */

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

            return [
                'brand'    => $product?->brand?->name ?? null,
                'category' => $product?->category?->name ?? null,
                'product'  => $product?->name ?? null,
                'color'    => $p['warna_id'] ?? null,
                'quantity' => (int)($p['quantity'] ?? 0),
            ];
        }, $items);
    }
}
