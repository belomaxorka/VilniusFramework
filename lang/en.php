<?php declare(strict_types=1);

return [
    'hello' => 'Hello, :name!',
    'welcome' => 'Welcome to our application!',
    'goodbye' => 'Goodbye, see you later!',
    
    // Nested translations example
    'user' => [
        'profile' => [
            'title' => 'User Profile',
            'edit' => 'Edit Profile',
            'save' => 'Save Changes',
        ],
        'account' => [
            'settings' => 'Account Settings',
            'delete' => 'Delete Account',
            'password' => 'Change Password',
        ],
        'greeting' => 'Hello, :username!',
    ],
    
    'errors' => [
        'not_found' => 'Page not found',
        'unauthorized' => 'Unauthorized access',
        'server_error' => 'Internal server error',
        'validation' => [
            'required' => 'The :field field is required',
            'email' => 'Please enter a valid email',
            'min' => 'The :field must be at least :min characters',
        ],
    ],
    
    'buttons' => [
        'submit' => 'Submit',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
        'save' => 'Save',
    ],
];