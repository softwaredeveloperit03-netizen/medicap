import { Component } from '@angular/core';

@Component({
  selector: 'app-submission-history',
  template: `<app-dossier-queue title="Dossier Calendar" queue="calendar" closeRoute="/regulatory"></app-dossier-queue>`,
})
export class SubmissionHistoryComponent {}
