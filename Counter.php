<?php

/**
 * Issue counter
 */
class Counter
{
    /**
     * Empty line pattern
     */
    const LINE_DELIMETR = '#\n\s*\n#Uis';

    /**
     * Issue item pattern. E.g. Module:
     */
    const ISSUE_ITEM = '@^(\d|[A-Z]{1}).*?:@m';

    /**
     * Issue array
     *
     * @var array
     */
    protected $issues;

    /**
     * @constructor
     * @param string $source
     */
    public function __construct($source)
    {
        $splitedIssues = preg_split(self::LINE_DELIMETR, $source);
        $this->issues = $this->parseIssues(array_filter($splitedIssues));
    }

    /**
     * Prepare array of issues.
     *
     * @param array $issues
     * @return array
     */
    protected function parseIssues(array $issues)
    {
        $data = [];
        if (!empty($issues)) {
            foreach ($issues as $issueKey => $issue) {
                preg_match_all(self::ISSUE_ITEM, $issue, $items);
                $values = preg_split(self::ISSUE_ITEM, $issue, -1, PREG_SPLIT_NO_EMPTY);
                if (isset($items[0]) && count($items[0]) == count($values)) {
                    foreach ($items[0] as $key => $item) {
                        $data[$issueKey][$item] = $values[$key];
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get each issues count.
     *
     * @return array
     */
    public function getCount()
    {
        $filter['high'] = array_filter($this->issues, [$this, 'filterHigh']);
        $filter['medium'] = array_filter($this->issues, [$this, 'filterMedium']);
        $filter['low'] = array_filter($this->issues, [$this, 'filterLow']);
        $issues = $this->calculateIssues($filter);

        return $issues;
    }

    /**
     * Calculate issues count.
     *
     * @param array $issues
     * @return array
     */
    protected function calculateIssues(array $issues)
    {
        $data = [];
        foreach ($issues as $priority => $issueList) {
            foreach ($issueList as $key => $issue) {
                $data = $this->checkIssuesFormating($issue, $data);

                // Calculate number of issue occurrences:
                $count = $this->calculateOccurrences($issue);

                // Calculating issues by issue priority type.
                @$data['by_priority'][$priority] += $count;

                // Calculating issues by issue type.
                @$data['by_type'][$issue['Type:']] += $count;

            }
        }

        return $data;
    }

    /**
     * Check issue for all necessary fields. Check for fields without data.
     *
     * @param array $issue
     * @param array $data
     * @return array
     */
    protected function checkIssuesFormating(array $issue, array &$data)
    {
        // If issue without location:
        if (!isset($issue['Location:'])) {
            $data['without_location'][] = key($issue);
        }
        if (count(array_keys($issue)) != count(array_filter(array_values($issue), 'trim'))) {
            $data['empty_field'][] = key($issue);
        }

        return $data;
    }

    /**
     * Calculate issue occurrences by location item
     *
     * @param array $issue
     * @return int
     */
    protected function calculateOccurrences(array $issue)
    {
        $count = 0;
        $locations = array_filter(explode("\n", $issue['Location:']));
        foreach ($locations as $location) {
            $occurrences = explode(',', $location);
            $count += count($occurrences);
        }

        return $count;
    }

    /**
     * Callback for filltering high priority issues.
     *
     * @param array $issue
     * @return array
     */
    protected function filterHigh(array $issue)
    {
        return trim($issue['Priority:']) == 'High';
    }

    /**
     * Callback for filltering medium priority issues.
     *
     * @param array $issue
     * @return array
     */
    protected function filterMedium(array $issue)
    {
        return trim($issue['Priority:']) == 'Medium';
    }

    /**
     * Callback for filltering low priority issues.
     *
     * @param array $issue
     * @return array
     */
    protected function filterLow(array $issue)
    {
        return trim($issue['Priority:']) == 'Low';
    }
}
