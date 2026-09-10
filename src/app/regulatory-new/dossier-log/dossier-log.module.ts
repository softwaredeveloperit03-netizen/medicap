import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { DossierQueueModule } from '../shared/dossier-queue/dossier-queue.module';
import { DossierLogPageComponent } from './dossier-log.component';

const routes: Routes = [{ path: '', component: DossierLogPageComponent }];

@NgModule({
  declarations: [DossierLogPageComponent],
  imports: [CommonModule, TranslateModule, DossierQueueModule, RouterModule.forChild(routes)],
})
export class DossierLogModule {}
