<?php declare(strict_types=1);

namespace SwagMigrationAssistant\Migration\Writer;

use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

class NewsletterRecipientWriter extends AbstractWriter
{
    public function supports(): string
    {
        return DefaultEntities::NEWSLETTER_RECIPIENT;
    }
}
