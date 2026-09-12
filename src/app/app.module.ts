  import { BrowserModule } from '@angular/platform-browser';
import { APP_INITIALIZER, NgModule } from '@angular/core';
import { AppComponent } from './app.component';
import { ClarityModule } from '@clr/angular';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { FormsModule  } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { HttpClient } from '@angular/common/http';
import { DataAccessService } from './data-access.service';
import { LoginComponent } from './login/login.component';
import { SessionReauthComponent } from './shared/session-security/session-reauth.component';
import { QcModuleDashboardModule } from './shared/qc-module-dashboard/qc-module-dashboard.module';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { NavbarComponent } from './navbar/navbar.component';
import { HashLocationStrategy, LocationStrategy } from '@angular/common';
import { QuicklinkModule, QuicklinkStrategy } from 'ngx-quicklink';
import { MultiSelectModule } from 'primeng/multiselect';
import { LoadingBarHttpClientModule } from '@ngx-loading-bar/http-client';
import { NgxDocViewerModule } from 'ngx-doc-viewer';
import { PdfViewerModule } from 'ng2-pdf-viewer';
import { CameraModule } from './camera/camera.module';

import { CameraComponent } from './camera/camera.component';
import { FooterComponent } from './footer/footer.component'; 
    import { ModalModule } from './_modal';
 import { CalendarModule, DateAdapter } from 'angular-calendar';
import { adapterFactory } from 'angular-calendar/date-adapters/date-fns';
   import { DemodashComponent } from './demodash/demodash.component';
 import { ReactiveFormsModule } from '@angular/forms';
import { QuillModule } from 'ngx-quill';
import { SharedModule } from './shared/shared.module';
import { BreakdownComponent } from './breakdown/breakdown.component';
import { DragDropModule } from '@angular/cdk/drag-drop';
import { FloatingDocsPopupComponent } from './floating-docs-popup/floating-docs-popup.component';
import { StatusBoardComponent } from './status-board/status-board.component';
import { TranslateLoader, TranslateModule } from '@ngx-translate/core';
import { TranslateHttpLoader } from '@ngx-translate/http-loader';
import { LanguageService } from './shared/language.service';
import { ThemePickerComponent } from './shared/theme-picker/theme-picker.component';
import { ThemeRadialFabComponent } from './shared/theme-radial-fab/theme-radial-fab.component';
import { AppThemeService } from './shared/app-theme.service';
import { EsignDialogComponent } from './shared/esign/esign-dialog.component';
 
