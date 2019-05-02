<?php

declare(strict_types=1);

namespace App;

use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientBuilder;
use Akeneo\PimEnterprise\ApiClient\AkeneoPimEnterpriseClientInterface;

/**
 * @author    Samir Boulil <samir.boulil@akeneo.com>
 * @copyright 2019 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ApiClientFactory
{
    /** @var string */
    private $uri;

    /** @var string */
    private $clientId;

    /** @var string */
    private $clientSecret;

    /** @var string */
    private $username;

    /** @var string */
    private $password;

    public function __construct(string $uri, string $clientId, string $clientSecret, string $username, string $password)
    {
        $this->uri = $uri;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->username = $username;
        $this->password = $password;
    }

    public function build(): AkeneoPimEnterpriseClientInterface
    {
        $clientBuilder = new AkeneoPimEnterpriseClientBuilder($this->uri);

        return $clientBuilder->buildAuthenticatedByPassword($this->clientId, $this->clientSecret, $this->username, $this->password);
    }
}
