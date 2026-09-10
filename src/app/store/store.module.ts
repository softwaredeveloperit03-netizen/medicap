import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { LabelComponent } from './label/label.component';
import { TemperatureModule } from './temperature/temperature.module';
import { TemperatureComponent } from './temperature/temperature.component';
import { ChangeGradeComponent } from './change-grade/change-grade.component';
import { MultiSelect, MultiSelectModule } from 'primeng/multiselect';
import { LogsComponent } from './logs/logs.component';
import { ReportsComponent } from './reports/reports.component';
import { CommonComponent } from './common/common.component';
import { DropdownModule } from 'primeng/dropdown';
import { AutoCompleteModule } from 'primeng/autocomplete';
import { MaterialIssueRequestComponent } from './material-issue-request/material-issue-request.component';
import { MaterialRequisitionComponent } from './material-requisition/material-requisition.component';
import { DayStoreMasterComponent } from './day-store-master/day-store-master.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from '../shared/shared.module';

 const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'change-grade', component: ChangeGradeComponent},
  { path: 'logs', component: LogsComponent},
  { path: 'reports', component: ReportsComponent},
  { path: 'label', component: LabelComponent},
  { path: 'temperature', component: TemperatureComponent, data: { temperatureDepartment: 'Store', closeRoute: '/store' } },
  { path: 'common_log', component: CommonComponent},
  { path: 'material-issue-request', component: MaterialIssueRequestComponent},
  { path: 'material-requisition', component: MaterialRequisitionComponent},
   { path: 'equipments', loadChildren: () => import('./equipments/equipments.module').then(m=>m.EquipmentsModule), data: {preload: false}},
  { path: 'rack', loadChildren: () => import('./rack/rack.module').then(m=>m.RackModule), data: {preload: false}},
  { path: 'raw', loadChildren: () => import('./raw/raw.module').then(m=>m.RawModule), data: {preload: false}},
  { path: 'retest-intimation-slip', loadChildren: () => import('./retest-intimation-slip/retest-intimation-slip.module').then(m => m.RetestIntimationSlipModule), data: { preload: false }},
   { path: 'dispensing', loadChildren: () => import('./dispensing/dispensing.module').then(m=>m.DispensingModule), data: {preload: false}},
  { path: 'bincard', loadChildren: () => import('./bincard/bincard.module').then(m=>m.BincardModule), data: {preload: false}},
  { path: 'challan', loadChildren: () => import('./challan/challan.module').then(m=>m.ChallanModule), data: {preload: false}},
  { path: 'rejection', loadChildren: () => import('./rejection/rejection.module').then(m=>m.RejectionModule), data: {preload: false}},
  { path: 'indend', loadChildren: () => import('./indend/indend.module').then(m=>m.IndendModule), data: {preload: false}},
   { path: 'stock', loadChildren: () => import('./stock/stock.module').then(m=>m.StockModule), data: {preload: false}},
  { path: 'material-status', loadChildren: () => import('./material-status/material-status.module').then(m=>m.MaterialStatusModule), data: {preload: false}},
  { path: 'finish', loadChildren: () => import('./finish/finish.module').then(m=>m.FinishModule), data: {preload: false}},
  { path: 'qms', loadChildren: () => import('./qms/qms.module').then(m=>m.QmsModule), data: {preload: false}},
   { path: 'godown', loadChildren: () => import('./godown/godown.module').then(m=>m.GodownModule), data: {preload: false}},
  { path: 'job_work', loadChildren: () => import('./jobwork/jobwork.module').then(m=>m.JobworkModule), data: {preload: false}},
  { path: 'calibration', loadChildren: () => import('./calibration/calibration.module').then(m=>m.CalibrationModule), data: {preload: false}},
  { path: 'mixed-grn', loadChildren: () => import('./mixed-grn/mixed-grn.module').then(m=>m.MixedGrnModule), data: {preload: false}},
  { path: 'goods', loadChildren: () => import('./goods/goods.module').then(m=>m.GoodsModule), data: {preload: false}},
  { path: 'checklist', loadChildren: () => import('./checklist/checklist.module').then(m=>m.ChecklistModule), data: {preload: false}},
  { path: 'status', loadChildren: () => import('./status/status.module').then(m=>m.StatusModule), data: {preload: false}},
  {
    path: 'purchase-requisition',
    loadChildren: () =>
      import('./purchase-requisition/purchase-requisition.module').then(
        (m) => m.PurchaseRequisitionModule
      ),
    data: { preload: false },
  },
  { path: 'material', loadChildren: () => import('./material/material.module').then(m=>m.MaterialModule), data: {preload: false}},
  { path: 'additional', loadChildren: () => import('./additional/additional.module').then(m=>m.AdditionalModule), data: {preload: false}},
  { path: 'logins', loadChildren: () => import('./logins/logins.module').then(m=>m.LoginsModule), data: {preload: false}},
  { path: 'lablings', loadChildren: () => import('./lablings/lablings.module').then(m=>m.LablingsModule), data: {preload: false}},
  { path: 'expiry-management', loadChildren: () => import('./expiry-management/expiry-management.module').then(m=>m.ExpiryManagementModule), data: {preload: false}},
  { path: 'stocktranfer', loadChildren: () => import('./stocktranfer/stocktranfer.module').then(m=>m.StocktranferModule), data: {preload: false}},
   { path: 'wms', loadChildren: () => import('./wms/wms.module').then(m=>m.WmsModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent, LabelComponent, ChangeGradeComponent, LogsComponent, ReportsComponent, CommonComponent, MaterialIssueRequestComponent, MaterialRequisitionComponent, DayStoreMasterComponent],
  imports: [ TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    MultiSelectModule,
    DropdownModule,
    AutoCompleteModule,
    TemperatureModule,
    RouterModule.forChild(routes)
  ]
})
export class StoreModule { }
