import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ViewComponent } from './view/view.component';
import { ApprovalComponent } from './approval/approval.component';
import { FormLayoutViewComponent } from './form-layout-view/form-layout-view.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'view/:id', component: ViewComponent },
  { path: 'view-layout/:id', component: FormLayoutViewComponent },
  { path: 'approval', component: ApprovalComponent },
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    ViewComponent,
    ApprovalComponent,
    FormLayoutViewComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    RouterModule.forChild(routes),
  ],
})
export class RmMasterCustomisationModule {}
