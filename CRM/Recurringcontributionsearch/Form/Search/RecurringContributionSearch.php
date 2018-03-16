<?php
use CRM_Recurringcontributionsearch_ExtensionUtil as E;

/**
 * A custom contact search
 */
class CRM_Recurringcontributionsearch_Form_Search_RecurringContributionSearch extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  function __construct(&$formValues) {
    parent::__construct($formValues);
  }

  /**
   * Prepare a set of search fields
   *
   * @param CRM_Core_Form $form modifiable
   * @return void
   */
  function buildForm(&$form) {
    CRM_Utils_System::setTitle(E::ts('Recurring Contribution Search'));

    $form->add('text', 'contact_name', ts('Name contains'), TRUE);
		$form->add('select', 'cycle_interval', ts('Frequency'), $this->setCycleIntervalList(), FALSE,
      array('id' => 'cycle_interval', 'multiple' => 'multiple', 'title' => ts('- select -'), 'class' => 'crm-select2'));
		$form->add('select', 'cycle_days', ts('Cycle day(s)'), $this->setCycleDayList(), FALSE,
      array('id' => 'cycle_days', 'multiple' => 'multiple', 'title' => ts('- select -'), 'class' => 'crm-select2'));
    $form->add('select', 'campaign_ids', ts('Campaign(s)'), $this->setCampaignList(), FALSE,
      array('id' => 'campaign_ids', 'multiple' => 'multiple', 'title' => ts('- select -'), 'class' => 'crm-select2'));
    $form->add('select', 'group_ids', ts('Group(s)'), $this->setGroupList(), FALSE,
      array('id' => 'group_ids', 'multiple' => 'multiple', 'title' => ts('- select -'), 'class' => 'crm-select2'));
    $form->add('select', 'tag_ids', ts('Tag(s)'), $this->setTagList(), FALSE,
      array('id' => 'tag_ids', 'multiple' => 'multiple', 'title' => ts('- select -'), 'class' => 'crm-select2'));
    $form->addDate('start_date_from', ts('Start Date from'), FALSE);
    $form->addDate('start_date_to', ts('... to'), FALSE);
    $form->addDate('end_date_from', ts('End Date from'), FALSE);
    $form->addDate('end_date_to', ts('... to'), FALSE);
    $onlyActives = array(
      '1' => ts('Only active recurring contributions'),
      '0' => ts('All recurring contributions'),
    );
    $form->addRadio('only_active', ts('Only active?'), $onlyActives, NULL, '<br />', TRUE);

    // Optionally define default search values
    $form->setDefaults(array(
      'contact_name' => '',
      'only_active' => '1',
    ));

    /**
     * if you are using the standard template, this array tells the template what elements
     * are part of the search criteria
     */
    $form->assign('elements', array(
      'contact_name',
      'campaign_ids',
      'cycle_interval',
      'cycle_days',
      'group_ids',
      'tag_ids',
      'start_date_from',
      'start_date_to',
      'end_date_from',
      'end_date_to',
      'only_active',));
  }

  /**
   * Get a list of summary data points
   *
   * @return mixed; NULL or array with keys:
   *  - summary: string
   *  - total: numeric
   */
  function summary() {
    return NULL;
    // return array(
    //   'summary' => 'This is a summary',
    //   'total' => 50.0,
    // );
  }

  /**
   * Get a list of displayable columns
   *
   * @return array, keys are printable column headers and values are SQL column names
   */
  function &columns() {
    // return by reference
    $columns = array(
      E::ts('Contact Id') => 'contact_id',
      E::ts('Name') => 'display_name',
      E::ts('Contact Type') => 'contact_type',
      E::ts('Campaign') => 'campaign',
      E::ts('Amount') => 'amount',
      E::ts('Cycle day') => 'cycle_day',
      E::ts('Frequency') => 'frequency',
      E::ts('Start date') => 'start_date',
      E::ts('End date') => 'end_date',
    );
    return $columns;
  }

  /**
   * Construct a full SQL query which returns one page worth of results
   *
   * @param int $offset
   * @param int $rowcount
   * @param null $sort
   * @param bool $includeContactIDs
   * @param bool $justIDs
   * @return string, sql
   */
  function all($offset = 0, $rowcount = 0, $sort = NULL, $includeContactIDs = FALSE, $justIDs = FALSE) {
    // delegate to $this->sql(), $this->select(), $this->from(), $this->where(), etc.
    return $this->sql($this->select(), $offset, $rowcount, $sort, $includeContactIDs, NULL);
  }

  /**
   * Construct a SQL SELECT clause
   *
   * @return string, sql fragment with SELECT arguments
   */
  function select() {
    return "
    	contribution_recur.id as id,
      contact_a.id           as contact_id  ,
      contact_a.contact_type as contact_type,
      contact_a.display_name    as display_name,
      campaign.title as campaign,
      contribution_recur.amount as amount,
      contribution_recur.currency as currency,
      contribution_recur.cycle_day as cycle_day,
      CONCAT(contribution_recur.frequency_interval, ' ', contribution_recur.frequency_unit) as frequency,
      contribution_recur.frequency_unit as frequency_unit,
      contribution_recur.frequency_interval as frequency_interval,
      contribution_recur.start_date as start_date,
      contribution_recur.end_date as end_date
    ";
  }

  /**
   * Construct a SQL FROM clause
   *
   * @return string, sql fragment with FROM and JOIN clauses
   */
  function from() {
    $from = "
    	FROM civicrm_contribution_recur contribution_recur
    	INNER JOIN civicrm_contact contact_a ON contact_a.id = contribution_recur.contact_id
    	LEFT JOIN civicrm_campaign campaign ON contribution_recur.campaign_id = campaign.id
    ";
		// add civicrm_group_contact if required
    if (isset($this->_formValues['group_ids']) && !empty($this->_formValues['group_ids'])) {
      $from .= " JOIN civicrm_group_contact gc ON contact_a.id = gc.contact_id";
    }
    // add civicrm_entity_tag if required
    if (isset($this->_formValues['tag_ids']) && !empty($this->_formValues['tag_ids'])) {
      $from .= " JOIN civicrm_entity_tag et ON contact_a.id = et.entity_id AND entity_table = 'civicrm_contact'";
    }
		
		return $from;
  }

  /**
   * Construct a SQL WHERE clause
   *
   * @param bool $includeContactIDs
   * @return string, sql fragment with conditional expressions
   */
  function where($includeContactIDs = FALSE) {
    $where = '';
    $this->_whereClauses = array('(contact_a.is_deleted = %1)');
    $this->_whereParams = array(1 => array(0, 'Integer'));
    $this->_whereIndex = 1;
    $this->addContactNameWhereClause();
    $this->addCampaignWhereClauses();
    $this->addCycleDaysWhereClause();
    $this->addCycleIntervalWhereClause();
    $this->addGroupWhereClauses();
    $this->addTagWhereClauses();
    $this->addStartDateWhereClauses();
    $this->addEndDateWhereClauses();
    $this->addOnlyActiveWhereClause();
    if (!empty($this->_whereClauses)) {
      $where = implode(' AND ', $this->_whereClauses);
    }
    return $this->whereClause($where, $this->_whereParams);
  }
	
	/**
   * Method to set the tag clauses
   */
  private function addTagWhereClauses() {
    if (isset($this->_formValues['tag_ids']) && !empty($this->_formValues['tag_ids'])) {
      foreach ($this->_formValues['tag_ids'] as $tagId) {
        $this->_whereIndex++;
        $tagIds[] = '%'.$this->_whereIndex;
        $this->_whereParams[$this->_whereIndex] = array($tagId, 'Integer');
      }
      if (!empty($tagIds)) {
        $this->_whereClauses[] = '(et.tag_id IN('.implode(', ', $tagIds).'))';
      }
    }
  }

  /**
   * Method to set the group clauses
   */
  private function addGroupWhereClauses() {
    if (isset($this->_formValues['group_ids']) && !empty($this->_formValues['group_ids'])) {
      foreach ($this->_formValues['group_ids'] as $groupId) {
        $this->_whereIndex++;
        $groupIds[] = '%'.$this->_whereIndex;
        $this->_whereParams[$this->_whereIndex] = array($groupId, 'Integer');
      }
      if (!empty($groupIds)) {
        $this->_whereClauses[] = '(gc.group_id IN('.implode(', ', $groupIds).'))';
      }
    }
  }
	
	private function addCycleDaysWhereClause() {
		if (isset($this->_formValues['cycle_days']) && !empty($this->_formValues['cycle_days'])) {
      foreach ($this->_formValues['cycle_days'] as $cycleDay) {
        $this->_whereIndex++;
        $cycleDays[] = '%'.$this->_whereIndex;
        $this->_whereParams[$this->_whereIndex] = array($cycleDay, 'Integer');
      }
      if (!empty($cycleDays)) {
        $this->_whereClauses[] = '(contribution_recur.cycle_day IN('.implode(', ', $cycleDays).'))';
      }
    }
	}
	
	private function addCycleIntervalWhereClause() {
		if (isset($this->_formValues['cycle_interval']) && !empty($this->_formValues['cycle_interval'])) {
			$cycle_intervals = $this->_formValues['cycle_interval'];
    	if (in_array("'12 month'", $cycle_intervals)) {
      	$cycle_intervals[] = "'1 year'"; // the database could have both: '1 year' and '12 month'...
    	}
			$ors = array();
			foreach($cycle_intervals as $interval) {
				$splitted_interval = explode(" ", $interval);
				$this->_whereIndex++;
				$or = '(contribution_recur.frequency_interval = %'.$this->_whereIndex.' AND contribution_recur.frequency_unit = %'.($this->_whereIndex+1).')';
				$ors[] = $or;
				$this->_whereParams[$this->_whereIndex] = array($splitted_interval[0], 'Integer');
				$this->_whereParams[$this->_whereIndex+1] = array($splitted_interval[1], 'String');
				$this->_whereIndex++;
			}
			$this->_whereClauses[] = implode(' OR ', $ors);
		}
	}

  /**
   * Method to set the clause for only active if set
   */
  private function addOnlyActiveWhereClause() {
    if (isset($this->_formValues['only_active']) && $this->_formValues['only_active'] == 1) {
      $this->_whereClauses[] = '(contribution_recur.end_date IS NULL OR contribution_recur.end_date >= CURDATE())';
    }
  }

  /**
   * Method to set the start date clause
   */
  private function addStartDateWhereClauses() {
    if (isset($this->_formValues['start_date_from']) && !empty($this->_formValues['start_date_from'])) {
      $startDateFrom = new DateTime($this->_formValues['start_date_from']);
      $this->_whereIndex++;
      $this->_whereClauses[] = '(contribution_recur.start_date >= %'.$this->_whereIndex. ')';
      $this->_whereParams[$this->_whereIndex] = array($startDateFrom->format('Y-m-d h:i:s'), 'String');
    }
    if (isset($this->_formValues['start_date_to']) && !empty($this->_formValues['start_date_to'])) {
      $startDateTo = new DateTime($this->_formValues['start_date_to']);
      $this->_whereIndex++;
      $this->_whereClauses[] = '(contribution_recur.start_date <= %'.$this->_whereIndex. ')';
      $this->_whereParams[$this->_whereIndex] = array($startDateTo->format('Y-m-d h:i:s'), 'String');
    }
  }

  /**
   * Method to set the end date clause
   */
  private function addEndDateWhereClauses() {
    if (isset($this->_formValues['end_date_from']) && !empty($this->_formValues['end_date_from'])) {
      $endDateFrom = new DateTime($this->_formValues['end_date_from']);
      $this->_whereIndex++;
      $this->_whereClauses[] = '(contribution_recur.end_date >= %'.$this->_whereIndex. ')';
      $this->_whereParams[$this->_whereIndex] = array($endDateFrom->format('Y-m-d h:i:s'), 'String');
    }
    if (isset($this->_formValues['end_date_to']) && !empty($this->_formValues['end_date_to'])) {
      $endDateTo = new DateTime($this->_formValues['end_date_to']);
      $this->_whereIndex++;
      $this->_whereClauses[] = '(contribution_recur.end_date <= %'.$this->_whereIndex. ')';
      $this->_whereParams[$this->_whereIndex] = array($endDateTo->format('Y-m-d h:i:s'), 'String');
    }
  }
	
	/**
   * Method to set the campaign where clauses
   */
  private function addCampaignWhereClauses() {
    if (isset($this->_formValues['campaign_ids']) && !empty($this->_formValues['campaign_ids'])) {
      foreach ($this->_formValues['campaign_ids'] as $campaignId) {
        $this->_whereIndex++;
        $campaignIds[] = '%'.$this->_whereIndex;
        $this->_whereParams[$this->_whereIndex] = array($campaignId, 'Integer');
      }
      if (!empty($campaignIds)) {
        $this->_whereClauses[] = '(contribution_recur.campaign_id IN('.implode(', ', $campaignIds).'))';
      }
    }
  }

  /**
   * Method to set the contact name where clause
   */
  private function addContactNameWhereClause() {
    if (isset($this->_formValues['contact_name']) && !empty($this->_formValues['contact_name'])) {
      $this->_whereIndex++;
      $this->_whereClauses[] = '(contact_a.sort_name LIKE %'.$this->_whereIndex. ')';
      $this->_whereParams[$this->_whereIndex] = array('%'.$this->_formValues['contact_name'].'%', 'String');
    }
  }

  /**
   * Determine the Smarty template for the search screen
   *
   * @return string, template path (findable through Smarty template path)
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/Custom.tpl';
  }

  /**
   * Modify the content of each row
   *
   * @param array $row modifiable SQL result row
   * @return void
   */
  function alterRow(&$row) {
    //$row['frequency'] = $row['frequency_interval'].' '.$row['frequency_unit'];
  }

