import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ApprovalComponent } from './approval/approval.component';
import { NewComponent } from './new/new.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MasterExcelModule } from 'src/app/shared/master-excel/master-excel.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import {MultiSelectModule} from 'primeng/multiselect';
import { TranslateModule } from '@ngx-translate/core';

 
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
 ];

@NgModule({
  declarations: [DashboardComponent, NewComponent,ApprovalComponent],
  imports: [
    SharedModule,
    MasterExcelModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class EquipmentsModule { }
