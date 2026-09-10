import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { SendComponent } from './send/send.component';
import { UploadComponent } from './upload/upload.component';
import { ApprovalComponent } from './approval/approval.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'send', component: SendComponent},
  { path: 'upload', component: UploadComponent},
  { path: 'approval', component: ApprovalComponent}
];

@NgModule({
  declarations: [SendComponent, UploadComponent, ApprovalComponent, DashboardComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PrintingModule { }
