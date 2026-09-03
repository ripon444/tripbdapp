<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckAccountStatus;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleAuthorizationTest extends TestCase
{
    public function test_customer_cannot_access_driver_or_admin_routes()
    {
        $customer = new User(['id' => 1, 'role' => 'customer', 'status' => 'active']);
        $middleware = new CheckRole();

        $request = Request::create('/api/v1/driver/profile', 'GET');
        $request->setUserResolver(fn() => $customer);

        $response = $middleware->handle($request, fn() => new Response('OK'), 'driver');

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_driver_can_access_driver_routes()
    {
        $driver = new User(['id' => 2, 'role' => 'driver', 'status' => 'active']);
        $middleware = new CheckRole();

        $request = Request::create('/api/v1/driver/profile', 'GET');
        $request->setUserResolver(fn() => $driver);

        $response = $middleware->handle($request, fn() => new Response('OK'), 'driver');

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_suspended_user_is_blocked_by_status_middleware()
    {
        $suspendedUser = new User(['id' => 3, 'role' => 'customer', 'status' => 'suspended']);
        $middleware = new CheckAccountStatus();

        $request = Request::create('/api/v1/customer/profile', 'GET');
        $request->setUserResolver(fn() => $suspendedUser);

        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals(403, $response->getStatusCode());
    }
}
