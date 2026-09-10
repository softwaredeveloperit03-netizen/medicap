import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { HttpClientModule } from '@angular/common/http';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';

import { BatchReleaseRoutingModule } from './batchrelease-routing.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewBatchComponent } from './newbatch/newbatch.component';
import { BatchReleaseRecordComponent } from './record/record.component';
import { ChecklistApproveComponent } from './checklist-approve/checklist-approve.component';
import { DiscripancyComponent } from './discripancy/discripancy.component';
import { NewChecklistComponent } from './new-checklist/new-checklist.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { ReviseChecklistComponent } from './revise-checklist/revise-checklist.component';
import { TranslateModule } from '@ngx-translate/core';



@NgModule({
  declarations: [
    DashboardComponent,
    NewBatchComponent,
    BatchReleaseRecordComponent,
    ChecklistApproveComponent,
    DiscripancyComponent,
    NewChecklistComponent,
    ChecklistComponent,
    ReviseChecklistComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    HttpClientModule,
    BatchReleaseRoutingModule
  ],
})
export class BatchReleaseModule { }
