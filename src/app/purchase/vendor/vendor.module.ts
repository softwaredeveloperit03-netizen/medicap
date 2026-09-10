import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RegistrationComponent } from './registration/registration.component';
import { ClarityModule } from '@clr/angular';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { DashboardComponent } from './dashboard/dashboard.component';
import { SendchecklistComponent } from './sendchecklist/sendchecklist.component';
import { LogComponent } from './log/log.component';
import { ApprovalComponent } from './approval/approval.component';
import { ForEditingComponent } from './for-editing/for-editing.component';
import { BlacklistComponent } from './blacklist/blacklist.component';
import { VendorLogComponent } from './vendor-log/vendor-log.component';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { MaterialModule } from './material/material.module';

const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'registration', component: RegistrationComponent },
  { path: 'blacklist', component: BlacklistComponent },
  { path: 'sendchecklist', component: SendchecklistComponent },
  { path: 'log', component: LogComponent },
  { path: 'approval', component: ApprovalComponent },
  { path: 'for-editing', component: ForEditingComponent },
  { path: 'approvedVendorLog', component: VendorLogComponent },
  // Eagerly resolve MaterialModule (bundled with vendor) so Map Material opens reliably
  {
    path: 'material',
    loadChildren: () => Promise.resolve(MaterialModule),
    data: { preload: false },
  },
  {
    path: 'document',
    loadChildren: () =>
      import('./document/document.module').then((m) => m.DocumentModule),
    data: { preload: false },
  },
];

@NgModule({
  declarations: [
    RegistrationComponent,
    BlacklistComponent,
    DashboardComponent,
    SendchecklistComponent,
    LogComponent,
    ApprovalComponent,
    ForEditingComponent,
    VendorLogComponent,
  ],
  imports: [
    TranslateModule,
    SharedModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    ReactiveFormsModule,
    RouterModule.forChild(routes),
  ],
  exports: [RegistrationComponent, ApprovalComponent],
})
export class VendorModule {}
