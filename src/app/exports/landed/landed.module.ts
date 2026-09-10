import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { DatePipe } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { ImportdetailsComponent } from './importdetails/importdetails.component';
import { IntLogisticsComponent } from './int-logistics/int-logistics.component';
import { CustomsimportComponent } from './customsimport/customsimport.component';
import { CifComponent } from './cif/cif.component';
import { DutypaymentComponent } from './dutypayment/dutypayment.component';
import { CfsComponent } from './cfs/cfs.component';
import { FreightforwarderComponent } from './freightforwarder/freightforwarder.component';
import { DestinationClearingComponent } from './destination-clearing/destination-clearing.component';
import { TransportComponent } from './transport/transport.component';
import { PaymentbankComponent } from './paymentbank/paymentbank.component';
import { WarehousingComponent } from './warehousing/warehousing.component';
import { InhandComponent } from './inhand/inhand.component';
import { FinalComponent } from './final/final.component';
import { LogComponent } from './log/log.component';
import { TranslateModule } from '@ngx-translate/core';

 
const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'importdetails', component: ImportdetailsComponent },
  { path: 'int-logistics', component: IntLogisticsComponent },
  { path: 'customsimport', component: CustomsimportComponent },
  { path: 'cif', component: CifComponent },
  { path: 'dutypayment', component: DutypaymentComponent },
  { path: 'cfs', component: CfsComponent },
  { path: 'freightforwarder', component: FreightforwarderComponent },
  { path: 'destination-clearing', component: DestinationClearingComponent },
  { path: 'transport', component: TransportComponent },
  { path: 'paymentbank', component: PaymentbankComponent },
  { path: 'warehousing', component: WarehousingComponent },
  { path: 'inhand', component: InhandComponent },
  { path: 'final', component: FinalComponent },
  { path: 'log', component: LogComponent }
];

@NgModule({
  declarations: [
    DashboardComponent,
    ImportdetailsComponent,
    IntLogisticsComponent,
    CustomsimportComponent,
    CifComponent,
    DutypaymentComponent,
    CfsComponent,
    FreightforwarderComponent,
    DestinationClearingComponent,
    TransportComponent,
    PaymentbankComponent,
    WarehousingComponent,
    InhandComponent,
    FinalComponent,
    LogComponent,
     
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ],
  providers: [DatePipe]
})
export class LandedModule { }
