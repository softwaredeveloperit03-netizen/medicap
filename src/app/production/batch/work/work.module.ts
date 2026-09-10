import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { AwiatingComponent } from './awiating/awiating.component';
import { LogComponent } from './log/log.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ChangeComponent } from './change/change.component';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'awaiting', component: AwiatingComponent},
  { path: 'log', component: LogComponent},
  { path: 'change', component: ChangeComponent}
];

@NgModule({
  declarations: [DashboardComponent, AwiatingComponent, LogComponent, ChangeComponent],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class WorkModule { }
