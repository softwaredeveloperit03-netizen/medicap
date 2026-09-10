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
import { FormNewComponent } from '../form-new/form-new.component';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'revision', component: RevisionComponent},
  { path: 'form', component: FormComponent},
  { path: 'approve', component: ApproveComponent},

]
@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    RevisionComponent,
    FormComponent,
    ApproveComponent  
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
