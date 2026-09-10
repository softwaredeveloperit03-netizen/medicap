import { Component } from '@angular/core';

@Component({
  selector: 'app-dossier-approval-page',
  template: `<app-dossier-queue title="Dossier Approval" queue="approval" closeRoute="/regulatory"></app-dossier-queue>`,
})
export class DossierApprovalPageComponent {}