/**
   * Method to get the group select list
   *
   * @return array
   */
  private function setGroupList() {
    $result = array();
    try {
      $groups = civicrm_api3('Group', 'get', array(
        'is_active' => 1,
        'options' => array('limit' => 0,),
      ));
      foreach ($groups['values'] as $group) {
        $result[$group['id']] = $group['title'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    asort($result);
    return $result;
  }

  /**
   * Method to get the tag select list
   *
   * @return array
   */
  private function setTagList() {
    $result = array();
    try {
      $tags = civicrm_api3('Tag', 'get', array(
        'options' => array('limit' => 0,),
      ));
      foreach ($tags['values'] as $tag) {
        if (strpos($tag['used_for'], 'civicrm_contact') !== FALSE) {
          $result[$tag['id']] = $tag['name'];
        }
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    asort($result);
    return $result;
  }

  /**
   * Method to get the campaign select list
   *
   * @return array
   */
  private function setCampaignList() {
    $result = array();
    try {
      $campaigns = civicrm_api3('Campaign', 'get', array(
        'is_active' => 1,
        'options' => array('limit' => 0,),
      ));
      foreach ($campaigns['values'] as $campaign) {
        $result[$campaign['id']] = $campaign['title'];
      }
    }
    catch (CiviCRM_API3_Exception $ex) {
    }
    asort($result);
    return $result;
  }
	
	private function setCycleDayList() {
		// cycle days
    $cycle_days = array();
    for ($i=1; $i <= 31; $i++) {
      $cycle_days[(string) $i] = (string) $i;  
    }
		return $cycle_days;
	}
	
	private function setCycleIntervalList() {
		// cycle intervals
    $cycle_intervals = array(
      "1 month"  => E::ts('Montly'),
      "2 month"  => E::ts('Every 2 months'),
      "3 month"  => E::ts('Quartly'),
      "4 month"  => E::ts('Every 4 months'),
      "5 month"  => E::ts('Every 5 months'),
      "6 month"  => E::ts('Semi-annually'),
      "7 month"  => E::ts('Every 7 months'),
      "8 month"  => E::ts('Every 8 months'),
      "9 month"  => E::ts('Every 9 months'),
      "10 month"  => E::ts('Every 10 months'),
      "11 month"  => E::ts('Every 11 months'),
      "12 month" => E::ts('Annually')
    );
		return $cycle_intervals;
	}
}
