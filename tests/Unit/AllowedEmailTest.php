<?php

namespace Tests\Unit;

use App\Models\AllowedEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AllowedEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_allowed_returns_true_for_active_email(): void
    {
        AllowedEmail::create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'is_active' => true,
        ]);

        $this->assertTrue(AllowedEmail::isAllowed('test@example.com'));
    }

    public function test_is_allowed_returns_false_for_inactive_email(): void
    {
        AllowedEmail::create([
            'email' => 'inactive@example.com',
            'name' => 'Inactive User',
            'is_active' => false,
        ]);

        $this->assertFalse(AllowedEmail::isAllowed('inactive@example.com'));
    }

    public function test_is_allowed_returns_false_for_non_existent_email(): void
    {
        $this->assertFalse(AllowedEmail::isAllowed('nonexistent@example.com'));
    }

    public function test_is_allowed_is_case_sensitive(): void
    {
        AllowedEmail::create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'is_active' => true,
        ]);

        $this->assertFalse(AllowedEmail::isAllowed('TEST@EXAMPLE.COM'));
        $this->assertFalse(AllowedEmail::isAllowed('Test@Example.Com'));
    }

    public function test_get_active_emails_returns_only_active_emails(): void
    {
        // Create some test emails
        AllowedEmail::create([
            'email' => 'active1@example.com',
            'name' => 'Active User 1',
            'is_active' => true,
        ]);

        AllowedEmail::create([
            'email' => 'active2@example.com', 
            'name' => 'Active User 2',
            'is_active' => true,
        ]);

        AllowedEmail::create([
            'email' => 'inactive@example.com',
            'name' => 'Inactive User',
            'is_active' => false,
        ]);

        $activeEmails = AllowedEmail::getActiveEmails();

        $this->assertCount(2, $activeEmails);
        $this->assertTrue($activeEmails->contains('email', 'active1@example.com'));
        $this->assertTrue($activeEmails->contains('email', 'active2@example.com'));
        $this->assertFalse($activeEmails->contains('email', 'inactive@example.com'));
    }

    public function test_get_active_emails_returns_empty_collection_when_no_active_emails(): void
    {
        // Create only inactive emails
        AllowedEmail::create([
            'email' => 'inactive1@example.com',
            'name' => 'Inactive User 1',
            'is_active' => false,
        ]);

        AllowedEmail::create([
            'email' => 'inactive2@example.com',
            'name' => 'Inactive User 2',
            'is_active' => false,
        ]);

        $activeEmails = AllowedEmail::getActiveEmails();

        $this->assertCount(0, $activeEmails);
        $this->assertTrue($activeEmails->isEmpty());
    }

    public function test_get_active_emails_returns_empty_collection_when_no_emails_exist(): void
    {
        $activeEmails = AllowedEmail::getActiveEmails();

        $this->assertCount(0, $activeEmails);
        $this->assertTrue($activeEmails->isEmpty());
    }

    public function test_model_casts_is_active_to_boolean(): void
    {
        $email = AllowedEmail::create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'is_active' => 1, // Integer
        ]);

        $this->assertIsBool($email->is_active);
        $this->assertTrue($email->is_active);

        $email->update(['is_active' => 0]);
        $email->refresh();

        $this->assertIsBool($email->is_active);
        $this->assertFalse($email->is_active);
    }

    public function test_fillable_attributes_can_be_mass_assigned(): void
    {
        $data = [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'is_active' => true,
        ];

        $email = AllowedEmail::create($data);

        $this->assertEquals($data['email'], $email->email);
        $this->assertEquals($data['name'], $email->name);
        $this->assertEquals($data['is_active'], $email->is_active);
    }
}