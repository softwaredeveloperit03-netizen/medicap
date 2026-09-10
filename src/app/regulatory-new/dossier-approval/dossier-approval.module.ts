import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { DossierQueueModule } from '../shared/dossier-queue/dossier-queue.module';
import { DossierApprovalPageComponent } from './dossier-approval.component';

const routes: Routes = [{ path: '', component: DossierApprovalPageComponent }];

@NgModule({
  declarations: [DossierApprovalPageComponent],
  imports: [CommonModule, TranslateModule, DossierQueueModule, RouterModule.forChild(routes)],
})
export class DossierApprovalModule {}
