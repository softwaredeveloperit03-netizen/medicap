import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { PendingComponent } from './pending/pending.component';
import { PmPlaningComponent } from './pm-planing/pm-planing.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'pending', component: PendingComponent},
  { path: 'pm-pending', component: PmPlaningComponent},
];

@NgModule({
  declarations: [DashboardComponent, PendingComponent, PmPlaningComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class BmrModule { }
