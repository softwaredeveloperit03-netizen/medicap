import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { FactoryorderComponent } from './factoryorder/factoryorder.component';
import { NewComponent } from './new/new.component';
import { LogComponent } from './log/log.component';
import { MicroComponent } from './micro/micro.component';
import { RecforcastComponent } from './recforcast/recforcast.component';
import { ProdforcastComponent } from './prodforcast/prodforcast.component';
import { PlanmacroComponent } from './planmacro/planmacro.component';
import { ProdlogComponent } from './prodlog/prodlog.component';
import { RequirementlogComponent } from './requirementlog/requirementlog.component';
import { ForecastComponent } from './forecast/forecast.component';
import { YealyforecastComponent } from './yealyforecast/yealyforecast.component';
import { ConcilidateplanComponent } from './concilidateplan/concilidateplan.component';
import { ReconciliationComponent } from './reconciliation/reconciliation.component';
import { MrpStockQtyColsComponent } from './mrp-stock-qty/mrp-stock-qty-cols.component';
import { MrpStockLegendComponent } from './mrp-stock-qty/mrp-stock-legend.component';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'Factoryorder', component: FactoryorderComponent},
  { path: 'New', component: NewComponent},
  { path: 'Reconciliation', component: ReconciliationComponent},
  { path: 'Log', component: LogComponent},
  { path: 'micro', component: MicroComponent},
  { path: 'recforcast', component: RecforcastComponent},
  { path: 'prodforcast', component: ProdforcastComponent},
  { path: 'macro', component: PlanmacroComponent},
  { path: 'Prodlog', component: ProdlogComponent},
  { path: 'Requirementlog', component: RequirementlogComponent},
  { path: 'forecast', component: ForecastComponent},
  { path: 'Yealyforecast', component: YealyforecastComponent},
  { path: 'Concilidateplan', component: ConcilidateplanComponent},
  {path :'forcasting',loadChildren:()=>import('./forcasting/forcasting.module').then(m => m.ForcastingModule),data:{preload : false}},



];
@NgModule({
  declarations: [DashboardComponent,FactoryorderComponent,NewComponent,LogComponent,MicroComponent,RecforcastComponent, ProdforcastComponent,PlanmacroComponent,
     ProdlogComponent, RequirementlogComponent, ForecastComponent, YealyforecastComponent, ConcilidateplanComponent, ReconciliationComponent,
     MrpStockQtyColsComponent, MrpStockLegendComponent],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,

    RouterModule.forChild(routes)
  ]
})
export class MrpModule { }
