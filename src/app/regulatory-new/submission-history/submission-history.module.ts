import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { DossierQueueModule } from '../shared/dossier-queue/dossier-queue.module';
import { SubmissionHistoryComponent } from './submission-history.component';

const routes: Routes = [{ path: '', component: SubmissionHistoryComponent }];

@NgModule({
  declarations: [SubmissionHistoryComponent],
  imports: [CommonModule, TranslateModule, DossierQueueModule, RouterModule.forChild(routes)],
})
export class SubmissionHistoryModule {}
