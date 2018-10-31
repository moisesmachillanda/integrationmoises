<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header("Content-Type: text/plain");

require 'vendor/autoload.php';
use SparkPost\SparkPost;
use GuzzleHttp\Client;
use Http\Adapter\Guzzle6\Client as GuzzleAdapter;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

$httpClient = new GuzzleAdapter(new Client());
$sparky = new SparkPost($httpClient, ['key'=>'3646dbb03a38df0d55441775155e745259338628', 'debug'=>true]);

// echo print_r($sparky, true);

$client = new Client([
    // Base URI is used with relative requests
    'base_uri' => 'http://httpbin.org',
    // You can set any number of default request options.
    'timeout'  => 2.0,
]);
$resp = $client->get('http://httpbin.org/get');

$promise = $sparky->transmissions->post([
    'content' => [
        'from' => [
            'name' => 'Email Tester',
            'email' => 'no-reply@email.myreadylink.com',
        ],
        'reply_to' => 'support@diakon.org',
        'subject' => 'Mailing From PHP',
        'html' => '<html><body><h1>Email API Connecte, {{name}}!</h1><p>You just sent your very first mailing!</p></body></html>',
        'text' => 'Congratulations, {{name}}!! You just sent your very first mailing!',
    ],
    'substitution_data' => ['name' => 'Triple Strength'],
    'recipients' => [
        [
            'address' => [
                'name' => 'Web Developer',
                'email' => 'mmachillanda@triplestrength.com',
            ],
        ],


    ],
    // 'cc' => [
    //     [
    //         'address' => [
    //             'name' => 'Hung',
    //             'email' => 'hnguyen@triplestrength.com',
    //         ],
    //     ],
    // ],
    // 'bcc' => [
    //     [
    //         'address' => [
    //             'name' => 'Deb',
    //             'email' => 'dkemp@triplestrength.com',
    //         ],
    //     ],
    // ],
]);


$promise->wait();

$promise->then(
    // Success callback
    function ($response) {
        echo "\nLine " .__LINE__ . ": Made it here?\n\n";
        echo "Success!\n";
        echo $response->getStatusCode()."\n";
        print_r($response->getBody())."\n";
    },
    // Failure callback
    function (Exception $e) {
        echo "\nLine " .__LINE__ . ": Made it here?\n\n";
        echo "Error?!\n";
        echo $e->getCode()."\n";
        echo $e->getMessage()."\n";
    }
);
