import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { CheckingComponent } from './checking/checking.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { CorrectionComponent } from './correction/correction.component';
import { EditComponent } from './edit/edit.component';
import { TranslateModule } from '@ngx-translate/core';

 import { MergesComponent } from './merges/merges.component';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'correction', component: CorrectionComponent},
  { path: 'log', component: LogComponent},
  { path: 'edit', component: EditComponent},
  { path: 'merge', component: MergesComponent},
];

@NgModule({
  declarations: [DashboardComponent, EditComponent, MergesComponent, NewComponent, ApprovalComponent, LogComponent, CheckingComponent, CorrectionComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class RawModule { }
