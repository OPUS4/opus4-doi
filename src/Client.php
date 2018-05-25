<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category    Application
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Doi;

class Client {

    private $username;

    private $password;

    private $serviceUrl;

    private $log;

    /**
     * Client constructor.
     *
     * @param $config \Zend_Config
     * @param $log \Zend_Log (optional)
     *
     * @throws ClientException
     */
    public function __construct($config, $log = null) {
        if (isset($config->doi->registration->datacite->username) && $config->doi->registration->datacite->username != '') {
            $this->username = $config->doi->registration->datacite->username;
        }
        if (isset($config->doi->registration->datacite->password) && $config->doi->registration->datacite->password != '') {
            $this->password = $config->doi->registration->datacite->password;
        }
        if (isset($config->doi->registration->datacite->serviceUrl) && $config->doi->registration->datacite->serviceUrl != '') {
            $this->serviceUrl = $config->doi->registration->datacite->serviceUrl;
        }

        if (!is_null($log)) {
            $this->log = $log;
        }

        if (is_null($this->username) || is_null($this->password) || is_null($this->serviceUrl)) {
            $message = 'missing configuration settings to properly initialize DOI client';
            $this->log($message, 'err');
            throw new ClientException($message);
        }
    }

    /**
     * Registriert die übergebene DOI und weist ihr die Metadaten zu, die im übergebenen XML stehen.
     * Achtung: Es kann bis zu 24-72h dauern bis die DOI im Handle-System sichtbar und auflösbar ist.
     *
     * @param $doiValue
     * @param $xmlStr
     * @param $landingPageUrl
     *
     * @throws ClientException
     */
    public function registerDoi($doiValue, $xmlStr, $landingPageUrl) {

        // Schritt 1: Metadaten als XML registrieren
        $response = null;
        $url = $this->serviceUrl . '/metadata';
        try {
            $client = new \Zend_Http_Client($url);
            $client->setAuth($this->username, $this->password);

            $client->setRawData($xmlStr, 'application/xml;charset=UTF-8');
            $response = $client->request(\Zend_Http_Client::POST);
        }
        catch (\Exception $e) {
            $message = 'request to ' . $url . ' failed with ' . $e->getMessage();
            $this->log($message, 'err');
            throw new ClientException($message);
        }

        $this->log('DataCite response status code (expected 201): ' . $response->getStatus());
        $this->log('DataCite response body: ' . $response->getBody());

        // Response Codes
        // 201 Created: operation successful
        // 400 Bad Request: invalid XML, wrong prefix
        // 401 Unauthorised: no login
        // 403 Forbidden: login problem, quota exceeded
        // 415 Wrong Content Type : Not including content type in the header.

        if ($response->getStatus() != 201) {
            $message = 'unexpected DataCite MDS response code ' . $response->getStatus();
            $this->log($message, 'err');
            throw new ClientException($message);
        }

        // Schritt 2: Register the DOI name
        // DOI und URL der Frontdoor des zugehörigen Dokuments übergeben
        $url = $this->serviceUrl . '/doi/' . $doiValue;
        try {
            $client = new \Zend_Http_Client($url);
            $client->setAuth($this->username, $this->password);
            $data = "doi=$doiValue\nurl=" . $landingPageUrl;
            $client->setRawData($data, 'text/plain;charset=UTF-8');
            $response = $client->request(\Zend_Http_Client::PUT);
        }
        catch (\Exception $e) {
            $message = 'request to ' . $url . ' failed with ' . $e->getMessage();
            $this->log($message, 'err');
            throw new ClientException($message);
        }

        // Response Codes
        // 201 Created: operation successful
        // 400 Bad Request: request body must be exactly two lines: DOI and URL; wrong domain, wrong prefix;
        // 401 Unauthorised: no login
        // 403 Forbidden: login problem, quota exceeded
        // 412 Precondition failed: metadata must be uploaded first.

        $this->log('DataCite response status code (expected 201): ' . $response->getStatus());
        $this->log('DataCite response body: ' . $response->getBody());

        if ($response->getStatus() != 201) {
            $message = 'unexpected DataCite MDS response code ' . $response->getStatus();
            $this->log($message, 'err');
            throw new ClientException($message);
        }
    }

