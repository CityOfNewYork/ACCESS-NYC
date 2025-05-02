export default class Notification {
    constructor(element,closeButtonID,divToCloseID) {
        this.element = element;
        this.closeButton = element.querySelector('#'+closeButtonID);
        this.divToClose = element.querySelector('#'+divToCloseID);
    }

    init() {
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => this.close());
        }
    }

    close() {
        if (this.divToClose) {
            this.divToClose.remove();
        }
    }
}
