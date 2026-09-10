import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class NotificationService {

  constructor() { }

  requestPermission(): void {
    if ('Notification' in window) {
      Notification.requestPermission().then(permission => {
        console.log('Permission granted:', permission);
      });
    } else {
      console.warn('This browser does not support notifications.');
    }
  }

  showNotification(title: string, options?: NotificationOptions): void {
    console.log('Notification permission:', Notification.permission);

    if ('Notification' in window && Notification.permission === 'granted') {
      console.log("in the service ");
      new Notification(title, options);
    } else {
      console.warn('Notification not shown. Permission not granted.');
    }
  }
}
