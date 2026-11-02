<?php

namespace App\GP247\Plugins\MFA\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use GP247\Core\Models\AdminMenu;

class ExtensionModel extends Model
{
    /** Vendor user class name wrongly shipped as the vendor guard model up to 2.3.x. */
    public const LEGACY_VENDOR_MODEL = 'App\GP247\Plugins\MultiVendorPro\Models\VendorUser';

    /** Vendor user class registered by the MultiVendor plugin (provider `vendors`). */
    public const VENDOR_MODEL = 'App\GP247\Plugins\MultiVendor\Models\VendorUser';

    public function __construct()
    {
        //
    }

    /**
     * Install extension — also the converge step of update(): every part only
     * creates what is missing, so running it again changes nothing.
     *
     * @return void
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-guard-config-real-guards, US-mfa-trusted-device
     */
    public function installExtension()
    {
        // Create two_factor_auth table
        if (!Schema::hasTable('two_factor_auth')) {
            Schema::create('two_factor_auth', function (Blueprint $table) {
                $table->id();
                $table->string('user_type'); // Polymorphic user type (model class)
                $table->string('user_id'); // User ID (supports both integer and UUID)
                $table->text('secret'); // Encrypted secret key
                $table->text('recovery_codes')->nullable(); // Encrypted recovery codes
                $table->boolean('enabled')->default(0); // Is MFA enabled
                $table->timestamp('enabled_at')->nullable(); // When MFA was enabled
                $table->timestamp('last_used_at')->nullable(); // Last time MFA was used
                $table->timestamps();

                // Index for faster lookups
                $table->index(['user_type', 'user_id']);
                $table->unique(['user_type', 'user_id']);
            });
        }

        // Browsers whose owner chose "trust this device" (only the token's SHA-256 is stored)
        if (!Schema::hasTable('mfa_trusted_devices')) {
            Schema::create('mfa_trusted_devices', function (Blueprint $table) {
                $table->id();
                $table->string('user_type');
                $table->string('user_id');
                $table->char('token_hash', 64)->unique();
                $table->string('user_agent', 255)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('expires_at');
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();

                $table->index(['user_type', 'user_id']);
            });
        }

        $this->moveLegacyVendorEnrolments();

        // Ensure admin menu root exists under SECURITY group if needed
        $checkMenu = AdminMenu::where('key','MFA')->first();
        if (!$checkMenu) {
            $menuSecurity = AdminMenu::where('key', 'ADMIN_SECURITY')->first();
            AdminMenu::insert([
                'parent_id' => $menuSecurity->id,
                'title' => 'Plugins/MFA::lang.title',
                'icon' => 'fas fa-chalkboard-teacher',
                'uri' => 'route_admin::admin_mfa.index',
            ]);
        }


    }

    /**
     * Uninstall extension
     * 
     * @return void
     */
    public function uninstallExtension()
    {
        // Drop tables
        Schema::dropIfExists('mfa_trusted_devices');
        Schema::dropIfExists('two_factor_auth');

        (new AdminMenu)->where('uri', 'route_admin::admin_mfa.index')->delete();
    }

    /**
     * Re-attach vendor enrolments saved under the wrong class name (up to 2.3.x).
     *
     * Only rows of the legacy name are touched; an account that already has a row
     * under the real name keeps both (the unique key forbids merging), so running
     * this again changes nothing.
     *
     * @return int Rows moved.
     *
     * @aidlc-unit mfa-plugin
     * @aidlc-story US-mfa-guard-config-real-guards
     */
    public function moveLegacyVendorEnrolments(): int
    {
        if (!Schema::hasTable('two_factor_auth')) {
            return 0;
        }
        $taken = TwoFactorAuth::where('user_type', self::VENDOR_MODEL)->pluck('user_id')->all();

        return TwoFactorAuth::where('user_type', self::LEGACY_VENDOR_MODEL)
            ->whereNotIn('user_id', $taken)
            ->update(['user_type' => self::VENDOR_MODEL]);
    }
}

