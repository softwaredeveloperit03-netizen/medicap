import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { NewComponent } from './new/new.component';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
];

@NgModule({
  declarations: [DashboardComponent, NewComponent],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class PlanModule { }
