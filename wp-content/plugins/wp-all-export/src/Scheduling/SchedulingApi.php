<?php

namespace Wpae\Scheduling;


use Wpae\Scheduling\Exception\SchedulingHttpException;

class SchedulingApi
{
    private $apiUrl;

    public function __construct($apiUrl)
    {
        $this->apiUrl = $apiUrl;
    }

    public function checkConnection()
    {
        if (is_multisite()) {
            $siteUrl = get_site_url(get_current_blog_id());
        } else {
            $siteUrl = get_site_url();
        }

        $pingBackUrl = $this->getApiUrl('connection') . '?url=' . urlencode($siteUrl);

        $response = wp_remote_request(
            $pingBackUrl,
            array(
                'method' => 'GET'
            )
        );

        if ($response instanceof \WP_Error) {
            return false;
        }

        if ($response['response']['code'] == 200) {
            return true;
        } else {
            return false;
        }
    }

    public function getSchedules($elementId, $elementType)
    {
        $response = wp_remote_request(

            $this->getApiUrl('schedules?forElement=' . $elementId .
                '&type=' . $elementType .
                '&endpoint=' . urlencode(get_site_url())),
            array(
                'method' => 'GET',
                'headers' => $this->getHeaders()
            )
        );

        if ($response instanceof \WP_Error) {
            return false;
        }

        return json_decode($response['body']);
    }

    public function getSchedule($scheduleId)
    {
        wp_remote_request(

            $this->getApiUrl('schedules/' . $scheduleId),
            array(
                'method' => 'GET',
                'headers' => $this->getHeaders()
            )
        );
    }

    public function createSchedule($scheduleData)
    {

        $response = wp_remote_request(
            $this->getApiUrl('schedules'),
            array(
                'method' => 'PUT',
                'headers' => $this->getHeaders(),
                'body' => json_encode($scheduleData)
            )
        );

        if ($response instanceof \WP_Error) {
            throw new SchedulingHttpException('There was a problem saving the schedule');
        }

        return $response;
    }

    public function deleteSchedule($remoteScheduleId)
    {

        wp_remote_request(
            $this->getApiUrl('schedules/' . $remoteScheduleId),
            array(
                'method' => 'DELETE',
                'headers' => $this->getHeaders()
            )
        );
    }

    public function disableSchedule($remoteScheduleId)
    {
        wp_remote_request(
            $this->getApiUrl('schedules/' . $remoteScheduleId . '/disable'),
            array(
                'method' => 'DELETE',
                'headers' => $this->getHeaders()
            )
        );
    }


    public function enableSchedule($scheduleId)
    {
        wp_remote_request(
            $this->getApiUrl('schedules/' . $scheduleId . '/enable'),
            array(
                'method' => 'POST',
                'headers' => $this->getHeaders()
            )
        );
    }


    public function updateSchedule($scheduleId, $scheduleTime)
    {

        $response = wp_remote_request(
            $this->getApiUrl('schedules/' . $scheduleId),
            array(
                'method' => 'POST',
                'headers' => $this->getHeaders(),
                'body' => json_encode($scheduleTime)
            ));

        if ($response instanceof \WP_Error) {
            throw new SchedulingHttpException('There was a problem saving the schedule');
        }

        return $response;
    }

    private function getHeaders()
    {

        $options = \PMXE_Plugin::getInstance()->getOption();

        if (!empty($options['scheduling_license'])) {
            return array(
                'Authorization' => 'License ' . \PMXE_Plugin::decode($options['scheduling_license'])
            );
        } else {
            //TODO: Throw custom exception
            throw new \Exception('No license present');
        }
    }

    /**
     * @return string
     */
    private function getApiUrl($resource)
    {
        return $this->apiUrl . '/' . $resource;
    }
}