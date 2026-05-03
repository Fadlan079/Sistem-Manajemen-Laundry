<?php

namespace App\Imports;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Service;
use App\Models\Payment;
use App\Models\Delivery;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;

class OrdersImport implements ToModel, WithHeadingRow
{
    private $rowsImported = 0;
    private $errors = [];

    public function model(array $row)
    {
        $email   = trim($row['email'] ?? '');
        $layanan = trim($row['layanan'] ?? '');
        
        if (empty($email) || empty($layanan)) {
            $this->errors[] = "Baris dengan data kosong ditemukan.";
            return null;
        }

        $user = User::where('email', $email)->first();
        $service = Service::where('name', $layanan)->first();

        if (!$user) {
            $this->errors[] = "Email [{$email}] tidak terdaftar di sistem.";
            return null;
        }

        if (!$service) {
            $this->errors[] = "Layanan [{$layanan}] tidak ditemukan.";
            return null;
        }

        return DB::transaction(function () use ($row, $user, $service) {
            $isKg = in_array(strtolower($service->unit ?? ''), ['/kg', 'kg']);
            $qty = $row['jumlah_atau_berat'] ?? 1;
            $tipe = strtolower($row['tipe_pengiriman'] ?? 'outlet');
            
            $deliveryFee = match ($tipe) {
                'antar_jemput' => 10000,
                'jemput', 'antar' => 5000,
                default => 0,
            };

            $totalPrice = $deliveryFee + ($qty * $service->price);

            $order = Order::create([
                'user_id' => $user->id,
                'service_id' => $service->id,
                'status' => 'antri',
                'total_price' => $totalPrice,
                'pickup_address' => $row['alamat'] ?? '-',
                'delivery_address' => $row['alamat'] ?? '-',
                'notes' => $row['catatan'] ?? null,
                'operator_id' => auth()->id(),
            ]);

            OrderItem::create([
                'order_id' => $order->id,
                'service_id' => $service->id,
                'item_name' => $service->name,
                'qty' => $qty,
                'actual_weight' => $isKg ? $qty : null,
                'price' => $service->price,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'amount' => 0,
                'method' => 'cash',
                'status' => 'menunggu',
            ]);

            if (in_array($tipe, ['antar_jemput', 'jemput'])) {
                Delivery::create([
                    'order_id' => $order->id,
                    'status' => 'dijemput',
                    'type' => 'pickup',
                    'scheduled_at' => Carbon::now(),
                ]);
            }

            if (in_array($tipe, ['antar_jemput', 'antar'])) {
                Delivery::create([
                    'order_id' => $order->id,
                    'status' => 'diantar',
                    'type' => 'delivery',
                ]);
            }

            $this->rowsImported++;
            return $order;
        });
    }

    public function getImportReport()
    {
        return [
            'success_count' => $this->rowsImported,
            'errors' => $this->errors,
        ];
    }
}
