import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { DossierQueueComponent } from './dossier-queue.component';

@NgModule({
  declarations: [DossierQueueComponent],
  imports: [CommonModule, FormsModule, RouterModule, ClarityModule, TranslateModule, SharedModule],
  exports: [DossierQueueComponent],
})
export class DossierQueueModule {}
