<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class MembersFromOldSeeder extends Seeder
{
    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('members')) {
            Log::warning('Old members table not found. Run ImportOldSqlSeeder first.');
            return;
        }

        $exists = DB::table('members')->count();
        if ($exists === 0) {
            Log::info('No rows in old members table to import.');
            return;
        }

        // Detect optional columns in users table
        $hasPhone = Schema::hasColumn('users', 'phone');
        $hasAddress = Schema::hasColumn('users', 'address');

        // Preload existing emails to avoid N queries and ensure uniqueness quickly (case-insensitive)
        // Map: email_lower => owner_membership_number (may be empty for non-member users)
        $existingEmails = [];
        foreach (DB::table('users')->select('email', 'membership_number')->get() as $u) {
            $em = $u->email;
            if ($em !== null) {
                $existingEmails[strtolower(trim((string)$em))] = (string)($u->membership_number ?? '');
            }
        }
        // Preload existing users indexed by membership_number to control insert vs update behavior
        $existingUsers = DB::table('users')->select('id', 'membership_number', 'email', 'phone', 'address', 'created_at')->get()
            ->keyBy(fn ($u) => (string)($u->membership_number ?? ''));
        // Precompute a single password hash (doing this per-row is very slow)
        $passwordHash = Hash::make('password123');

        DB::table('members')->orderBy('id')->chunk(1000, function ($rows) use (&$existingEmails, $existingUsers, $passwordHash, $hasPhone, $hasAddress) {
            foreach ($rows as $row) {
                $membership = $row->memberno ?: null;
                if (!$membership) {
                    // Skip if no membership number
                    continue;
                }

                $key = (string)$membership;
                $existing = $existingUsers->get($key);

                // Ensure email uniqueness (case-insensitive); fallback to generated if invalid or duplicate (not self)
                $rawEmail = trim((string)($row->emailaddress ?? ''));
                $emailLower = strtolower($rawEmail);
                $isValid = $rawEmail && filter_var($rawEmail, FILTER_VALIDATE_EMAIL);
                $selfEmailLower = $existing && !empty($existing->email) ? strtolower((string)$existing->email) : null;

                if (!$isValid) {
                    $email = $this->generatedEmailFromMember((int)$row->id, $key);
                    while (isset($existingEmails[strtolower($email)])) {
                        $email = $this->generatedEmailFromMember((int)$row->id + random_int(1, 999999), $key);
                    }
                } else {
                    // Valid email. If conflicts with another user (not self), generate fallback
                    if (isset($existingEmails[$emailLower]) && $emailLower !== $selfEmailLower) {
                        $email = $this->generatedEmailFromMember((int)$row->id, $key);
                        while (isset($existingEmails[strtolower($email)])) {
                            $email = $this->generatedEmailFromMember((int)$row->id + random_int(1, 999999), $key);
                        }
                    } else {
                        $email = $emailLower; // normalize to lowercase
                    }
                }

                $name = trim((string)($row->membername ?? 'Member ' . $row->id));
                if ($name === '') {
                    $name = 'Unknown Member';
                }

                // Optional fields from legacy
                $phone = trim((string)($row->phoneno ?? '')) ?: null;
                $address = trim((string)($row->address ?? '')) ?: null;
                $joinedAt = null;
                if (!empty($row->datejoined) && $row->datejoined !== '0000-00-00') {
                    try {
                        $joinedAt = Carbon::parse($row->datejoined)->startOfDay();
                    } catch (\Throwable $e) {
                        $joinedAt = null;
                    }
                }

                $key = (string)$membership;
                $existing = $existingUsers->get($key);

                if ($existing) {
                    // Prepare update set. Do not overwrite non-empty phone/address if already present.
                    $update = [
                        'name' => $name,
                        'email' => $email,
                        // keep password as known default for legacy imports
                        'password' => $passwordHash,
                    ];
                    if ($hasPhone && (empty($existing->phone) && $phone)) {
                        $update['phone'] = $phone;
                    }
                    if ($hasAddress && (empty($existing->address) && $address)) {
                        $update['address'] = $address;
                    }

                    if (!empty($update)) {
                        DB::table('users')->where('membership_number', $key)->update($update);
                        // Reflect updates in memory maps for this run
                        // Drop old email mapping (if owned by this member)
                        $oldLower = $selfEmailLower;
                        if ($oldLower && (($existingEmails[$oldLower] ?? null) === $key)) {
                            unset($existingEmails[$oldLower]);
                        }
                        // Update cached user
                        $existing->email = $email;
                        if (array_key_exists('phone', $update)) {
                            $existing->phone = $update['phone'];
                        }
                        if (array_key_exists('address', $update)) {
                            $existing->address = $update['address'];
                        }
                        $existingUsers->put($key, $existing);
                    }
                } else {
                    // Insert new user with legacy fields, preserving join date as created_at when available
                    $insert = [
                        'membership_number' => $key,
                        'name' => $name,
                        'email' => $email,
                        'password' => $passwordHash,
                    ];
                    if ($hasPhone) $insert['phone'] = $phone;
                    if ($hasAddress) $insert['address'] = $address;
                    if ($joinedAt) {
                        $insert['created_at'] = $joinedAt;
                        $insert['updated_at'] = $joinedAt;
                    } else {
                        // Ensure timestamps exist for non-nullable columns
                        $now = now();
                        $insert['created_at'] = $now;
                        $insert['updated_at'] = $now;
                    }
                    DB::table('users')->insert($insert);
                    // Reflect in memory maps for idempotency within this run
                    $existingUsers->put($key, (object)[
                        'id' => null,
                        'membership_number' => $key,
                        'email' => $email,
                        'phone' => $phone,
                        'address' => $address,
                        'created_at' => $joinedAt,
                    ]);
                }

                // Track email to prevent future duplicates within this run (case-insensitive owner mapping)
                $existingEmails[strtolower($email)] = $key;
            }
        });
    }

    private function generatedEmailFromMember(int $id, string $membership): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $membership));
        return "member-{$id}-{$slug}@old.local";
    }
}
