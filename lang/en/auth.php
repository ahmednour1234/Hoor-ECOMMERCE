<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'These credentials do not match our records.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',

    // ---------------------------------------------------------- HOOR screens

    'login' => [
        'title'    => 'Welcome Back',
        'subtitle' => 'Sign in to continue to your HOOR account.',
        'submit'   => 'Login',
        'remember' => 'Remember me',
        'forgot'   => 'Forgot password?',
        'no_account' => 'Don\'t have an account?',
    ],

    'register' => [
        'title'      => 'Create Account',
        'subtitle'   => 'Join HOOR and start your shopping journey.',
        'submit'     => 'Register',
        'have_account' => 'Already have an account?',
        'agree'      => 'I agree to the',
        'terms'      => 'Terms & Conditions',
    ],

    'fields' => [
        'name'             => 'Full Name',
        'name_placeholder' => 'Enter your full name',
        'email'            => 'Email',
        'email_placeholder' => 'you@example.com',
        'password'         => 'Password',
        'password_placeholder' => 'Enter your password',
        'confirm'          => 'Confirm Password',
        'confirm_placeholder' => 'Confirm your password',
    ],

    'or' => 'or',

    'show_password' => 'Show password',
    'hide_password' => 'Hide password',

    'forgot_page' => [
        'title'    => 'Reset Password',
        'subtitle' => 'Enter your email and we will send you a reset link.',
        'submit'   => 'Send reset link',
        'back'     => 'Back to login',
    ],

    'reset_page' => [
        'title'    => 'Choose a New Password',
        'subtitle' => 'Pick something you have not used before.',
        'submit'   => 'Save password',
    ],

    'confirm_page' => [
        'title'    => 'Confirm Password',
        'subtitle' => 'Please confirm your password before continuing.',
        'submit'   => 'Confirm',
    ],

    'verify_page' => [
        'title'    => 'Verify Your Email',
        'subtitle' => 'We sent you a link. Click it to finish setting up your account.',
        'sent'     => 'A new link is on its way.',
        'resend'   => 'Resend the link',
        'logout'   => 'Sign out',
    ],
];
