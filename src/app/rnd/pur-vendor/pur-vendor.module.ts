import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { ClarityModule } from '@clr/angular';
import { RouterModule, Routes } from '@angular/router';
import { TranslateModule } from '@ngx-translate/core';
import { SharedModule } from 'src/app/shared/shared.module';
import { VendorModule } from 'src/app/purchase/vendor/vendor.module';
import { RegistrationComponent } from 'src/app/purchase/vendor/registration/registration.component';
import { ApprovalComponent } from 'src/app/purchase/vendor/approval/approval.component';
import { PurVendorDashboardComponent } from './dashboard/dashboard.component';

const routes: Routes = [
  { path: '', component: PurVendorDashboardComponent },
  { path: 'registration', component: RegistrationComponent },
  { path: 'approval', component: ApprovalComponent },
];

@NgModule({
  declarations: [PurVendorDashboardComponent],
  imports: [
    TranslateModule,
    SharedModule,
    CommonModule,
    FormsModule,
    ReactiveFormsModule,
    ClarityModule,
    VendorModule,
    RouterModule.forChild(routes),
  ],
})
export class PurVendorModule {}