    /**
     * DataCite gibt für eine registrierte DOI die hinterlegte Frontdoor-URL zurück (oder eine Fehlermeldung, wenn
     * die DOI nicht vorhanden ist). Diese Methode prüft, dass die übergebene DOI bei DataCite registriert wurde
     * und das die aufgelöste URL mit der Frontdoor-URL des übergebenen Dokuments übereinstimmt. Im Erfolgsfall
     * liefert die Methode true zurück; andernfalls false.
     *
     * Response Status Codes
     * 200 OK: operation successful
     * 204 No Content : DOI is known to DataCite Metadata Store (MDS), but is not minted (or not resolvable e.g. due to handle's latency)
     * 401 Unauthorized: no login
     * 403 Login problem or dataset belongs to another party
     * 404 Not Found: DOI does not exist in our database (e.g. registration pending)
     *
     * @param $doiValue
     * @param $landingPageURL
     *
     * @return bool Methode liefert true zurück, wenn die DOI erfolgreich registiert wurde und die Prüfung positiv ausfällt.
     *
     * @throws ClientException
     *
     */
    public function checkDoi($doiValue, $landingPageURL) {
        $response = null;
        $url = $this->serviceUrl . '/doi/' . $doiValue;
        try {
            $client = new \Zend_Http_Client($url);
            $client->setAuth($this->username, $this->password);
            $response = $client->request(\Zend_Http_Client::GET);
        }
        catch (\Exception $e) {
            $message = 'request to ' . $url . ' failed with ' . $e->getMessage();
            $this->log($message, 'err');
            throw new ClientException($message);
        }

        $statusCode = $response->getStatus();
        // in $body steht die URL zur Frontdoor, die mit der DOI verknüpft wurde
        $body = $response->getBody();

        $this->log('DataCite response status code (expected 200): ' . $statusCode);
        $this->log('DataCite response body (expected ' . $landingPageURL . '): ' . $body);

        return ($statusCode == 200 && $landingPageURL == $body);
    }

    /**
     * Ändert die für eine DOI hinterlegte Frontdoor-URL.
     * Achtung: Es kann bis zu 24-72h dauern bis die Änderung der Frontdoor-URL im Handle-System sichtbar ist.
     *
     * Diese Funktion wird benötigt, wenn sich die Frontdoor-URLs der Dokumente ändern, z.B. wenn sich
     * die Domain der OPUS-Instanz ändert oder wenn sich die Konstruktion von Frontdoor-URLs (Pfadbestandteile)
     * in einer zukünftigen OPUS-Version ändert.
     *
     *
     * @param $doiValue DOI für die die URL zur Landing-Page verändert werden soll
     * @param $newUrl URL der Landing-Page
     *
     * @throws ClientException
     *
     */
    public function updateUrlForDoi($doiValue, $newUrl) {
        $response = null;
        $url = $this->serviceUrl . '/doi/' . $doiValue;
        try {
            $client = new \Zend_Http_Client($url);
            $client->setAuth($this->username, $this->password);
            $data = "doi=$doiValue\nurl=$newUrl";
            $client->setRawData($data, 'text/plain;charset=UTF-8');
            $response = $client->request(\Zend_Http_Client::PUT);
        }
        catch (\Exception $e) {
            $message = 'request to ' . $url . ' failed with ' . $e->getMessage();
            $this->log($message, 'err');
            throw new ClientException($message);
        }

        $this->log('DataCite response status code (expected 201): ' . $response->getStatus());
        $this->log('DataCite response body: ' . $response->getBody());

        if ($response->getStatus() != 201) {
            $message = 'unexpected DataCite MDS response code ' . $response->getStatus();
            $this->log($message, 'err');
            throw new ClientException($message);
        }
    }

    /**
     * Markiert den Datensatz zur übergebenen DOI als inaktiv.
     *
     * @param $doiValue
     *
     * @throws ClientException
     */
    public function deleteMetadataForDoi($doiValue) {
        $response = null;
        $url = $this->serviceUrl . '/metadata/' . $doiValue;
        try {
            $client = new \Zend_Http_Client($url);
            $client->setAuth($this->username, $this->password);
            $response = $client->request(\Zend_Http_Client::DELETE);
        }
        catch (\Exception $e) {
            $message = 'request to ' . $url . ' failed with ' . $e->getMessage();
            $this->log($message, 'err');
            throw new ClientException($message);
        }

        $this->log('DataCite response status code (expected 200): ' . $response->getStatus());

        if ($response->getStatus() != 200) {
            $message = 'unexpected DataCite MDS response code ' . $response->getStatus();
            $this->log($message, 'err');
            throw new ClientException($message);
        }
    }

    private function log($message, $level = 'debug') {
        if (is_null($this->log)) {
            return; // do not log anything
        }
        $this->log->$level($message);
    }

}
