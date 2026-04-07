<?php

namespace Tests\Feature;

use App\Http\Middleware\IpWhitelistMiddleware;
use App\Models\WhitelistedIp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AdminIpWhitelistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/test-ip-whitelist', function () {
            return 'Allowed';
        })->middleware(IpWhitelistMiddleware::class);
    }

    public function test_it_allows_any_ip_when_whitelist_is_empty()
    {
        Config::set('cooperative.admin_ip_whitelist', []);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
            ->get('/test-ip-whitelist');

        $response->assertStatus(200);
        $response->assertSee('Allowed');
    }

    public function test_it_allows_whitelisted_ip()
    {
        Config::set('cooperative.admin_ip_whitelist', ['1.2.3.4']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
            ->get('/test-ip-whitelist');

        $response->assertStatus(200);
        $response->assertSee('Allowed');
    }

    public function test_it_blocks_non_whitelisted_ip()
    {
        Config::set('cooperative.admin_ip_whitelist', ['1.2.3.4']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.5'])
            ->get('/test-ip-whitelist');

        $response->assertStatus(403);
    }

    public function test_it_allows_ip_in_cidr_range()
    {
        Config::set('cooperative.admin_ip_whitelist', ['192.168.1.0/24']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.50'])
            ->get('/test-ip-whitelist');

        $response->assertStatus(200);
        $response->assertSee('Allowed');
    }

    public function test_it_blocks_ip_outside_cidr_range()
    {
        Config::set('cooperative.admin_ip_whitelist', ['192.168.1.0/24']);

        $response = $this->withServerVariables(['REMOTE_ADDR' => '192.168.2.1'])
            ->get('/test-ip-whitelist');

        $response->assertStatus(403);
    }

    public function test_it_works_with_multiple_entries_including_spaces()
    {
        // Simulate space in env string by using trim and array_map in config
        Config::set('cooperative.admin_ip_whitelist', array_filter(array_map('trim', explode(',', '1.2.3.4, 192.168.1.0/24 '))));

        $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
            ->get('/test-ip-whitelist')
            ->assertStatus(200);

        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.100'])
            ->get('/test-ip-whitelist')
            ->assertStatus(200);

        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->get('/test-ip-whitelist')
            ->assertStatus(403);
    }

    public function test_it_protects_admin_dashboard()
    {
        Config::set('cooperative.admin_ip_whitelist', ['1.2.3.4']);

        // Accessing admin login page (it's also protected by the same middleware stack)
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
            ->get('/admin/login')
            ->assertStatus(403);

        $this->withServerVariables(['REMOTE_ADDR' => '1.2.3.4'])
            ->get('/admin/login')
            ->assertStatus(200);
    }

    public function test_it_allows_ip_from_database()
    {
        Config::set('cooperative.admin_ip_whitelist', []);
        WhitelistedIp::create(['ip_address' => '5.5.5.5', 'is_active' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])
            ->get('/test-ip-whitelist')
            ->assertStatus(200);

        $this->withServerVariables(['REMOTE_ADDR' => '6.6.6.6'])
            ->get('/test-ip-whitelist')
            ->assertStatus(403);
    }

    public function test_it_blocks_inactive_ip_from_database()
    {
        Config::set('cooperative.admin_ip_whitelist', []);
        WhitelistedIp::create(['ip_address' => '5.5.5.5', 'is_active' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])
            ->get('/test-ip-whitelist')
            ->assertStatus(403);
    }

    public function test_it_updates_last_used_at_from_database()
    {
        Config::set('cooperative.admin_ip_whitelist', []);
        $ip = WhitelistedIp::create(['ip_address' => '5.5.5.5', 'is_active' => true]);

        $this->assertNull($ip->last_used_at);

        $this->withServerVariables(['REMOTE_ADDR' => '5.5.5.5'])
            ->get('/test-ip-whitelist');

        $this->assertNotNull($ip->fresh()->last_used_at);
    }

    public function test_it_combines_static_and_database_whitelists()
    {
        Config::set('cooperative.admin_ip_whitelist', ['1.1.1.1']);
        WhitelistedIp::create(['ip_address' => '2.2.2.2', 'is_active' => true]);

        $this->withServerVariables(['REMOTE_ADDR' => '1.1.1.1'])
            ->get('/test-ip-whitelist')
            ->assertStatus(200);

        $this->withServerVariables(['REMOTE_ADDR' => '2.2.2.2'])
            ->get('/test-ip-whitelist')
            ->assertStatus(200);

        $this->withServerVariables(['REMOTE_ADDR' => '3.3.3.3'])
            ->get('/test-ip-whitelist')
            ->assertStatus(403);
    }
}
