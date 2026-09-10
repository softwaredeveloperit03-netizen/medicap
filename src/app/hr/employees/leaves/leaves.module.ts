import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { LeaveComponent } from './leave/leave.component';
import { CardComponent } from './card/card.component';
import { RecordComponent } from './record/record.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ApprovalComponent } from './approval/approval.component';
import { HandoverComponent } from './handover/handover.component';
import { AlternetComponent } from './alternet/alternet.component';
import { LogComponent } from './log/log.component';
import { SharedModule } from 'src/app/shared/shared.module';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'leave', component: LeaveComponent},
  { path: 'record', component: RecordComponent},
  { path: 'card', component: CardComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'handover', component: HandoverComponent},
  { path: 'alternet', component: AlternetComponent},
  { path: 'log', component: LogComponent},
];

@NgModule({
  declarations: [
    DashboardComponent,LeaveComponent,
    CardComponent,
    RecordComponent,
    ApprovalComponent,
    HandoverComponent,
    AlternetComponent,LogComponent,
  ],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class LeavesModule { }
