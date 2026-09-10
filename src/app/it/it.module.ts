


import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { ClarityModule } from '@clr/angular';
import { DocsIconsModule } from '../floating-docs-popup/docs-icons.module';
import { QueriesComponent } from './queries/queries.component';
import { MasterComponent } from './master/master.component';
import { AuditComponent } from './audit/audit.component';
import { ChangeRequestComponent } from './change-request/change-request.component';
import { DesktopComponent } from './desktop/desktop.component';
import { MobileComponent } from './mobile/mobile.component';
import { EwastelogbookComponent } from './ewastelogbook/ewastelogbook.component';
import { PhyCondiConfigSysComponent } from './phy-condi-config-sys/phy-condi-config-sys.component';
import { PhyCondiCheckingComponent } from './phy-condi-checking/phy-condi-checking.component';
import { EquipInstFormComponent } from './backuprestore/equip-inst-form/equip-inst-form.component';
import { ServerroomComponent } from './serverroom/serverroom.component';
import { DataretriveComponent } from './backuprestore/dataretrive/dataretrive.component';
import { SoftwarequestionnaireComponent } from './supplier-vendor-comp-selection/softwarequestionnaire/softwarequestionnaire.component';
import { InventorylogComponent } from './inventorylog/inventorylog.component';
import { ServerRoomTempMonitComponent } from './server-room-temp-monit/server-room-temp-monit.component';
import { SecurAccessCtrlComponent } from './secur-access-ctrl/secur-access-ctrl.component';
import { TranslateModule } from '@ngx-translate/core';

const routes: Routes = [
  { path: '', component: DashboardComponent},
  { path: 'queries', component: QueriesComponent},
  { path: 'master', component: MasterComponent},
  { path: 'audit1', component: AuditComponent},
  { path: 'PhyCondiCheckingComponent', component: PhyCondiCheckingComponent},
  { path: 'desktop', component: DesktopComponent},
  { path: 'mobile', component: MobileComponent},
  { path: 'eWasteLogBook', component: EwastelogbookComponent },
  { path: 'serverroom', component: ServerroomComponent },
  { path: 'change-request', component: ChangeRequestComponent},
  { path: 'PhyCondiConfigSysComponent', component: PhyCondiConfigSysComponent },
  { path: 'InventoryLog', component: InventorylogComponent },
  { path: 'ServerRoomTempMonitComponent', component: ServerRoomTempMonitComponent },
  { path: 'SecurAccessCtrlComponent', component: SecurAccessCtrlComponent },



  { path: 'unit', loadChildren: () => import('./unit/unit.module').then(m=>m.UnitModule), data: {preload: false}},
  { path: 'password', loadChildren: () => import('./password/password.module').then(m=>m.PasswordModule), data: {preload: false}},
  { path: 'workorder', loadChildren: () => import('./workorder/workorder.module').then(m=>m.WorkorderModule), data: {preload: false}},
  { path: 'userReq', loadChildren: () => import('./userreq/userreq.module').then(m=>m.UserreqModule), data: {preload: false}},
  { path: 'itpolicies', loadChildren: () => import('./itpolicies/itpolicies.module').then(m=>m.ItpoliciesModule), data: {preload: false}},
  { path: 'backuprestore', loadChildren: () => import('./backuprestore/backuprestore.module').then(m=>m.BackuprestoreModule), data: {preload: false}},
  { path: 'softwareques', loadChildren: () => import('./supplier-vendor-comp-selection/supplier-vendor-comp-selection.module').then(m=>m.SupplierVendorCompSelectionModule), data: {preload: false}},

];

@NgModule({
  declarations: [DashboardComponent, QueriesComponent,MasterComponent,AuditComponent,ChangeRequestComponent,
    DesktopComponent,MobileComponent, EwastelogbookComponent,
    PhyCondiConfigSysComponent,
    ServerroomComponent,
    EquipInstFormComponent,
    DataretriveComponent,
    InventorylogComponent,
    ServerRoomTempMonitComponent,
    SecurAccessCtrlComponent,
  ],
  imports: [
    SharedModule,
    CommonModule,
    FormsModule,
    ClarityModule,
    DocsIconsModule,
    TranslateModule,
    RouterModule.forChild(routes)
  ]
})

export class ItModule { }
