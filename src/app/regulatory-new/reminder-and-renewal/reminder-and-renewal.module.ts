import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { DossierQueueModule } from '../shared/dossier-queue/dossier-queue.module';
import { ReminderAndRenewalComponent } from './reminder-and-renewal.component';

const routes: Routes = [{ path: '', component: ReminderAndRenewalComponent }];

@NgModule({
  declarations: [ReminderAndRenewalComponent],
  imports: [CommonModule, TranslateModule, DossierQueueModule, RouterModule.forChild(routes)],
})
export class ReminderAndRenewalModule {}
