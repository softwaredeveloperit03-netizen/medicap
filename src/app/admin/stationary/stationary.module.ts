import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { ConsumptionComponent } from './consumption/consumption.component';
import { StationaryComponent } from './stationary/stationary.component';
import { StockRegisterComponent } from './stock-register/stock-register.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'consumption', component: ConsumptionComponent},
  { path: 'stationary', component: StationaryComponent},
  { path: 'stcok-register', component: StockRegisterComponent}
];

@NgModule({
  declarations: [
    DashboardComponent,
    ConsumptionComponent,
    StationaryComponent,
    StockRegisterComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class StationaryModule { }