const routes: Routes = [
  { path: '', component: DashboardComponent },
   { path: 'camera', component: CameraComponent },
   { path: 'demodash', component: DemodashComponent },
   { path: 'breakdown', component: BreakdownComponent},
  { path: 'status', component: StatusBoardComponent},

 
  {
    path: 'accounts',
    loadChildren: () =>
      import('./accounts/accounts.module').then(
        (m) => m.AccountsModule
      ),
    data: { preload: false },
  },
 
 
  {
    path: 'p-changecontrol',
    loadChildren: () =>
      import('./p-changecontrol/p-changecontrol.module').then(
        (m) => m.PChangecontrolModule
      ),
    data: { preload: false },
  },
  {
    path: 'o-changecontrol',
    loadChildren: () =>
      import('./o-changecontrol/o-changecontrol.module').then(
        (m) => m.OChangecontrolModule
      ),
    data: { preload: false },
  },
  {
    path: 'pdeviaton',
    loadChildren: () =>
      import('./pdeviaton/pdeviaton.module').then(
        (m) => m.PdeviatonModule
      ),
    data: { preload: false },
  },
  {
    path: 'calibration',
    loadChildren: () =>
      import('./calibration/calibration.module').then(
        (m) => m.CalibrationModule
      ),
    data: { preload: false },
  },
  {
    path: 'equipment-calibration',
    loadChildren: () =>
      import('src/app/shared/equipment-dept-calibration/equipment-dept-calibration.module').then(
        (m) => m.EquipmentDeptCalibrationModule
      ),
    data: { useSessionDepartment: true, preload: false },
  },
  {
    path: 'npd',
    loadChildren: () =>
      import('./npd/npd.module').then(
        (m) => m.NpdModule
      ),
    data: { preload: false },
  },
  {
    path: 'rnd',
    loadChildren: () =>
      import('./rnd/rnd.module').then(
        (m) => m.RndModule
      ),
    data: { preload: false },
  },

  {
    path: 'reception',
    loadChildren: () =>
      import('./reception/reception.module').then(
        (m) => m.ReceptionModule
      ),
    data: { preload: false },
  },

  {
    path: 'timeline',
    loadChildren: () =>
      import('./timeline/timeline.module').then(
        (m) => m.TimelineModule
      ),
    data: { preload: false },
  },
  {
    path: 'dispatch',
    loadChildren: () =>
      import('./dispatch/dispatch.module').then((m) => m.DispatchModule),
    data: { preload: false },
  },
  {
    path: 'engi-store',
    loadChildren: () =>
      import('./engi-store/engi-store.module').then((m) => m.EngiStoreModule),
    data: { preload: false },
  },
  {
    path: 'engineering',
    loadChildren: () =>
      import('./engineering1/engineering/engineering.module').then(
        (m) => m.EngineeringModule
      ),
    data: { preload: false },
  },
  {
    path: 'hr',
    loadChildren: () => import('./hr/hr.module').then((m) => m.HrModule),
    data: { preload: false },
  },
  {
    path: 'ipqc',
    loadChildren: () => import('./ipqc/ipqc.module').then((m) => m.IpqcModule),
    data: { preload: false },
  },
  {
    path: 'it',
    loadChildren: () => import('./it/it.module').then((m) => m.ItModule),
    data: { preload: false },
  },
  {
    path: 'management',
    loadChildren: () =>
      import('./management/management.module').then((m) => m.ManagementModule),
    data: { preload: false },
  },
  {
    path: 'master',
    loadChildren: () =>
      import('./master/master.module').then((m) => m.MasterModule),
    data: { preload: false },
  },
 
 
  {
    path: 'planning',
    loadChildren: () =>
      import('./planning/planning.module').then((m) => m.PlanningModule),
    data: { preload: false },
  },
  {
    path: 'production',
    loadChildren: () =>
      import('./production/production.module').then((m) => m.ProductionModule),
    data: { preload: false },
  },
  {
    path: 'fproduction',
    loadChildren: () =>
      import('./fproduction/fproduction.module').then(
        (m) => m.FproductionModule
      ),
    data: { preload: false },
  },
 
  {
    path: 'production-formulation',
    loadChildren: () =>
      import('./production/production.module').then(
        (m) => m.ProductionModule
      ),
    data: { preload: false },
  },
  {
    path: 'qa',
    loadChildren: () => import('./qa/qa.module').then((m) => m.QaModule),
    data: { preload: false },
  },
  {
    path: 'qc',
    loadChildren: () => import('./qc/qc.module').then((m) => m.QcModule),
    data: { preload: false },
  },
  {
    path: 'department-guide',
    loadChildren: () =>
      import('./shared/department-guide/department-guide.module').then(
        (m) => m.DepartmentGuideModule
      ),
    data: { preload: false },
  },
 
 
  {
    path: 'store',
    loadChildren: () =>
      import('./store/store.module').then((m) => m.StoreModule),
    data: { preload: false },
  },
  {
    path: 'purchase',
    loadChildren: () =>
      import('./purchase/purchase.module').then((m) => m.PurchaseModule),
    data: { preload: false },
  },
  
 
  {
    path: 'godownStore',
    loadChildren: () =>
      import('./godownstore/godownstore.module').then(
        (m) => m.GodownstoreModule
      ),
    data: { preload: false },
  },
  {
    path: 'autopurchase',
    loadChildren: () =>
      import('./autopurchase/autopurchase.module').then(
        (m) => m.AutopurchaseModule
      ),
    data: { preload: false },
  },
  {
    path: 'marketing',
    loadChildren: () =>
      import('./marketing/marketing.module').then((m) => m.MarketingModule),
    data: { preload: false },
  },
  {
    path: 'support',
    loadChildren: () =>
      import('./support/support.module').then((m) => m.SupportModule),
    data: { preload: false },
  },
  {
    path: 'qms',
    loadChildren: () => import('./qms/qms.module').then((m) => m.QmsModule),
    data: { preload: false },
  },
  {
    path: 'ehs',
    loadChildren: () => import('./ehs/ehs.module').then((m) => m.EhsModule),
    data: { preload: false },
  },
  {
    path: 'security',
    loadChildren: () =>
      import('./security/security.module').then((m) => m.SecurityModule),
    data: { preload: false },
  },
  {
    path: 'admin',
    loadChildren: () =>
      import('./admin/admin.module').then((m) => m.AdminModule),
    data: { preload: false },
  },
  {
    path: 'daystore',
    loadChildren: () =>
      import('./daystore/daystore.module').then((m) => m.DaystoreModule),
    data: { preload: false },
  },
 
 
  {
    path: 'packing',
    loadChildren: () =>
      import('./packing2/packing.module').then((m) => m.PackingModule),
    data: { preload: false },
  },
  {
    path: 'employee-dashboard',
    loadChildren: () =>
      import('./employee-dashboard/employee-dashboard.module').then(
        (m) => m.EmployeeDashboardModule
      ),
    data: { preload: false },
  },
 
  {
    path: 'unitformula',
    loadChildren: () =>
      import('./unitformula/unitformula.module').then(
        (m) => m.UnitformulaModule
      ),
    data: { preload: false },
  },
 

  {
    path: 'prod-f-ebmr',
    loadChildren: () =>
      import('./production/production.module').then((m) => m.ProductionModule),
    data: { preload: false },
  },
 
 
 
  {
    path: 'training',
    loadChildren: () =>
      import('./training/training.module').then((m) => m.TrainingModule),
    data: { preload: false },
  },
  {
    path: 'customer',
    loadChildren: () =>
      import('./customer/customer.module').then((m) => m.CustomerModule),
    data: { preload: false },
  },
  {
    path: 'export',
    loadChildren: () =>
      import('./export/export.module').then((m) => m.ExportModule),
    data: { preload: false },
  },
    {
    path: 'Exports',
    loadChildren: () =>
      import('./exports/exports.module').then((m) => m.ExportsModule),
    data: { preload: false },
  },
  {
    path: 'hrfordepthead',
    loadChildren: () =>
      import('./hrfordepthead/hrfordepthead.module').then(
        (m) => m.HrfordeptheadModule
      ),
    data: { preload: false },
  },
 
  {
    path: 'plant_head',
    loadChildren: () =>
      import('./deptmodule/deptmodule.module').then((m) => m.DeptmoduleModule),
    data: { preload: false },
  },
 
  {
    path: 'regulatory',
    loadChildren: () =>
      import('./regulatory-new/regulatory-new.module').then(
        (m) => m.RegulatoryNewModule
      ),
    data: { preload: false },
  },
  {
    path: 'preventiveimain',
    loadChildren: () =>
      import('./preventiveimtimation/preventiveimtimation.module').then(
        (m) => m.PreventiveimtimationModule
      ),
    data: { preload: false },
  },
  {
    path: 'equipment-work-order',
    loadChildren: () =>
      import('./equipment-work-order/equipment-work-order.module').then(
        (m) => m.EquipmentWorkOrderModule
      ),
    data: { preload: false },
  },
  {
    path: 'qhead',
    loadChildren: () =>
      import('./qhead/qhead.module').then((m) => m.QheadModule),
    data: { preload: false },
  },
 

  {
    path: 'indend',
    loadChildren: () =>
      import('./indend/indend.module').then((m) => m.IndendModule),
    data: { preload: false },
  },

  { path: 'equipmentusage',loadChildren:() => import('./equipmentusage/equipmentusage.module').then((m)=>m.EquipmentusageModule),data: {preload: false } },
  { path: 'mrp',loadChildren:() => import('./mrp/mrp.module').then((m)=>m.MrpModule),data: {preload: false } },

 
];

