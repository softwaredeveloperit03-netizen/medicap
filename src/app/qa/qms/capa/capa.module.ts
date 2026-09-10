import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { TaskdeptComponent } from './taskdept/taskdept.component';
import { FollowUpComponent } from './follow-up/follow-up.component';
 
const routes: Routes = [
  { path: '', redirectTo: 'dashboard', pathMatch: 'full' },
  { path: 'dashboard', component: DashboardComponent },
  { path: 'new', component: NewComponent },
  { path: 'SSProcedure', component: CheckingComponent },
  { path: 'log', component: LogComponent },
  { path: 'task', component: TaskdeptComponent },
  { path: 'followUp', component: FollowUpComponent },
];
@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    CheckingComponent,
    LogComponent,
    TaskdeptComponent,
    FollowUpComponent,
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes),
  ],
})
export class CapaModule {}
