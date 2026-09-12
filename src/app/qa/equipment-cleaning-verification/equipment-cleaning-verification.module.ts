import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { DropdownModule } from 'primeng/dropdown';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { ApprovalNewComponent } from './approval/approval-new.component';
import { ApprovalLogComponent } from './approval/approval-log.component';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'log', component: LogComponent },
  { path: 'approval/new', component: ApprovalNewComponent },
  { path: 'approval/log', component: ApprovalLogComponent },
];

@NgModule({
  declarations: [DashboardComponent, NewComponent, LogComponent, ApprovalNewComponent, ApprovalLogComponent],
  imports: [
    SharedModule,
    TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DropdownModule,
    RouterModule.forChild(routes),
  ],
})
export class EquipmentCleaningVerificationModule {}
