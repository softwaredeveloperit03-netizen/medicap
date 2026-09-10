import { NgModule } from '@angular/core';
import { CommonModule,DatePipe  } from '@angular/common';

import { DashboardComponent } from './dashboard/dashboard.component';
import { DirectComponent } from './direct/direct.component';
import { EditComponent } from './edit/edit.component';
import { LogComponent } from './log/log.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { RightsComponent } from './rights/rights.component';
import { ListComponent } from './list/list.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { EditEmpFormComponent } from './edit-emp-form/edit-emp-form.component';
import { RightlogComponent } from './rightlog/rightlog.component';
import { IncrementComponent } from './increment/increment.component';
import { DeptchangeComponent } from './deptchange/deptchange.component';
import { ChangedepdashComponent } from './changedepdash/changedepdash.component';
import { UpdaterightsComponent } from './updaterights/updaterights.component';
import { ResignedComponent } from './resigned/resigned.component';
import { AdditionalComponent } from './additional/additional.component';
import { AnextureComponent } from './anexture/anexture.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'direct', component: DirectComponent},
  { path: 'list', component: ListComponent},
  { path: 'increament', component: IncrementComponent},
  { path: 'change_dept_dash', component: ChangedepdashComponent},
  { path: 'log', component: LogComponent},
  { path: 'edit', component: EditComponent},
  { path: 'edit_form', component: EditEmpFormComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'rights', component: RightsComponent},
  { path: 'deptChange', component: DeptchangeComponent},
  { path: 'update_rights', component: UpdaterightsComponent},
  { path: 'rights-log', component: RightlogComponent},
  { path: 'resigned', component: ResignedComponent},
  { path: 'additional', component: AdditionalComponent},
  { path: 'annexure', component: AnextureComponent},
  { path: 'interview', loadChildren: () => import('./interview/interview.module').then(m=>m.InterviewModule), data: {preload: false}},
  { path: 'leaves', loadChildren: () => import('./leaves/leaves.module').then(m=>m.LeavesModule), data: {preload: false}},


];

@NgModule({
  declarations: [DashboardComponent, DirectComponent,EditComponent, LogComponent,
     ApprovalComponent, RightsComponent, ListComponent, EditEmpFormComponent, RightlogComponent ,
      IncrementComponent, DeptchangeComponent, ChangedepdashComponent,UpdaterightsComponent, ResignedComponent, AnextureComponent],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ],
  providers:[DatePipe]
})
export class EmployeesModule { }