export function httpLoaderFactory(http: HttpClient): TranslateHttpLoader {
  return new TranslateHttpLoader(http, './assets/i18n/', '.json');
}

export function appLanguageInitializer(languageService: LanguageService): () => void {
  return () => languageService.init();
}

@NgModule({
  declarations: [
    AppComponent,
    LoginComponent,
    SessionReauthComponent,
    DashboardComponent,
    NavbarComponent,
    CameraComponent,
    FooterComponent,
    DemodashComponent,
    BreakdownComponent,
    StatusBoardComponent,
    ThemePickerComponent,
    ThemeRadialFabComponent,
    EsignDialogComponent,
  ],
  imports: [
    BrowserModule,
    CalendarModule.forRoot({
      provide: DateAdapter,
      useFactory: adapterFactory,
    }),
    ClarityModule,
    SharedModule,
    QcModuleDashboardModule,
    FormsModule,
    ReactiveFormsModule,
    HttpClientModule,
    BrowserAnimationsModule,
    DragDropModule,
    QuicklinkModule,
    MultiSelectModule,
    NgxDocViewerModule,
    LoadingBarHttpClientModule,
    PdfViewerModule,
    ModalModule,
    TranslateModule.forRoot({
      loader: {
        provide: TranslateLoader,
        useFactory: httpLoaderFactory,
        deps: [HttpClient],
      },
    }),
 
    RouterModule.forRoot(routes, { preloadingStrategy: QuicklinkStrategy }),
  ],
  exports: [ReactiveFormsModule],
  providers: [
    DataAccessService,
    { provide: LocationStrategy, useClass: HashLocationStrategy },
    {
      provide: APP_INITIALIZER,
      useFactory: appLanguageInitializer,
      deps: [LanguageService],
      multi: true
    }
  ],
  bootstrap: [AppComponent],
})
export class AppModule {}
