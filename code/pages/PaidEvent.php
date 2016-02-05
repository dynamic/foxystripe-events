<?php

class PaidEvent extends ProductPage implements PermissionProvider
{
    private static $singular_name = 'Paid Event';
    private static $plural_name = 'Paid Events';
    private static $description = 'Event page with paid registration';

    private static $db = array(
        'Date' => 'Date',
        'EndDate' => 'Date',
        'Time' => 'Time',
        'EndTime' => 'Time',
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        DateField::set_default_config('showcalendar', true);

        // Event Info
        $fields->addFieldsToTab('Root.Event.Info', array(
            DateField::create('Date')->setTitle('Event Start Date'),
            DateField::create('EndDate')->setTitle('Event End Date'),
            TimePickerField::create('Time')->setTitle('Event Time'),
            TimePickerField::create('EndTime')->setTitle('Event End Time'),
        ));

        // Registrations
        $orders = new ArrayList();
        $addToOrders = function ($details) use (&$orders) {
            if (!$orders->find('ID', $details->OrderID)) {
                $orders->push($details->OrderID);
            }
        };
        $this->owner->OrderDetails()->each($addToOrders);
        $registrations = Order::get()->filter(array('ID' => $orders->toArray()));

        $gridConfig = GridFieldConfig_RecordViewer::create();
        $fields->addFieldsToTab('Root.Event.Registrations', array(
            GridField::create('Registrations', 'Registrations', $registrations, $gridConfig),
        ));

        $this->extend('updateCMSFields', $fields);

        return $fields;
    }

    public function validate()
    {
        $result = parent::validate();

        if ($this->EndTime && ($this->Time > $this->EndTime)) {
            return $result->error('End Time must be later than the Start Time');
        }

        if ($this->EndDate && ($this->Date > $this->EndDate)) {
            return $result->error('End Date must be equal to the Start Date or in the future');
        }

        return $result;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->EndDate) {
            $this->EndDate = $this->Date;
        }
    }

    /**
     * @param Member $member
     *
     * @return bool
     */
    public function canView($member = null)
    {
        return parent::canView($member = null);
    }

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

    public function providePermissions()
    {
        return array(
            'Event_CRUD' => 'Create, Update and Delete a Paid Event',
        );
    }
}

class PaidEvent_Controller extends ProductPage_Controller
{
}
