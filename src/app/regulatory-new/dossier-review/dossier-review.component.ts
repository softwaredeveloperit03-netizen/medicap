import { Component } from '@angular/core';

@Component({
  selector: 'app-dossier-review-page',
  template: `<app-dossier-queue title="Dossier Review" queue="review" closeRoute="/regulatory"></app-dossier-queue>`,
})
export class DossierReviewPageComponent {}
