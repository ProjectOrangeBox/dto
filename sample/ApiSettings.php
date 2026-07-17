<?php

declare(strict_types=1);

namespace orange\request\sample;

use orange\request\Request;
use orange\request\attributes\Column;
use orange\request\attributes\FieldName;
use orange\request\attributes\Label;
use orange\request\attributes\Table;
use orange\request\attributes\filters\ToLower;
use orange\request\attributes\filters\Trim;
use orange\request\attributes\validations\IsRequired;
use orange\request\attributes\validations\ValidHexColor;
use orange\request\attributes\validations\ValidHostname;
use orange\request\attributes\validations\ValidJson;
use orange\request\attributes\validations\ValidTimezone;
use orange\request\attributes\validations\ValidUrl;
use orange\request\attributes\validations\ValidUuid;

/**
 * An integration/settings payload, exercising the newer format validators:
 * URLs, JSON, timezones, UUIDs, hostnames, and hex colors.
 */
class ApiSettings extends Request
{
    #[Trim]
    #[IsRequired]
    #[ValidUrl]
    #[FieldName('webhook_url')]
    #[Column('webhook_url')]
    #[Table('api_settings')]
    #[Label('Webhook URL')]
    public string $webhookUrl;

    #[IsRequired]
    #[ValidJson]
    #[FieldName('config')]
    #[Column('config')]
    #[Table('api_settings')]
    #[Label('Config')]
    public string $config;

    #[Trim]
    #[IsRequired]
    #[ValidTimezone]
    #[Column('timezone')]
    #[Table('api_settings')]
    #[Label('Timezone')]
    public string $timezone;

    #[Trim]
    #[IsRequired]
    #[ValidUuid]
    #[FieldName('api_key')]
    #[Column('api_key')]
    #[Table('api_settings')]
    #[Label('API key')]
    public string $apiKey;

    #[Trim]
    #[ToLower]
    #[IsRequired]
    #[ValidHostname]
    #[Column('host')]
    #[Table('api_settings')]
    #[Label('Host')]
    public string $host;

    #[Trim]
    #[IsRequired]
    #[ValidHexColor]
    #[FieldName('brand_color')]
    #[Column('brand_color')]
    #[Table('api_settings')]
    #[Label('Brand color')]
    public string $brandColor;
}
