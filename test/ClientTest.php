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

namespace OpusTest\Doi;

use Opus\Doi\Client;
use Opus\Doi\ClientException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase {

    const dataCiteUserName = 'test';

    const dataCitePassword = 'secret';

    const sampleIpAddress = '192.0.2.1';

    public function testConstructorWithEmptyConfig() {
        $config = new \Zend_Config(array());

        $exception = null;
        try {
            new Client($config);
        }
        catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithPartialConfig1() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array('username' => 'doe')))));

        $exception = null;
        try {
            new Client($config);
        }
        catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithPartialConfig2() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => 'doe',
                            'password' => 'secret')))));

        $exception = null;
        try {
            new Client($config);
        }
        catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithPartialConfig3() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => 'doe',
                            'serviceUrl' => 'http://' . self::sampleIpAddress)))));

        $exception = null;
        try {
            new Client($config);
        }
        catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithFullConfig() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => 'doe',
                            'password' => 'secret',
                            'serviceUrl' => 'http://' . self::sampleIpAddress)))));

        $exception = null;
        try {
            new Client($config);
        }
        catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function testRegisterDOI() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => 'doe',
                            'password' => 'secret',
                            'serviceUrl' => 'http://' . self::sampleIpAddress)))));

        $client = new Client($config);
        $this->setExpectedException('Opus\Doi\ClientException');
        $client->registerDOI('10.5072/opustest-999', '', 'http://localhost/opus4/frontdoor/index/index/999');
    }

    public function testRegisterDOIWithDataCiteTestAccount() {

        $this->markTestSkipped('Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)');

        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => self::dataCiteUserName,
                            'password' => self::dataCitePassword,
                            'serviceUrl' => 'https://mds.test.datacite.org')))));

        $client = new Client($config);
        $xmlStr = '<?xml version="1.0" encoding="utf-8"?>
<resource xmlns="http://datacite.org/schema/kernel-4" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://datacite.org/schema/kernel-4 http://schema.datacite.org/meta/kernel-4/metadata.xsd">
<identifier identifierType="DOI">10.5072/opustest-999</identifier>
<creators>
<creator>
<creatorName>Doe, John</creatorName>
<givenName>John</givenName>
<familyName>Doe</familyName>
</creator>
</creators>
<titles>
<title xml:lang="en">Document without meaningful title</title>
</titles>
<publisher>ACME corp</publisher>
<publicationYear>2018</publicationYear>
<resourceType resourceTypeGeneral="Text">Book</resourceType>
<dates><date dateType="Created">2018-03-25</date></dates>
</resource>';

        $client->registerDOI('10.5072/opustest-999', $xmlStr, 'http://localhost/opus4/frontdoor/index/index/999');
    }

    public function testCheckDOI() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => 'doe',
                            'password' => 'secret',
                            'serviceUrl' => 'http://' . self::sampleIpAddress)))));

        $client = new Client($config);
        $this->setExpectedException('Opus\Doi\ClientException');
        $result = $client->checkDOI('10.5072/opustest-999', 'http://localhost/opus4/frontdoor/index/index/99');
        $this->assertFalse($result);
    }

    public function testCheckDOIWithDataCiteTestAccount() {

        $this->markTestSkipped('Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)');

        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => self::dataCiteUserName,
                            'password' => self::dataCitePassword,
                            'serviceUrl' => 'https://mds.test.datacite.org')))));

        $client = new Client($config);
        $result = $client->checkDOI('10.5072/opustest-999', 'http://localhost/opus4/frontdoor/index/index/999');
        $this->assertTrue($result);

        $result = $client->checkDOI('10.5072/opustest-999', 'http://localhost/opus4/frontdoor/index/index/111');
        $this->assertFalse($result);
    }

    public function testUpdateURLforDOI() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => 'doe',
                            'password' => 'secret',
                            'serviceUrl' => 'http://' . self::sampleIpAddress)))));

        $client = new Client($config);
        $this->setExpectedException('Opus\Doi\ClientException');
        $client->updateURLforDOI('10.5072/opustest-999', 'http://localhost/opus5/frontdoor/index/index/999');
    }

    public function testUpdateURLforDOIWithDataCiteTestAccount() {

        $this->markTestSkipped('Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)');

        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => self::dataCiteUserName,
                            'password' => self::dataCitePassword,
                            'serviceUrl' => 'https://mds.test.datacite.org')))));

        $client = new Client($config);
        $client->updateURLforDOI('10.5072/opustest-999', 'http://localhost/opus5/frontdoor/index/index/999');
        $result = $client->checkDOI('10.5072/opustest-999', 'http://localhost/opus5/frontdoor/index/index/999');
        $this->assertTrue($result);
    }

    public function testDeleteMetadataForDoi() {
        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => 'doe',
                            'password' => 'secret',
                            'serviceUrl' => 'http://' . self::sampleIpAddress)))));

        $client = new Client($config);
        $this->setExpectedException('Opus\Doi\ClientException');
        $client->deleteMetadataForDoi('10.5072/opustest-999');
    }

    public function testDeleteMetadataForDoiWithDataCiteTestAccount() {

        $this->markTestSkipped('Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)');

        $config = new \Zend_Config(
            array('doi' =>
                array('registration' =>
                    array('datacite' =>
                        array(
                            'username' => self::dataCiteUserName,
                            'password' => self::dataCitePassword,
                            'serviceUrl' => 'https://mds.test.datacite.org')))));

        $client = new Client($config);
        $client->deleteMetadataForDoi('10.5072/opustest-999');
    }
}