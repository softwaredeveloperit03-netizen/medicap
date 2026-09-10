import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { RouterModule, Routes } from '@angular/router';
import { MaterialOutComponent } from './material-out/material-out.component';
import { WebcamModule } from 'ngx-webcam';
import { CameraComponent } from './camera/camera.component';
import { MultiSelectModule } from 'primeng/multiselect';
import { DropdownModule } from 'primeng/dropdown';
import { OutwardGatepassComponent } from './outward-gatepass/outward-gatepass.component';
import { RequestComponent } from './request/request.component';
import { StocktransinComponent } from './stocktransin/stocktransin.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared/shared.module';
import { MaterialRequisitionComponent } from '../store/material-requisition/material-requisition.component';


  
  
const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'materialoutword', component: MaterialOutComponent },
  { path: 'outward', component: OutwardGatepassComponent },
  { path: 'stockTranferOutword', component: RequestComponent },
  { path: 'stocktransin', component: StocktransinComponent },
  { path: 'material-requisition', component: MaterialRequisitionComponent },
  {
    path: 'purchase-requisition',
    loadChildren: () =>
      import('./purchase-requisition/purchase-requisition.module').then(
        (m) => m.PurchaseRequisitionModule
      ),
    data: { preload: false },
  },
  {
    path: 'attendence',
    loadChildren: () => import('./attendance/attendance.module').then((m) => m.AttendanceModule),
    data: { preload: false },
  },
  {
    path: 'inword',
    loadChildren: () => import('./inword/inword.module').then((m) => m.InwordModule),
    data: { preload: false },
  },
  {
    path: 'entry',
    loadChildren: () => import('./entry/entry.module').then((m) => m.EntryModule),
    data: { preload: false },
  },
  {
    path: 'emp-gatepass',
    loadChildren: () =>
      import('./employee-gatepass/employee-gatepass.module').then((m) => m.EmployeeGatepassModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [
    DashboardComponent,
    MaterialOutComponent,
    RequestComponent,
    CameraComponent,
    OutwardGatepassComponent,
    StocktransinComponent,
    MaterialRequisitionComponent,
  ],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    WebcamModule,
    MultiSelectModule,
    DropdownModule,
    RouterModule.forChild(routes)
  ]
})
export class SecurityModule { }
