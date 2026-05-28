<?php
$ch = curl_init('https://sigma-project-one.vercel.app/api/dashboard');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer cr5gasTRKaZFyzVTwWVpIoCiViiq4IFXRVSGo0ScZAA87JYbxyyZc9jzWjvMSuxfHjZYoeyEi7sUZnfU',
    'Accept: application/json'
]);
$response = curl_exec($ch);
echo substr($response, 0, 1000);
