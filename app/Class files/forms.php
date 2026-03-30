<?php

$formSchemas = [
    'login' => [
        ['name' => 'username', 'label' => 'Username:', 'type' => 'text', 'rules' => ['required' => true]],
        ['name' => 'password', 'label' => 'Password:', 'type' => 'password', 'rules' => ['required' => true]]
    ],
    'register' => [
        ['name' => 'username', 'label' => 'Username:', 'type' => 'text', 'rules' => ['required' => true, 'min' => 3]],
        ['name' => 'password', 'label' => 'Password:', 'type' => 'password', 'rules' => ['required' => true, 'min' => 6]],
        ['name' => 'confirm_password', 'label' => 'Confirm Password:', 'type' => 'password', 'rules' => ['match' => 'password']],
        ['name' => 'name', 'label' => 'Name:', 'type' => 'text', 'rules' => ['required' => true]],
        ['name' => 'age', 'label' => 'Age:', 'type' => 'number', 'rules' => ['required' => true, 'numeric' => true]],
        ['name' => 'city', 'label' => 'City:', 'type' => 'text', 'rules' => []],
        ['name' => 'email', 'label' => 'Email:', 'type' => 'email', 'rules' => ['required' => true, 'email' => true]]
    ],
    'editUser' => [
        ['name' => 'auPK', 'label' => '', 'type' => 'hidden', 'rules' => []], // Will prepopulate dynamically
        ['name' => 'username', 'label' => 'Username:', 'type' => 'text', 'rules' => ['required' => true, 'min' => 3]],
        ['name' => 'name', 'label' => 'Name:', 'type' => 'text', 'rules' => ['required' => true]],
        ['name' => 'age', 'label' => 'Age:', 'type' => 'number', 'rules' => ['required' => true, 'numeric' => true]],
        ['name' => 'city', 'label' => 'City:', 'type' => 'text', 'rules' => []],
        ['name' => 'email', 'label' => 'Email:', 'type' => 'email', 'rules' => ['required' => true, 'email' => true]]
        // verified_status is added conditionally in UI, but we can't fully express that securely here without
        // adding a custom flag, so we'll leave it out of the base schema and append it dynamically in the UI.
    ]
];

?>
