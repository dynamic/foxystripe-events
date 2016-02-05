<?php

class FoxyStripeCalendar extends ProductHolder
{
    private static $singular_name = 'Event Calendar';
    private static $plural_name = 'Event Calendars';
    private static $description = 'Event calendar, displays upcoming events';

    public static $item_class = 'PaidEvent';
    private static $allowed_children = array('PaidEvent');

    private static $timezone = 'America/Chicago';

    private static $db = array(
        'EventsPerPage' => 'Int',
        'RangeToShow' => 'Enum("Month,Year,All Upcoming","Month")',
    );

    private static $many_many = array(
        'Categories' => 'FoxyStripeEventCategory',
    );

    private static $many_many_extraFields = array(
        'Categories' => array(
            'SortOrder' => 'Int',
        ),
    );

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create(
                'RangeToShow',
                'Range to show',
                singleton('FoxyStripeCalendar')->dbObject('RangeToShow')->enumValues()
            ),
            'Content'
        );
        $fields->addFieldToTab(
            'Root.Main',
            NumericField::create(
                'EventsPerPage'
            )->setTitle('Events to show per page (0 shows all based on the "Rage to show")'),
            'Content'
        );

        $config = GridFieldConfig_RelationEditor::create();
        if (class_exists('GridFieldSortableRows')) {
            $config->addComponent(new GridFieldSortableRows('SortOrder'));
        }
        if (class_exists('GridFieldAddExistingSearchButton')) {
            $config->removeComponentsByType('GridFieldAddExistingAutocompleter');
            $config->addComponent(new GridFieldAddExistingSearchButton());
        }
        $categories = $this->owner->Categories()->sort('SortOrder');
        $categoryField = GridField::create('Categories', 'Categories', $categories, $config);

        $fields->addFieldsToTab('Root.Filter', array(
            $categoryField,
        ));

        return $fields;
    }

    public function buildEndDate($start = null)
    {
        if ($start === null) {
            $start = sfDate::getInstance(strtotime('now'));
        }

        switch ($this->RangeToShow) {
            case 'Day':
                $end_date = $start;
                break;
            case 'Year':
                $end_date = date('Y-m-d', strtotime(date('Y-m-d', time()).' + 365 day'));
                break;
            case 'All Upcoming':
                $end_date = false;
                break;
            default:
                $end_date = date('Y-m-d', strtotime(date('Y-m-d', time()).' + 1 month'));
                break;
        }

        return $end_date;
    }

    public static function getUpcomingEvents($filter = array(), $limit = 10)
    {
        $filter['Date:GreaterThanOrEqual'] = date('Y-m-d', strtotime('now'));

        // filter by Category
        if (isset($filter['ParentID']) && $filter['ParentID'] != '') {
            $calendar = self::get()->byID($filter['ParentID']);
            if ($calendar->Categories()->exists()) {
                $filter['Categories.ID'] = $calendar->Categories()->map('ID', 'ID')->toArray();
            }
        }

        $events = ($limit == 0) ?
            PaidEvent::get()
                ->filter($filter)
                ->sort('Date', 'ASC')

            :
            PaidEvent::get()
                ->filter($filter)
                ->limit($limit)
                ->sort('Date', 'ASC');

        return $events;
    }

    public function getEvents($filter = null, $limit = 10)
    {
        $eventList = ArrayList::create();

        if ($this->data()->Categories()->exists()) {
            $filter['ParentID'] = $this->ID;
        }

        $events = self::getUpcomingEvents($filter, $limit);
        $eventList->merge($events);

        return $eventList;
    }

    public function getItemsShort()
    {
        return PaidEvent::get()
            ->limit(3)
            ->filter(array(
                'Date:LessThan:Not' => date('Y-m-d', strtotime('now')),
                'ParentID' => $this->ID,
            ))
            ->sort('Date', 'ASC');
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
        return Permission::check('FoxyStripeCalendar_CRUD');
    }

    public function canDelete($member = null)
    {
        return Permission::check('FoxyStripeCalendar_CRUD');
    }

    public function canCreate($member = null)
    {
        return Permission::check('FoxyStripeCalendar_CRUD');
    }

    public function providePermissions()
    {
        return array(
            //'Location_VIEW' => 'Read a Location',
            'FoxyStripeCalendar_CRUD' => 'Create, Update and Delete a FoxyStripe Calendar Page',
        );
    }
}

class FoxyStripeCalendar_Controller extends ProductHolder_Controller
{
    public function Items($filter = array(), $pageSize = 10)
    {
        $filter['ParentID'] = $this->Data()->ID;
        $class = $this->Data()->stat('item_class');

        $items = $this->getUpcomingEvents($filter);

        $list = PaginatedList::create($items, $this->request);
        $list->setPageLength($pageSize);

        return $list;
    }

    public function getUpcomingEvents($filter = array())
    {
        $pageSize = ($this->data()->EventsPerPage == 0) ? 10 : $this->data()->EventsPerPage;

        $filter['EndDate:GreaterThanOrEqual'] = date('Y-m-d', strtotime('now'));
        if ($this->data()->RangeToShow != 'All Upcoming') {
            $end_date = $this->data()->buildEndDate();
            $filter['Date:LessThanOrEqual'] = $end_date;
        }
        $items = $this->data()->getEvents($filter, 0);

        return $items->sort(array(
            'Date' => 'ASC',
            'Time' => 'ASC',
        ));
    }
}
