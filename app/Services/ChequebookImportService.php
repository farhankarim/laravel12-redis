<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChequebookImportService
{
    private array $countryCache = [];

    private array $regionCache = [];

    private array $areaCache = [];

    private array $branchCache = [];

    private array $productCache = [];

    public function processChunk(array $requestIds, string $companyPrefix, ?int $createdBy = null): int
    {
        if ($requestIds === []) {
            return 0;
        }

        $companyId = DB::table('companies')
            ->where('prefix', $companyPrefix)
            ->value('company_id');

        if (! $companyId) {
            throw new RuntimeException("Company not found for prefix [{$companyPrefix}].");
        }

        $seriesId = DB::table('active_series')
            ->where('status', 'active')
            ->value('record_id');

        if (! $seriesId) {
            throw new RuntimeException('No active series found in active_series table.');
        }

        $rows = DB::table('cheque_book_requests')
            ->whereIn('record_id', $requestIds)
            ->where('status', 'pending')
            ->orderBy('record_id')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $ibans = $rows->pluck('iban')->filter()->unique()->values();
        $accountNos = $rows->pluck('account_no')->filter()->unique()->values();

        $userLookup = DB::table('cheque_book_user')
            ->whereIn('IBAN', $ibans)
            ->pluck('user_id', 'IBAN')
            ->toArray();

        $chequeBookCountLookup = DB::table('cheque_book')
            ->selectRaw('account_no, COUNT(*) as c')
            ->whereIn('account_no', $accountNos)
            ->groupBy('account_no')
            ->pluck('c', 'account_no')
            ->map(fn ($count) => (int) $count)
            ->toArray();

        $assignRows = [];
        $logRows = [];
        $processedRequestIds = [];

        foreach ($rows as $row) {
            DB::transaction(function () use (
                $row,
                $companyId,
                $seriesId,
                $createdBy,
                &$userLookup,
                &$chequeBookCountLookup,
                &$assignRows,
                &$logRows,
                &$processedRequestIds,
            ): void {
                $actorId = $createdBy ?? 1;
                $now = now();

                $countryId = $this->getOrCreateCountry($companyId, (string) ($row->branch_country ?? ''), $actorId);
                $regionId = $this->getOrCreateRegion($companyId, $countryId, (string) ($row->region ?? ''), $actorId);
                $areaId = $this->getOrCreateArea($companyId, $regionId, (string) ($row->branch_city ?? ''), $actorId);
                $branchId = $this->getOrCreateBranch(
                    companyId: $companyId,
                    countryId: $countryId,
                    regionId: $regionId,
                    areaId: $areaId,
                    branchCode: (string) ($row->branch_code ?? ''),
                    branchName: (string) ($row->branch_name ?? ''),
                    branchAddress: (string) ($row->branch_address ?? ''),
                    city: (string) ($row->branch_city ?? ''),
                    operationManagerPhone: $row->operation_manager_phone,
                    branchManagerPhone: $row->branch_manager_phone,
                    createdBy: $actorId,
                );

                $iban = (string) ($row->iban ?? '');
                if (isset($userLookup[$iban])) {
                    $userId = (int) $userLookup[$iban];
                } else {
                    $accountTitle = (string) ($row->account_title ?? '');
                    $firstName = trim(explode(' ', $accountTitle)[0] ?? 'Customer');
                    $email = 'cb-'.strtolower(substr(sha1($iban !== '' ? $iban : (string) $row->account_no), 0, 16)).'@example.test';

                    $userId = DB::table('cheque_book_user')->insertGetId([
                        'name' => $firstName !== '' ? $firstName : 'Customer',
                        'email' => $email,
                        'company_id' => $companyId,
                        'branch_id' => $branchId,
                        'account_no' => $row->account_no,
                        'account_title' => $accountTitle,
                        'customer_premium_flag' => $row->customer_premium_flag,
                        'IBAN' => $iban,
                        'created_on' => $now,
                        'created_by' => $actorId,
                    ], 'user_id');

                    $userLookup[$iban] = $userId;
                }

                $accountNo = (string) $row->account_no;
                $nextSerial = ($chequeBookCountLookup[$accountNo] ?? 0) + 1;
                $productId = $this->resolveProductId($companyId, (string) ($row->product ?? ''), $actorId);

                $bookNo = $this->buildBookNo($accountNo, $nextSerial);
                while (DB::table('cheque_book')->where('book_no', $bookNo)->exists()) {
                    $nextSerial++;
                    $bookNo = $this->buildBookNo($accountNo, $nextSerial);
                }

                $chequeBookId = DB::table('cheque_book')->insertGetId([
                    'user_id' => $userId,
                    'account_no' => $accountNo,
                    'leaves_count' => $row->no_of_leaves,
                    'status' => 'pending',
                    'book_no' => $bookNo,
                    'currency_code' => $row->currency_code,
                    'product' => $productId,
                    'cheque_value_param' => $row->cheque_value_param,
                    'created_on' => $now,
                    'created_by' => $actorId,
                ], 'record_id');

                $chequeBookCountLookup[$accountNo] = $nextSerial;

                $leavesCount = max((int) $row->no_of_leaves, 0);
                [$seriesStart, $seriesEnd] = $this->reserveSeriesRange($seriesId, $leavesCount);

                if ($leavesCount > 0) {
                    $this->insertLeaves($chequeBookId, $accountNo, $seriesStart, $seriesEnd, $actorId, $now);
                }

                $assignRows[] = [
                    'chequebook_id' => $chequeBookId,
                    'series_id' => $seriesId,
                    'series_start' => $seriesStart,
                    'series_end' => $seriesEnd,
                    'created_at' => $now,
                    'created_by' => $actorId,
                ];

                $logRows[] = [
                    'chequebook_id' => $chequeBookId,
                    'message' => 'chequebook created & leaves assigned',
                    'status' => 'pending',
                    'created_At' => $now,
                    'created_by' => $actorId,
                ];

                $processedRequestIds[] = $row->record_id;
            }, 3);
        }

        if ($assignRows !== []) {
            DB::table('chequebook_series_assign')->insert($assignRows);
        }

        if ($logRows !== []) {
            DB::table('chequebook_logs')->insert($logRows);
        }

        if ($processedRequestIds !== []) {
            DB::table('cheque_book_requests')
                ->whereIn('record_id', $processedRequestIds)
                ->update(['status' => 'processed']);
        }

        return count($processedRequestIds);
    }

    private function reserveSeriesRange(int $seriesId, int $leavesCount): array
    {
        return DB::transaction(function () use ($seriesId, $leavesCount): array {
            $series = DB::table('active_series')
                ->where('record_id', $seriesId)
                ->lockForUpdate()
                ->first();

            if (! $series) {
                throw new RuntimeException('Active series row no longer exists.');
            }

            $currentUsed = (int) $series->series_used;
            $start = $currentUsed + 1;
            $end = $leavesCount > 0 ? ($start + $leavesCount - 1) : $currentUsed;

            if ($leavesCount > 0) {
                DB::table('active_series')
                    ->where('record_id', $seriesId)
                    ->update(['series_used' => $end]);
            }

            return [$start, $end];
        }, 3);
    }

    private function insertLeaves(int $chequeBookId, string $accountNo, int $start, int $end, int $createdBy, $now): void
    {
        $todayStr = now()->format('Ymd');
        $timeStr = now()->format('His');
        $custStr = preg_replace('/\D/', '', $accountNo);
        $aesKey = (string) config('chequebook.aes_key', config('app.key'));

        $rows = [];

        for ($series = $start; $series <= $end; $series++) {
            $serial = sprintf('%08d', $series);
            $qrCode = $todayStr.$serial.$custStr.$timeStr;

            $rows[] = [
                'cheque_book_id' => $chequeBookId,
                'serial_no' => $serial,
                'qr_code' => $qrCode,
                'qr_code_encrypted' => $this->aes128Encrypt($qrCode, $aesKey),
                'created_on' => $now,
                'created_by' => $createdBy,
            ];
        }

        DB::table('leaves_details')->insert($rows);
    }

    private function buildBookNo(string $accountNo, int $serial): string
    {
        return $accountNo.str_pad((string) $serial, 3, '0', STR_PAD_LEFT);
    }

    private function aes128Encrypt(string $plaintext, string $key): string
    {
        $cipher = 'aes-128-cbc';
        $ivLength = openssl_cipher_iv_length($cipher);
        $paddedPlaintext = str_pad(substr($plaintext, 0, 64), 64, "\0");
        $iv = random_bytes($ivLength);

        $encrypted = openssl_encrypt($paddedPlaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            throw new RuntimeException('Unable to encrypt QR payload.');
        }

        return base64_encode($iv.$encrypted);
    }

    private function getOrCreateCountry(int $companyId, string $countryName, int $createdBy): int
    {
        $key = $companyId.'|'.$countryName;
        if (isset($this->countryCache[$key])) {
            return $this->countryCache[$key];
        }

        $existingId = DB::table('company_countries')
            ->where('company_id', $companyId)
            ->where('country_name', $countryName)
            ->value('record_id');

        if ($existingId) {
            return $this->countryCache[$key] = (int) $existingId;
        }

        $id = DB::table('company_countries')->insertGetId([
            'company_id' => $companyId,
            'country_name' => $countryName,
            'status' => 'active',
            'created_on' => now(),
            'created_by' => $createdBy,
        ], 'record_id');

        return $this->countryCache[$key] = $id;
    }

    private function getOrCreateRegion(int $companyId, int $countryId, string $regionName, int $createdBy): int
    {
        $key = $companyId.'|'.$countryId.'|'.$regionName;
        if (isset($this->regionCache[$key])) {
            return $this->regionCache[$key];
        }

        $existingId = DB::table('company_regions')
            ->where('company_id', $companyId)
            ->where('country_id', $countryId)
            ->where('region_name', $regionName)
            ->value('record_id');

        if ($existingId) {
            return $this->regionCache[$key] = (int) $existingId;
        }

        $id = DB::table('company_regions')->insertGetId([
            'company_id' => $companyId,
            'country_id' => $countryId,
            'region_name' => $regionName,
            'status' => 'active',
            'created_on' => now(),
            'created_by' => $createdBy,
        ], 'record_id');

        return $this->regionCache[$key] = $id;
    }

    private function getOrCreateArea(int $companyId, int $regionId, string $areaName, int $createdBy): int
    {
        $key = $companyId.'|'.$regionId.'|'.$areaName;
        if (isset($this->areaCache[$key])) {
            return $this->areaCache[$key];
        }

        $existingId = DB::table('company_areas')
            ->where('company_id', $companyId)
            ->where('region_id', $regionId)
            ->where('area_name', $areaName)
            ->value('record_id');

        if ($existingId) {
            return $this->areaCache[$key] = (int) $existingId;
        }

        $id = DB::table('company_areas')->insertGetId([
            'company_id' => $companyId,
            'region_id' => $regionId,
            'area_name' => $areaName,
            'status' => 'active',
            'created_on' => now(),
            'created_by' => $createdBy,
        ], 'record_id');

        return $this->areaCache[$key] = $id;
    }

    private function getOrCreateBranch(
        int $companyId,
        int $countryId,
        int $regionId,
        int $areaId,
        string $branchCode,
        string $branchName,
        string $branchAddress,
        string $city,
        ?string $operationManagerPhone,
        ?string $branchManagerPhone,
        int $createdBy,
    ): int {
        $key = $companyId.'|'.$branchCode;
        if (isset($this->branchCache[$key])) {
            return $this->branchCache[$key];
        }

        $existingId = DB::table('company_branches')
            ->where('company_id', $companyId)
            ->where('branch_code', $branchCode)
            ->value('record_id');

        if ($existingId) {
            return $this->branchCache[$key] = (int) $existingId;
        }

        $id = DB::table('company_branches')->insertGetId([
            'company_id' => $companyId,
            'country_id' => $countryId,
            'region_id' => $regionId,
            'area_id' => $areaId,
            'branch_code' => $branchCode,
            'branch_name' => $branchName,
            'address' => $branchAddress,
            'city' => $city,
            'status' => 'active',
            'operation_manager_phone' => $operationManagerPhone,
            'branch_manager_phone' => $branchManagerPhone,
            'created_on' => now(),
            'created_by' => $createdBy,
        ], 'record_id');

        return $this->branchCache[$key] = $id;
    }

    private function resolveProductId(int $companyId, string $productValue, int $createdBy): int
    {
        $normalized = trim($productValue);

        if ($normalized !== '' && ctype_digit($normalized)) {
            return (int) $normalized;
        }

        $key = $companyId.'|'.$normalized;
        if (isset($this->productCache[$key])) {
            return $this->productCache[$key];
        }

        if ($normalized !== '') {
            $existingId = DB::table('company_products')
                ->where('company_id', $companyId)
                ->where(function ($query) use ($normalized): void {
                    $query->where('product_code', $normalized)
                        ->orWhere('product_name', $normalized);
                })
                ->value('record_id');

            if ($existingId) {
                return $this->productCache[$key] = (int) $existingId;
            }
        }

        $code = $normalized !== '' ? $normalized : 'UNKNOWN';
        $name = $normalized !== '' ? $normalized : 'Unknown Product';

        $createdId = DB::table('company_products')->insertGetId([
            'company_id' => $companyId,
            'product_name' => $name,
            'product_code' => $code,
            'category_name' => 'Imported',
            'status' => 'active',
            'created_by' => $createdBy,
            'created_at' => now(),
            'updated_at' => null,
        ], 'record_id');

        return $this->productCache[$key] = (int) $createdId;
    }
}
