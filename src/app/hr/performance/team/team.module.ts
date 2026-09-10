import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
 import { ApprovalComponent } from './approval/approval.component';
import { LogComponent } from './log/log.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { NewteamComponent } from './newteam/newteam.component';
import { TranslateModule } from '@ngx-translate/core';



const routes: Routes = [
  { path: '', component: DashboardComponent},
  {path: 'newteam', component: NewteamComponent},
  {path: 'approve', component: ApprovalComponent},
  {path: 'log', component: LogComponent},
  
];
@NgModule({
  declarations: [
    DashboardComponent,
    NewteamComponent,
    ApprovalComponent,
    LogComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})


export class TeamModule { }