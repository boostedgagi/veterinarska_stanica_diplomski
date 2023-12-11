<?php

namespace App;

class ContextGroup
{

    public const CONTACT_MESSAGE_SENT = 'contact_message_sent';
    public const ON_CALL = 'on_call_created';
    public const SHOW_EXAMINATION = 'show_examination';

    public const CREATE_EXAMINATION = 'create_examination';
    public const CREATE_HEALTH_RECORD = 'create_health_record';
    public const SHOW_HEALTH_RECORD = 'show_health_record';

    public const CREATE_PET = 'create_pet';
    public const SHOW_PET = 'create_pet';
    public const FOUND_PET = 'found_pet';
    public const SHOW_USER_PETS = 'show_user_pets';

    public const CREATE_USER = 'create_user';
    public const SHOW_USER = 'show_user';
    public const SHOW_VET = 'show_vet';
    public const SHOW_NEARBY_VETS = 'show_nearby_vets';

    public const CANCEL_HEALTH_RECORD = 'cancel_health_record';
    public const ME = 'user_me';
}