import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { MasterComponent } from './master/master.component';
import { StockComponent } from './stock/stock.component';
import { PreparationComponent } from './preparation/preparation.component';
import { ConsumptionComponent } from './consumption/consumption.component';
import { ReceivingComponent } from './receiving/receiving.component';
import { IssuanceComponent } from './issuance/issuance.component';
import { ParameterComponent } from './parameter/parameter.component';
import { SterilizationComponent } from './sterilization/sterilization.component';
import { Stock1Component } from './stock1/stock1.component';
import { DisposalComponent } from './disposal/disposal.component';
import { CorrectionComponent } from './correction/correction.component';
import { NewreceiveComponent } from './newreceive/newreceive.component';
import { StockbookComponent } from './stockbook/stockbook.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'master', component: MasterComponent},
  { path: 'stock', component: StockComponent},
  { path: 'preparation', component: PreparationComponent},
  { path: 'stock1', component: Stock1Component},
  { path: 'consumption', component: ConsumptionComponent},
  { path: 'receiving', component: ReceivingComponent},
  { path: 'correction', component: CorrectionComponent},
  { path: 'issuance', component: IssuanceComponent},
  { path: 'parameter', component: ParameterComponent},
  { path: 'sterilization', component: SterilizationComponent},
  { path: 'disposal', component: DisposalComponent},
  { path: 'newreceive', component: NewreceiveComponent},
  { path: 'stockbook', component: StockbookComponent},
  { path : 'growth',loadChildren:()=>import('./growth/growth.module').then(m =>m.GrowthModule),data:{preload:false}},
  { path : 'decontamination',loadChildren:()=>import('./decontamination/decontamination.module').then(m =>m.DecontaminationModule),data:{preload:false}},
];

@NgModule({
  declarations: [
    DashboardComponent,
    MasterComponent,
    StockComponent,
    PreparationComponent,
    ConsumptionComponent,
    ReceivingComponent,
    IssuanceComponent,
    ParameterComponent,
    SterilizationComponent,
    Stock1Component,
    DisposalComponent,
    CorrectionComponent,
    NewreceiveComponent,
    StockbookComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class MediaModule { }
