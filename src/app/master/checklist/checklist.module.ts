import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { RevisionComponent } from './revision/revision.component';
import { ApproveComponent } from './approve/approve.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes} from '@angular/router';
import { FormComponent } from './form/form.component';
import { BmrComponent } from './bmr/bmr.component';
import { IpqcComponent } from './ipqc/ipqc.component';
import { FormNewComponent } from './form-new/form-new.component';
import { LogComponent } from './log/log.component';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'revision', component: RevisionComponent},
  { path: 'form', component: FormComponent},
  { path: 'approve', component: ApproveComponent},
  { path: 'bmr', component: BmrComponent},
  { path: 'ipqc', component: IpqcComponent},
  { path: 'checklist-form-new', component: FormNewComponent},
  { path: 'Log', component: LogComponent},

  

]
@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    RevisionComponent,
    FormComponent,
    ApproveComponent,
    BmrComponent,
    IpqcComponent,
    FormNewComponent,
    LogComponent  
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class ChecklistModule { }
