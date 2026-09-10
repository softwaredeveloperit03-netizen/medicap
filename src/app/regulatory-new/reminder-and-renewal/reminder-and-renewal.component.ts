import { Component } from '@angular/core';

@Component({
  selector: 'app-reminder-and-renewal',
  template: `<app-dossier-queue title="Reminders & Renewals" queue="reminders" closeRoute="/regulatory"></app-dossier-queue>`,
})
export class ReminderAndRenewalComponent {}
