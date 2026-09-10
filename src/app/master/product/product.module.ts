import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { MasterExcelModule } from 'src/app/shared/master-excel/master-excel.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewComponent } from './new/new.component';
import {MultiSelectModule} from 'primeng/multiselect';
import { NewProductFormulationComponent } from './new-product-formulation/new-product-formulation.component';
import { LitProductFormulationComponent } from './lit-product-formulation/lit-product-formulation.component';
import { ChangemrphistoryComponent} from  './changemrphistory/changemrphistory.component'
const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'new', component: NewComponent},
  { path: 'new-formulation', component: NewProductFormulationComponent},
  { path: 'new-formulation-list', component: LitProductFormulationComponent},
  { path: 'changemrp', component: ChangemrphistoryComponent},

];

@NgModule({
  declarations: [
    DashboardComponent,
    NewComponent,
    NewProductFormulationComponent,
    LitProductFormulationComponent,
    ChangemrphistoryComponent,
  ],
  imports: [
    SharedModule,
    MasterExcelModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    ReactiveFormsModule,
    MultiSelectModule,
    RouterModule.forChild(routes)
  ]
})
export class ProductModule { }
