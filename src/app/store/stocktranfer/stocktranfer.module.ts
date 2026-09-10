import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NewreqComponent } from './newreq/newreq.component';
import { ReqlogComponent } from './reqlog/reqlog.component';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { DropdownModule } from 'primeng/dropdown';
import { RouterModule, Routes } from '@angular/router';
import { StockTransferComponent } from './stock-transfer/stock-transfer.component';
import { ReceivingComponent } from './receiving/receiving.component';
import { TranslateModule } from '@ngx-translate/core';




const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'newreq', component: NewreqComponent},
  { path: 'reqlog', component: ReqlogComponent},
  { path: 'stock-transfer', component: StockTransferComponent},
  { path: 'receiving', component: ReceivingComponent},

  ]




@NgModule({
  declarations: [
    DashboardComponent,
    NewreqComponent,
    ReqlogComponent,
    StockTransferComponent,
    ReceivingComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    AutoCompleteModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class StocktranferModule { }
