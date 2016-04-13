<?php

class FoxyStripeEventCategory extends DataObject implements PermissionProvider
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
        return Permission::check('EventCategory_CRUD');
    }

    public function canDelete($member = null)
    {
        return Permission::check('EventCategory_CRUD');
    }

    public function canCreate($member = null)
    {
        return Permission::check('EventCategory_CRUD');
    }

    public function canPublish($member = null)
    {
        return Permission::check('EventCategory_CRUD');
    }

    public function providePermissions()
    {
        return array(
            'EventCategory_CRUD' => 'Allow user to manage Event Categories',
        );
    }
}
