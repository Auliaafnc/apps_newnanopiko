<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProductReturnExport;
use App\Models\Concerns\OwnedByEmployee;
use App\Models\Concerns\LatestFirst;

class ProductReturn extends Model
{
    use OwnedByEmployee, LatestFirst;

    protected $fillable = [
        'no_return',
        'company_id',
        'customer_categories_id',
        'customer_id',
        'employee_id',
        'department_id',
        'reason',
        'amount',
        'image',
        'phone',
        'note',
        'address',
        'products',
        'status_pengajuan',
        'status_product',
        'status_return',
        // komentar & by siapa
        'rejection_comment',
        'rejected_by',
        'sold_out_comment',   
        'sold_out_by', 
        'on_hold_comment',
        'on_hold_until',
        'on_hold_by',
        'cancelled_comment',
        'cancelled_by',

        // bukti delivered
        'delivery_images',
        'delivered_at',
        'delivered_by',
        'return_file',
        'return_excel',
    ];

    protected $casts = [
        'company_id'             => 'integer',
        'customer_id'            => 'integer',
        'employee_id'            => 'integer',
        'department_id'          => 'integer',
        'customer_categories_id' => 'integer',
        'products'               => 'array',
        'address'                => 'array',
        'amount'                 => 'decimal:2',
        'created_at'             => 'datetime',
        'updated_at'             => 'datetime',
        'delivery_images'        => 'array',
        'image'                  => 'array',
        'delivered_at'           => 'datetime',
        'on_hold_until'          => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function (ProductReturn $return) {
            $return->no_return = 'RET-' . now()->format('Ymd') . strtoupper(Str::random(4));
            self::normalizeProductColors($return);
        });

        static::saving(function (ProductReturn $return) {
            self::consumeImageArray($return, 'image', 'return-photos');
            self::consumeImageArray($return, 'delivery_images', 'return-delivery-photos');
            self::normalizeProductColors($return);
        });

        static::saved(function (ProductReturn $return) {
            // generate PDF
            $html = view('invoices.product-return', compact('return'))->render();
            $pdf = Pdf::loadHtml($html)->setPaper('a4', 'portrait');

            $pdfFileName = "Return-{$return->no_return}.pdf";
            Storage::disk('public')->put($pdfFileName, $pdf->output());
            $return->updateQuietly(['return_file' => $pdfFileName]);

            // generate Excel
            $excelFileName = "Return-{$return->no_return}.xlsx";
            Excel::store(new ProductReturnExport($return), $excelFileName, 'public');
            $return->updateQuietly(['return_excel' => $excelFileName]);
        });

        static::saving(function (ProductReturn $return) {
            if ($return->status_pengajuan === 'rejected') {
                $return->status_product = 'rejected';
                $return->status_return = 'rejected';
            }
        });
    }

    /**
     * Konversi warna_id angka -> label warna dari $product->colors
     */
    protected static function normalizeProductColors(ProductReturn $return): void
    {
        $items = $return->products;

        if (is_string($items)) {
            $items = json_decode($items, true) ?: [];
        }
        if (!is_array($items)) {
            $items = [];
        }

        foreach ($items as &$it) {
            $pid = $it['produk_id'] ?? null;
            if (!$pid) continue;

            $product = Product::find($pid);
            if (!$product) continue;

            if (array_key_exists('warna_id', $it) && is_numeric($it['warna_id'])) {
                $idx = (int) $it['warna_id'];
                $colors = $product->colors ?? [];
                if (isset($colors[$idx])) {
                    $it['warna_id'] = $colors[$idx]; // simpan label (contoh "3000K")
                }
            }
        }

        $return->products = $items;
    }

    // ================= IMAGE HELPERS =================
    protected static function consumeImageArray(ProductReturn $return, string $field, string $folder): void
    {
        $imgs = $return->$field ?? [];
        if (is_string($imgs)) {
            $imgs = json_decode($imgs, true) ?: [];
        }
        if (!is_array($imgs)) return;

        $saved = [];
        foreach ($imgs as $img) {
            if (is_string($img) && preg_match('/^data:image\/([a-zA-Z0-9.+-]+);base64,/', $img, $m)) {
                $ext  = strtolower($m[1] ?? 'png');
                $data = substr($img, strpos($img, ',') + 1);
                $bin  = base64_decode($data, true);
                if ($bin === false) continue;

                $name = $folder . '/' . now()->format('Ymd_His') . '_' . Str::random(8) . '.' . $ext;
                Storage::disk('public')->put($name, $bin);
                $saved[] = $name;
            } elseif (is_string($img)) {
                $saved[] = $img;
            }
        }

        $return->$field = $saved;
    }

    // ================= RELASI =================
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class, 'customer_id'); }
    public function department(): BelongsTo { return $this->belongsTo(Department::class, 'department_id'); }
    public function employee(): BelongsTo { return $this->belongsTo(Employee::class, 'employee_id'); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class, 'company_id'); }
    public function category(): BelongsTo { return $this->belongsTo(CustomerCategories::class, 'customer_categories_id'); }
    public function deliveredBy(): BelongsTo { return $this->belongsTo(Employee::class, 'delivered_by'); }

    public function rejectedBy(): BelongsTo { return $this->belongsTo(Employee::class, 'rejected_by'); }
    public function onHoldBy(): BelongsTo { return $this->belongsTo(Employee::class, 'on_hold_by'); }
    public function cancelledBy(): BelongsTo { return $this->belongsTo(Employee::class, 'cancelled_by'); }

    // ================= PRODUK =================
    public function productsWithDetails(): array
    {
        $raw = $this->products;
        if (is_string($raw)) $raw = json_decode($raw, true) ?: [];
        elseif (!is_array($raw)) $raw = [];

        return array_map(function ($item) {
            $product = Product::find($item['produk_id'] ?? null);
            return [
                'brand_name'    => $product?->brand?->name ?? '(Brand hilang)',
                'category_name' => $product?->category?->name ?? '(Kategori hilang)',
                'product_name'  => $product?->name ?? '(Produk hilang)',
                'color'         => $item['warna_id'] ?? '-',
                'quantity'      => $item['quantity'] ?? 0,
            ];
        }, $raw);
    }

    public function getProductsDetailsAttribute(): string
    {
        $items = $this->productsWithDetails();
        if (empty($items)) return '';
        return collect($items)->map(fn ($i) =>
            "{$i['brand_name']} – {$i['category_name']} – {$i['product_name']} – {$i['color']} – Qty: {$i['quantity']}"
        )->implode('<br>');
    }

    // ================= ALAMAT =================
    public function getAddressTextAttribute(): string
    {
        if (is_array($this->address) && count($this->address) > 0) {
            $addr = $this->address[0];

            $parts = [
                $addr['detail_alamat'] ?? '',
                $addr['kelurahan'] ?? '',
                $addr['kecamatan'] ?? '',
                $addr['kota_kab'] ?? '',
                $addr['provinsi'] ?? '',
                $addr['kode_pos'] ?? '',
            ];

            $cleaned = array_filter($parts, function ($v) {
                $v = trim((string) $v);
                return $v !== '' && $v !== '-';
            });

            return implode(', ', $cleaned);
        }

        return '-';
    }
}
