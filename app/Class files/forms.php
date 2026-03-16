<?php

$formSchemas = [
    'login' => [
        ['name' => 'username', 'label' => 'Username:', 'type' => 'text', 'rules' => 'required'],
        ['name' => 'password', 'label' => 'Password:', 'type' => 'password', 'rules' => 'required']
    ],
    'register' => [
        ['name' => 'username', 'label' => 'Username:', 'type' => 'text', 'rules' => 'required|min:3'],
        ['name' => 'password', 'label' => 'Password:', 'type' => 'password', 'rules' => 'required|min:6'],
        ['name' => 'confirm_password', 'label' => 'Confirm Password:', 'type' => 'password', 'rules' => 'match:password'],
        ['name' => 'name', 'label' => 'Name:', 'type' => 'text', 'rules' => 'required'],
        ['name' => 'age', 'label' => 'Age:', 'type' => 'number', 'rules' => 'required|numeric'],
        ['name' => 'city', 'label' => 'City:', 'type' => 'text', 'rules' => null],
        ['name' => 'email', 'label' => 'Email:', 'type' => 'email', 'rules' => 'required|email']
    ],
    'editUser' => [
        ['name' => 'auPK', 'label' => '', 'type' => 'hidden', 'rules' => null], // Will prepopulate dynamically
        ['name' => 'username', 'label' => 'Username:', 'type' => 'text', 'rules' => 'required|min:3'],
        ['name' => 'name', 'label' => 'Name:', 'type' => 'text', 'rules' => 'required'],
        ['name' => 'age', 'label' => 'Age:', 'type' => 'number', 'rules' => 'required|numeric'],
        ['name' => 'city', 'label' => 'City:', 'type' => 'text', 'rules' => null],
        ['name' => 'email', 'label' => 'Email:', 'type' => 'email', 'rules' => 'required|email']
        // verified_status is added conditionally in UI, but we can't fully express that securely here without
        // adding a custom flag, so we'll leave it out of the base schema and append it dynamically in the UI.
    ]
];

?>
