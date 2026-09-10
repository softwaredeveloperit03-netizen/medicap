import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { VolumetricLogModalsComponent } from './volumetric-log-modals.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MasterExcelModule } from 'src/app/shared/master-excel/master-excel.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    ApprovalComponent,
    LogComponent,
    VolumetricLogModalsComponent,
  ],
  imports: [
    SharedModule,
    MasterExcelModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class VolumetricModule { }
