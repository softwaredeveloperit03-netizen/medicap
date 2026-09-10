import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { DossierQueueModule } from '../shared/dossier-queue/dossier-queue.module';
import { DossierReviewPageComponent } from './dossier-review.component';

const routes: Routes = [{ path: '', component: DossierReviewPageComponent }];

@NgModule({
  declarations: [DossierReviewPageComponent],
  imports: [CommonModule, TranslateModule, DossierQueueModule, RouterModule.forChild(routes)],
})
export class DossierReviewModule {}
