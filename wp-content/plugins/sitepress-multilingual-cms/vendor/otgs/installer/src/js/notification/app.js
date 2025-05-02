import Notification from './notification';

document.addEventListener('DOMContentLoaded', function() {
    const notificationObj = new Notification(document,'otgs-notification-close','otgs-notification');
    notificationObj.init();
});
