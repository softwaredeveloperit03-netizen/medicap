import { Component } from '@angular/core';

@Component({
  selector: 'app-dossier-complaints',
  template: `<app-dossier-queue title="Dossier Compilation" queue="compilation" closeRoute="/regulatory"></app-dossier-queue>`,
})
export class DossierComplaintsComponent {}
