<?php
/**
 * Author: Yusuf Shakeel
 * Date: 15-Apr-2018 Sun
 * Version: 1.0
 *
 * File: user.php
 * Description: This file contains the user api.
 *
 * GitHub: https://github.com/yusufshakeel/jwt-php-project
 *
 * MIT License
 * Copyright (c) 2018 Yusuf Shakeel
 */

header('Content-Type: application/json');

require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;

// JWT secret
define('JWT_SECRET', 'this-is-the-secret');

// get the request method
if (!isset($_SERVER['REQUEST_METHOD']) || !isset($_SERVER['REQUEST_URI'])) {
    echo json_encode(array(
        'code' => 400,
        'status' => 'error',
        'message' => 'Bad Request'
    ));
}

// check the method
switch ($_SERVER['REQUEST_METHOD']) {
    case 'POST':
        validateUserCredential();
        break;

    case 'GET':
        getUser();
        break;

    default:
        echo json_encode(array(
            'code' => 403,
            'status' => 'error',
            'message' => 'Method Not Allowed'
        ));
        exit;
}

function validateUserCredential()
{
    //get json
    $data = json_decode(file_get_contents("php://input"), true);

    if (empty($data)) {
        $result = array(
            'code' => 0,
            'status' => 'error',
            'message' => 'Data missing'
        );
    } else {

        if (isset($data['email'])) {
            $email = $data['email'];
        } else {
            $email = null;
        }

        if (isset($data['password'])) {
            $password = $data['password'];
        } else {
            $password = null;
        }

        // check login credentials
        $result = array(
            'code' => '0',
            'status' => 'error',
            'message' => 'No match found'
        );
        foreach (getUserAccountData() as $user) {
            if ($user['email'] === $email && password_verify($password, $user['password'])) {
                $result = array(
                    'code' => 200,
                    'status' => 'success',
                    'message' => 'Valid login credentials.',
                    'userid' => $user['userid']
                );
            }
        }

        // on success generate jwt
        if (isset($result['status']) && $result['status'] === 'success') {
            $issuedAt = time();
            $expirationTime = $issuedAt + 60;  // jwt valid for 60 seconds from the issued time
            $payload = array(
                'userid' => $result['userid'],
                'iat' => $issuedAt,
                'exp' => $expirationTime
            );
            $key = JWT_SECRET;
            $alg = 'HS256';
            $jwt = JWT::encode($payload, $key, $alg);
            $result['jwt'] = $jwt;
        }
    }

    echo json_encode($result);
}

function getUser()
{
    // get the jwt from url
    $jwt = isset($_GET['jwt']) ? $_GET['jwt'] : null;

    if (isset($jwt)) {
        // validate jwt
        $key = JWT_SECRET;
        try {
            $decoded = JWT::decode($jwt, $key, array('HS256'));
            $decoded_array = (array)$decoded;

            $result = array(
                'code' => 0,
                'status' => 'success',
                'data' => getUserAccountData($decoded_array['userid']),
                'jwt_payload' => $decoded_array
            );

        } catch (\Exception $e) {
            $result = array(
                'code' => 0,
                'status' => 'error',
                'message' => 'Invalid JWT - Authentication failed!'
            );
        }
    } else {
        $result = array(
            'code' => 0,
            'status' => 'error',
            'message' => 'JWT parameter missing!'
        );
    }

    echo json_encode($result);
}

function getUserAccountData($userid = null)
{
    /**
     * FOR DEMO PURPOSE
     * I have created two accounts
     * Password of the accounts: root1234
     */
    $userAccountArr = array(

        array(
            "userid" => "u1",
            "email" => "yusufshakeel@example.com",
            "password" => "$2y$12$3PfY4lNCR62/HH9aNGZFcebloX1gACQIbWeHfTwb8hKhMXfymiNLq",
            "firstname" => "Yusuf",
            "lastname" => "Shakeel"
        ),

        array(
            "userid" => "u2",
            "email" => "user@example.com",
            "password" => "$2y$12$3PfY4lNCR62/HH9aNGZFcebloX1gACQIbWeHfTwb8hKhMXfymiNLq",
            "firstname" => "Yusuf",
            "lastname" => "Shakeel"
        )

    );

    /**
     * if userid set
     * then fetch particular account
     */
    if (isset($userid)) {
        $result = array(
            'code' => '0',
            'status' => 'error',
            'message' => 'No match found'
        );
        foreach ($userAccountArr as $user) {
            if ($user['userid'] === $userid) {

                // use removing the password from the result
                unset($user['password']);
                $result = $user;
            }
        }
    } else {
        $result = $userAccountArr;
    }

    return $result;

}