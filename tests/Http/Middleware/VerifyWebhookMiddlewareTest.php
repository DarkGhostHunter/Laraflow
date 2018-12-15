<?php

namespace Tests\Http\Middleware;

use Illuminate\Http\UploadedFile;
use Orchestra\Testbench\TestCase;

class VerifyWebhookMiddlewareTest extends TestCase
{

    protected static $token;
    protected static $secret;

    protected function setUp()
    {
        self::$token = strtoupper(bin2hex(random_bytes(20)));
        self::$secret = bin2hex(random_bytes(16));

        parent::setUp();

        \Route::middleware('flow-webhook')->any('/_test/webhook', function () {
            return 'OK';
        });
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('flow.webhook-secret', self::$secret);
    }

    protected function getPackageProviders($app)
    {
        return [
            'DarkGhostHunter\Laraflow\FlowHelpersServiceProvider',
        ];
    }

    public function testSuccess()
    {
        $response = $this->post('/_test/webhook?secret=' . self::$secret, [
            'token' => self::$token
        ]);

        $response->assertOk();
        $response->assertSeeText('OK');

    }

    public function testAbortsOnUnallowedMethod()
    {
        foreach (['get', 'put', 'patch', 'delete'] as $httpVerb) {
            $response = $this->{$httpVerb}('/_test/webhook');
            $response->assertNotFound();
        }
    }

    public function testAbortOnIncompletePost()
    {
        $response = $this->post('/_test/webhook');

        $response->assertNotFound();
    }

    public function testAbortOnPostWithoutToken()
    {
        $response = $this->post('/_test/webhook?secret=' . self::$secret);

        $response->assertNotFound();
    }

    public function testAbortOnPostWithoutSecret()
    {
        $response = $this->post('/_test/webhook', [
            'token' => self::$token
        ]);

        $response->assertNotFound();
    }

    public function testAbortOnPostWithIncorrectSecret()
    {
        $response = $this->post('/_test/webhook?secret=' . bin2hex(random_bytes(8)), [
            'token' => self::$token
        ]);

        $response->assertNotFound();
    }

    public function testAbortOnPostWithMalformedToken()
    {
        $array = $this->post('/_test/webhook?secret=' . self::$secret, [
            'token' => ['an array']
        ]);

        $array->assertNotFound();

        $file = $this->post('/_test/webhook?secret=' . self::$secret, [
            'token' => UploadedFile::fake()->image('malicious.script.exe')
        ]);

        $file->assertNotFound();
    }

}
