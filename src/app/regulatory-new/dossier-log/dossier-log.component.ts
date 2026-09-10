import { Component } from '@angular/core';

@Component({
  selector: 'app-dossier-log-page',
  template: `<app-dossier-queue title="Dossier Log" queue="log" closeRoute="/regulatory"></app-dossier-queue>`,
})
export class DossierLogPageComponent {}
