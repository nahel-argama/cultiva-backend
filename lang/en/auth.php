<?php

return [
    'unauthenticated' => 'Unauthenticated.',
    'forbidden' => 'You do not have permission to access this resource.',
    'unverified' => 'The user is not verified.',
    'sign_up' => [
        'user_without_profile' => 'The user does not have a valid profile type.',
        'in_progress' => 'A registration is already in progress for this email.',
    ],
    'login' => [
        'invalid_credentials' => 'Invalid credentials.',
        'invalid_profile' => 'The user must have exactly one profile.',
        'user_not_active' => 'The user is not active.',
    ],
    'document_verification' => [
        'queued' => 'Document uploaded and queued for verification.',
        'already_verified' => 'The user is already verified.',
        'status_not_found' => 'No document validation in progress.',
        'pending_message' => 'Document verification is in progress.',
        'approved_message' => 'Document verified successfully.',
        'failed_message' => 'Document verification failed.',
    ],
];
