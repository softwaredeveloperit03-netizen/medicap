import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { DossierQueueModule } from '../shared/dossier-queue/dossier-queue.module';
import { DossierComplaintsComponent } from './dossier-complaints.component';

const routes: Routes = [{ path: '', component: DossierComplaintsComponent }];

@NgModule({
  declarations: [DossierComplaintsComponent],
  imports: [CommonModule, TranslateModule, DossierQueueModule, RouterModule.forChild(routes)],
})
export class DossierComplaintsModule {}
