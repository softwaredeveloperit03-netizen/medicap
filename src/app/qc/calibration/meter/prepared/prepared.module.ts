import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PrepareComponent } from './prepare/prepare.component';
import { CheckingComponent } from './checking/checking.component';
import { LogComponent } from './log/log.component';
import { NewComponent } from './new/new.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';

import { RouterModule, Routes } from '@angular/router'


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'checking', component: CheckingComponent},
  { path: 'log', component: LogComponent},
  { path: 'new', component: NewComponent},
  { path: 'prepare', component: PrepareComponent},
];
@NgModule({
  declarations: [
    PrepareComponent,
    CheckingComponent,
    LogComponent,
    DashboardComponent,
    NewComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PreparedModule { }
