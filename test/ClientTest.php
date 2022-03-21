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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018-2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Doi;

use Laminas\Config\Config;
use Opus\Doi\Client;
use Opus\Doi\ClientException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    const DATACITE_USERNAME = 'test';

    const DATACITE_PASSWORD = 'secret';

    const SAMPLE_IP_ADDRESS = '192.0.2.1';

    public function testConstructorWithEmptyConfig()
    {
        $config = new Config([]);

        $exception = null;
        try {
            new Client($config);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithPartialConfig1()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe']
                ]
            ]
        ]);

        $exception = null;
        try {
            new Client($config);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithPartialConfig2()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe',
                        'password' => 'secret'
                    ]
                ]
            ]
        ]);

        $exception = null;
        try {
            new Client($config);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithPartialConfig3()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe',
                        'serviceUrl' => 'http://' . self::SAMPLE_IP_ADDRESS
                    ]
                ]
            ]
        ]);

        $exception = null;
        try {
            new Client($config);
        } catch (\Exception $e) {
            $exception = $e;
        }
        $this->assertTrue($exception instanceof ClientException, get_class($exception));
    }

    public function testConstructorWithFullConfig()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe',
                        'password' => 'secret',
                        'serviceUrl' => 'http://' . self::SAMPLE_IP_ADDRESS
                    ]
                ]
            ]
        ]);

        $exception = null;
        try {
            new Client($config);
        } catch (\Exception $e) {
            $exception = $e;
        }

        $this->assertNull($exception);
    }

    public function testRegisterDoi()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe',
                        'password' => 'secret',
                        'serviceUrl' => 'http://' . self::SAMPLE_IP_ADDRESS
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $this->expectException(ClientException::class);
        $client->registerDoi(
            '10.5072/opustest-999',
            '',
            'http://localhost/opus4/frontdoor/index/index/999'
        );
    }

    public function testRegisterDoiWithDataCiteTestAccount()
    {
        $this->markTestSkipped(
            'Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)'
        );

        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => self::DATACITE_USERNAME,
                        'password' => self::DATACITE_PASSWORD,
                        'serviceUrl' => 'https://mds.test.datacite.org'
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $xmlStr = <<<STRING
<?xml version="1.0" encoding="utf-8"?>
<resource xmlns="http://datacite.org/schema/kernel-4" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
xsi:schemaLocation="http://datacite.org/schema/kernel-4 http://schema.datacite.org/meta/kernel-4/metadata.xsd">
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
</resource>
STRING;

        $client->registerDoi(
            '10.5072/opustest-999',
            $xmlStr,
            'http://localhost/opus4/frontdoor/index/index/999'
        );
    }

    public function testCheckDoi()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe',
                        'password' => 'secret',
                        'serviceUrl' => 'http://' . self::SAMPLE_IP_ADDRESS
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $this->expectException(ClientException::class);
        $result = $client->checkDoi(
            '10.5072/opustest-999',
            'http://localhost/opus4/frontdoor/index/index/99'
        );
        $this->assertFalse($result);
    }

    public function testCheckDoiWithDataCiteTestAccount()
    {
        $this->markTestSkipped(
            'Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)'
        );

        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => self::DATACITE_USERNAME,
                        'password' => self::DATACITE_PASSWORD,
                        'serviceUrl' => 'https://mds.test.datacite.org'
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $result = $client->checkDoi(
            '10.5072/opustest-999',
            'http://localhost/opus4/frontdoor/index/index/999'
        );
        $this->assertTrue($result);

        $result = $client->checkDoi(
            '10.5072/opustest-999',
            'http://localhost/opus4/frontdoor/index/index/111'
        );
        $this->assertFalse($result);
    }

    public function testUpdateUrlForDoi()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe',
                        'password' => 'secret',
                        'serviceUrl' => 'http://' . self::SAMPLE_IP_ADDRESS
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $this->expectException(ClientException::class);
        $client->updateUrlForDoi('10.5072/opustest-999', 'http://localhost/opus5/frontdoor/index/index/999');
    }

    public function testUpdateUrlForDoiWithDataCiteTestAccount()
    {
        $this->markTestSkipped(
            'Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)'
        );

        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => self::DATACITE_USERNAME,
                        'password' => self::DATACITE_PASSWORD,
                        'serviceUrl' => 'https://mds.test.datacite.org'
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $client->updateUrlForDoi('10.5072/opustest-999', 'http://localhost/opus5/frontdoor/index/index/999');
        $result = $client->checkDoi(
            '10.5072/opustest-999',
            'http://localhost/opus5/frontdoor/index/index/999'
        );
        $this->assertTrue($result);
    }

    public function testDeleteMetadataForDoi()
    {
        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => 'doe',
                        'password' => 'secret',
                        'serviceUrl' => 'http://' . self::SAMPLE_IP_ADDRESS
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $this->expectException(ClientException::class);
        $client->deleteMetadataForDoi('10.5072/opustest-999');
    }

    public function testDeleteMetadataForDoiWithDataCiteTestAccount()
    {
        $this->markTestSkipped(
            'Test kann nur manuell gestartet werden (Zugangsdaten zum MDS-Testservice von DataCite erforderlich)'
        );

        $config = new Config([
            'doi' => [
                'registration' => [
                    'datacite' => [
                        'username' => self::DATACITE_USERNAME,
                        'password' => self::DATACITE_PASSWORD,
                        'serviceUrl' => 'https://mds.test.datacite.org'
                    ]
                ]
            ]
        ]);

        $client = new Client($config);
        $client->deleteMetadataForDoi('10.5072/opustest-999');
    }
}
