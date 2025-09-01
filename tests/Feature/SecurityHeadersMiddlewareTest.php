<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_added_in_production(): void
    {
        // Force production environment
        $this->app['env'] = 'production';
        
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('home'));
            
        // Test Content Security Policy
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self'", $csp);
        $this->assertStringContainsString("https://cdn.tailwindcss.com", $csp);
        $this->assertStringContainsString("style-src 'self'", $csp);
        $this->assertStringContainsString("https://fonts.bunny.net", $csp);
        $this->assertStringContainsString("'unsafe-inline'", $csp);
        $this->assertStringContainsString("font-src 'self'", $csp);
        $this->assertStringContainsString("img-src 'self' data: blob:", $csp);
        $this->assertStringContainsString("connect-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        
        // Test Referrer Policy
        $this->assertTrue($response->headers->has('Referrer-Policy'));
        $this->assertEquals('same-origin', $response->headers->get('Referrer-Policy'));
        
        // Test X-Content-Type-Options
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        
        // Test X-Frame-Options
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
        
        // Test Permissions Policy
        $this->assertTrue($response->headers->has('Permissions-Policy'));
        $permissionsPolicy = $response->headers->get('Permissions-Policy');
        $this->assertStringContainsString('camera=()', $permissionsPolicy);
        $this->assertStringContainsString('microphone=()', $permissionsPolicy);
        $this->assertStringContainsString('geolocation=()', $permissionsPolicy);
        $this->assertStringContainsString('payment=()', $permissionsPolicy);
        $this->assertStringContainsString('usb=()', $permissionsPolicy);
        $this->assertStringContainsString('accelerometer=()', $permissionsPolicy);
        $this->assertStringContainsString('gyroscope=()', $permissionsPolicy);
        $this->assertStringContainsString('magnetometer=()', $permissionsPolicy);
        $this->assertStringContainsString('interest-cohort=()', $permissionsPolicy);
        
        // Test Cross-Origin Resource Policy
        $this->assertTrue($response->headers->has('Cross-Origin-Resource-Policy'));
        $this->assertEquals('same-site', $response->headers->get('Cross-Origin-Resource-Policy'));
        
        // Test Cross-Origin Opener Policy
        $this->assertTrue($response->headers->has('Cross-Origin-Opener-Policy'));
        $this->assertEquals('same-origin', $response->headers->get('Cross-Origin-Opener-Policy'));
    }

    public function test_security_headers_not_added_in_non_production(): void
    {
        // Ensure we're not in production
        $this->app['env'] = 'testing';
        
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('home'));
            
        // Security headers should not be present in non-production
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('Referrer-Policy'));
        $this->assertFalse($response->headers->has('X-Content-Type-Options'));
        $this->assertFalse($response->headers->has('X-Frame-Options'));
        $this->assertFalse($response->headers->has('Permissions-Policy'));
        $this->assertFalse($response->headers->has('Cross-Origin-Resource-Policy'));
        $this->assertFalse($response->headers->has('Cross-Origin-Opener-Policy'));
    }

    public function test_security_headers_applied_to_api_routes(): void
    {
        $this->app['env'] = 'production';
        
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('photos.feed'));
            
        // Security headers should be present on API routes too
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function test_security_headers_applied_to_upload_routes(): void
    {
        $this->app['env'] = 'production';
        
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('photos.upload.show'));
            
        // Security headers should be present on upload routes
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('Permissions-Policy'));
        $this->assertTrue($response->headers->has('Cross-Origin-Resource-Policy'));
    }

    public function test_middleware_handles_different_response_types(): void
    {
        $this->app['env'] = 'production';
        
        // Test with JSON response
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->getJson(route('photos.feed'));
            
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function test_middleware_unit_functionality(): void
    {
        // Test the middleware directly by setting environment
        $this->app['env'] = 'production';
        
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/test', 'GET');
        
        $response = $middleware->handle($request, function ($request) {
            return new Response('Test content');
        });
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertTrue($response->headers->has('Content-Security-Policy'));
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_middleware_unit_non_production(): void
    {
        // Test the middleware directly by setting environment
        $this->app['env'] = 'local';
        
        $middleware = new SecurityHeadersMiddleware();
        $request = Request::create('/test', 'GET');
        
        $response = $middleware->handle($request, function ($request) {
            return new Response('Test content');
        });
        
        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse($response->headers->has('Content-Security-Policy'));
        $this->assertFalse($response->headers->has('X-Frame-Options'));
    }

    public function test_csp_header_format(): void
    {
        $this->app['env'] = 'production';
        
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('home'));
        
        $csp = $response->headers->get('Content-Security-Policy');
        
        // Ensure CSP is properly formatted with semicolons
        $this->assertStringContainsString('; ', $csp);
        
        // Ensure all required directives are separated correctly
        $directives = explode('; ', $csp);
        $this->assertGreaterThan(5, count($directives));
        
        // Check specific directive format
        $defaultSrcFound = false;
        $scriptSrcFound = false;
        
        foreach ($directives as $directive) {
            if (str_starts_with($directive, 'default-src')) {
                $defaultSrcFound = true;
                $this->assertEquals("default-src 'self'", $directive);
            }
            if (str_starts_with($directive, 'script-src')) {
                $scriptSrcFound = true;
                $this->assertStringContainsString("'self'", $directive);
            }
        }
        
        $this->assertTrue($defaultSrcFound);
        $this->assertTrue($scriptSrcFound);
    }

    public function test_all_security_headers_count(): void
    {
        $this->app['env'] = 'production';
        
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('home'));
        
        $securityHeaders = [
            'Content-Security-Policy',
            'Referrer-Policy',
            'X-Content-Type-Options',
            'X-Frame-Options',
            'Permissions-Policy',
            'Cross-Origin-Resource-Policy',
            'Cross-Origin-Opener-Policy',
        ];
        
        foreach ($securityHeaders as $header) {
            $this->assertTrue($response->headers->has($header), "Missing security header: {$header}");
        }
        
        // Ensure we have exactly the expected number of security headers
        $presentHeaders = array_filter($securityHeaders, fn($header) => $response->headers->has($header));
        $this->assertCount(7, $presentHeaders);
    }
}