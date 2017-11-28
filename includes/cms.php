<?php

class CmsApi {
    const BASE_URL = 'https://cms.union.rpi.edu/api/';

    protected static function executeAPIGet($endpoint) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL,static::BASE_URL . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Token ' . getenv("CMS_API_TOKEN")));
        curl_setopt($ch, CURLOPT_CAINFO, 'cacert.pem');

        return json_decode(curl_exec($ch), true);
    }

    public static function getAllUsers($organizationId) {
        return static::executeAPIGet("organizations/getAllUsers/$organizationId/");
    }

    public static function viewRcs($rcsId) {
        return static::executeAPIGet("users/view_rcs/$rcsId/");
    }
}