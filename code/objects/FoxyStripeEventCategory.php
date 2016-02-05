<?php

class FoxyStripeEventCategory extends DataObject
{
    private static $db = array(
        'Title' => 'Varchar(255)',
    );

    private static $belongs_many_many = array(
        'Calendars' => 'FoxyStripeCalendar',
        'Events' => 'PaidEvent',
    );

    /**
     * @param Member $member
     *
     * @return bool
     */
    public function canEdit($member = null)
    {
        return Permission::check('Event_CRUD');
    }

    public function canDelete($member = null)
    {
        return Permission::check('Event_CRUD');
    }

    public function canCreate($member = null)
    {
        return Permission::check('Event_CRUD');
    }

    public function canPublish($member = null)
    {
        return Permission::check('Event_CRUD');
    }
}
