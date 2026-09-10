import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { BillComponent } from './bill/bill.component';
import { ComplaintComponent } from './complaint/complaint.component';
import { TransportManagmentComponent } from './transport-managment/transport-managment.component';
import { VechileComponent } from './vechile/vechile.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';


const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'bill', component:  BillComponent},
  { path: 'complaint', component:ComplaintComponent},
  { path: 'transport-management', component: TransportManagmentComponent},
  { path: 'vechile', component: VechileComponent}
];

@NgModule({
  declarations: [
    BillComponent,
    ComplaintComponent,
    TransportManagmentComponent,
    VechileComponent,
    DashboardComponent
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    RouterModule.forChild(routes)
  ]
})
export class TransportModule { }
