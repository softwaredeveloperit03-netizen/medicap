import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { LogComponent } from './log/log.component';
import { ApprovalComponent } from './approval/approval.component';
import { CheckingComponent } from './checking/checking.component';
import { TestingComponent } from './testing/testing.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'testing', component: TestingComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'approval', component: ApprovalComponent},
  { path: 'log', component: LogComponent},
];

@NgModule({
  declarations: [
    LogComponent,
    ApprovalComponent,
    CheckingComponent,
    TestingComponent,
    DashboardComponent,
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TestModule { }
