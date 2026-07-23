<?php

declare(strict_types=1);

namespace orange\dto\sample;

use orange\dto\Dto;
use orange\dto\attributes\Column;
use orange\dto\attributes\FieldName;
use orange\dto\attributes\Label;
use orange\dto\attributes\Table;
use orange\dto\attributes\filters\ToLower;
use orange\dto\attributes\filters\Trim;
use orange\dto\attributes\validations\IsRequired;
use orange\dto\attributes\validations\ValidHexColor;
use orange\dto\attributes\validations\ValidHostname;
use orange\dto\attributes\validations\ValidJson;
use orange\dto\attributes\validations\ValidTimezone;
use orange\dto\attributes\validations\ValidUrl;
use orange\dto\attributes\validations\ValidUuid;

/**
 * An integration/settings payload, exercising the newer format validators:
 * URLs, JSON, timezones, UUIDs, hostnames, and hex colors.
 */
class ApiSettings extends Dto
{
    #[Trim]
    #[IsRequired]
    #[ValidUrl]
    #[FieldName('webhook_url')]
    #[Column('webhook_url')]
    #[Table('api_settings')]
    #[Label('Webhook URL')]
    public protected(set) string $webhookUrl;

    #[IsRequired]
    #[ValidJson]
    #[FieldName('config')]
    #[Column('config')]
    #[Table('api_settings')]
    #[Label('Config')]
    public protected(set) string $config;

    #[Trim]
    #[IsRequired]
    #[ValidTimezone]
    #[Column('timezone')]
    #[Table('api_settings')]
    #[Label('Timezone')]
    public protected(set) string $timezone;

    #[Trim]
    #[IsRequired]
    #[ValidUuid]
    #[FieldName('api_key')]
    #[Column('api_key')]
    #[Table('api_settings')]
    #[Label('API key')]
    public protected(set) string $apiKey;

    #[Trim]
    #[ToLower]
    #[IsRequired]
    #[ValidHostname]
    #[Column('host')]
    #[Table('api_settings')]
    #[Label('Host')]
    public protected(set) string $host;

    #[Trim]
    #[IsRequired]
    #[ValidHexColor]
    #[FieldName('brand_color')]
    #[Column('brand_color')]
    #[Table('api_settings')]
    #[Label('Brand color')]
    public protected(set) string $brandColor;
}
