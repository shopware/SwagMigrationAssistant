let notificationId = null;

Shopware.WorkerNotification.register('newsletterRecipientTask', {
    name: 'SwagMigrationAssistant\\Migration\\MessageQueue\\Message\\ProcessMediaMessage',
    fn: onMediaProcessingMessageFound,
});

function onMediaProcessingMessageFound(next, { $root, entry, notification }) {
    const mediaFileCount = entry.size * 5;

    // Create notification config object
    const config = {
        title: $root.$t('swag-migration.worker-listener.mediaProcessing.title'),
        message: $root.$tc(
            'swag-migration.worker-listener.mediaProcessing.message',
            mediaFileCount,
        ),
        variant: 'info',
        metadata: {
            size: mediaFileCount,
        },
        growl: false,
        isLoading: true,
    };

    // Create new notification
    if (mediaFileCount && notificationId === null) {
        notification.create(config).then((uuid) => {
            notificationId = uuid;
        });
        next();
    }

    // Update existing notification
    if (notificationId !== null) {
        config.uuid = notificationId;

        if (mediaFileCount === 0) {
            config.title = $root.$t(
                'swag-migration.worker-listener.mediaProcessing.titleSuccess',
            );
            config.message = $root.$t(
                'swag-migration.worker-listener.mediaProcessing.messageSuccess',
            );
            config.isLoading = false;
        }
        notification.update(config);
    }

    next();
}
