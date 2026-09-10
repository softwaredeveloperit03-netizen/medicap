import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ForplanComponent } from './forplan/forplan.component';
import { LinebookingComponent } from './linebooking/linebooking.component';
import { LineapprovalComponent } from './lineapproval/lineapproval.component';
import { PlanningSharedModule } from '../planning/planning-shared.module';
import { VerifyStockComponent } from '../planning/verify-stock/verify-stock.component';
import { WologComponent } from '../planning/wolog/wolog.component';
import { RmpmbookingComponent } from '../planning/rmpmbooking/rmpmbooking.component';
import { SharedModule } from 'src/app/shared/shared.module';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'Forplan', component: ForplanComponent },
  { path: 'Lineapproval', component: LineapprovalComponent },
  { path: 'Canplan', redirectTo: '/planning/WoPlanningHub/can-plan', pathMatch: 'full' },
  { path: 'VerifyStock', component: VerifyStockComponent },
  { path: 'Wolog', component: WologComponent },
  { path: 'Rmpmbooking', component: RmpmbookingComponent },
];



@NgModule({
  declarations: [
    DashboardComponent,
    ForplanComponent,
    LinebookingComponent,
    LineapprovalComponent,
    VerifyStockComponent,
    WologComponent,
    RmpmbookingComponent,
  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    PlanningSharedModule,
    SharedModule,
    RouterModule.forChild(routes)
  ]
})
 

export class StpModule { }
