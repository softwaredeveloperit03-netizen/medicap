import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { BatchReleaseRecordComponent } from './record/record.component';
import { ChecklistApproveComponent } from './checklist-approve/checklist-approve.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { DiscripancyComponent } from './discripancy/discripancy.component';
import { NewBatchComponent } from './newbatch/newbatch.component';
import { NewChecklistComponent } from './new-checklist/new-checklist.component';
import { ChecklistComponent } from './checklist/checklist.component';
import { ReviseChecklistComponent } from './revise-checklist/revise-checklist.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new-checklist', component: NewChecklistComponent},
  { path: 'checklist-log', component: ChecklistComponent},
  { path: 'revise-checklist/:id', component: ReviseChecklistComponent},
  { path: 'new', component: NewBatchComponent},
  { path: 'record', component: BatchReleaseRecordComponent },
  { path: 'checklist-approve', component: ChecklistApproveComponent},
  { path: 'discripancy', component: DiscripancyComponent}
];

@NgModule({
  imports: [ TranslateModule,RouterModule.forChild(routes)],
  exports: [RouterModule]
})
export class BatchReleaseRoutingModule { }
