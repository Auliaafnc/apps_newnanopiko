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

        // ===== Address handling: string atau array =====
        [$addressText, $addressDetail] = $this->normalizeAddress($this->address);

        // ===== Images (multi) =====
        $images          = $this->toPublicUrls($this->image);
        $deliveryImages  = $this->toPublicUrls($this->delivery_images);

        // ===== Products (batch to avoid N+1) =====
        $productsReadable = $this->mapProductsReadableBatch($this->products);

        return [
            // Identitas
            'no_garansi'        => $this->no_garansi,
            'department'        => $this->department?->name ?? '-',
            'employee'          => $this->employee?->name ?? '-',
            'customer'          => $this->customer?->name ?? '-',
            'customer_category' => $this->customerCategory?->name ?? '-',

            // Kontak & alamat
            'phone'             => (string) $this->phone,
            'address_text'      => $addressText,     // string siap tampil
            'address_detail'    => $addressDetail,   // array berisi kode+nama (kalau ada)

            // Tanggal
            'purchase_date'     => optional($this->purchase_date)->format('d/m/Y'),
            'claim_date'        => optional($this->claim_date)->format('d/m/Y'),

            // Konten
            'reason'            => $this->reason,
            'note'              => $this->note ?: null,
            'images'            => $images,                      // array URL
            'delivery_images'   => $deliveryImages,              // array URL
            'products'          => $productsReadable,

            // Status (raw + label)
            'status_pengajuan'        => $this->status_pengajuan,
            'status_pengajuan_label'  => $this->labelPengajuan($this->status_pengajuan),
            'status_product'          => $this->status_product,
            'status_product_label'    => $this->labelProduct($this->status_product),
            'status_garansi'          => $this->status_garansi,
            'status_garansi_label'    => $this->labelGaransi($this->status_garansi),

            // File export
            'file_pdf_url'      => $this->garansi_file  ? Storage::url($this->garansi_file)  : null,
            'file_excel_url'    => $this->garansi_excel ? Storage::url($this->garansi_excel) : null,

            // Audit
            'delivered_at'      => optional($this->delivered_at)->format('d/m/Y H:i'),
            'delivered_by'      => $this->delivered_by,
            'created_at'        => optional($this->created_at)->format('d/m/Y H:i'),
            'updated_at'        => optional($this->updated_at)->format('d/m/Y H:i'),
        ];
    }

    /* ===================== Helpers ===================== */

    /** Address boleh string (sudah diformat) atau array berisi kode wilayah. */
    private function normalizeAddress($raw): array
    {
        // Jika sudah string (hasil format di handler), kembalikan langsung
        if (is_string($raw) && trim($raw) !== '') {
            return [$raw, []];
        }

        // Jika JSON string → decode
        $arr = is_array($raw) ? $raw : json_decode($raw ?? '[]', true);
        if (!is_array($arr) || empty($arr)) {
            return [null, []];
        }

        $detail = $this->mapAddressesReadable($arr);
        return [$this->addressText($detail), $detail];
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

    private function mapAddressesReadable(array $items): array
    {
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

    /** Ubah path (string/array) menjadi array URL publik */
    private function toPublicUrls($paths): array
    {
        // DB bisa simpan string (single) atau array (multiple)
        if (is_string($paths) && $paths !== '') {
            return [Storage::url($paths)];
        }
        $list = is_array($paths) ? $paths : (array) json_decode($paths ?? '[]', true);
        return collect($list)
            ->filter(fn ($p) => is_string($p) && $p !== '')
            ->map(fn ($p) => Storage::url($p))
            ->values()
            ->all();
    }

    /** Versi batch: hindari N+1 untuk map products */
    private function mapProductsReadableBatch($products): array
    {
        $items = is_array($products) ? $products : json_decode($products ?? '[]', true);
        if (!is_array($items) || empty($items)) return [];

        $ids = collect($items)
            ->map(fn ($p) => $p['produk_id'] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();

        $byId = Product::with(['brand:id,name','category:id,name'])
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return array_map(function ($p) use ($byId) {
            $prodId  = $p['produk_id'] ?? null;
            $product = $prodId ? $byId->get((int)$prodId) : null;

            return [
                'brand'    => $product?->brand?->name ?? null,
                'category' => $product?->category?->name ?? null,
                'product'  => $product?->name ?? null,
                'color'    => $p['warna_id'] ?? null,
                'quantity' => (int) ($p['quantity'] ?? 0),
            ];
        }, $items);
    }

    // ===== Label helpers untuk 3 status =====
    private function labelPengajuan(?string $state): string
    {
        return match ($state) {
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'pending', null, '' => 'Pending',
            default => ucfirst($state),
        };
    }

    private function labelProduct(?string $state): string
    {
        return match ($state) {
            'ready_stock' => 'Ready Stock',
            'sold_out'    => 'Sold Out',
            'rejected'    => 'Ditolak',
            'pending', null, '' => 'Pending',
            default => ucfirst($state),
        };
    }

    private function labelGaransi(?string $state): string
    {
        return match ($state) {
            'confirmed'  => 'Confirmed',
            'processing' => 'Processing',
            'on_hold'    => 'On Hold',
            'delivered'  => 'Delivered',
            'completed'  => 'Completed',
            'cancelled'  => 'Cancelled',
            'rejected'   => 'Ditolak',
            'pending', null, '' => 'Pending',
            default => ucfirst($state),
        };
    }
}
