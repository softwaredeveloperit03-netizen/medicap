import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { MasterVendorComponent } from './master-vendor/master-vendor.component';
import { NewVendorComponent } from './new-vendor/new-vendor.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { FormsModule } from '@angular/forms';
import { SharedModule } from 'src/app/shared/shared.module';
import { VendorChecklistComponent } from './vendor-checklist/vendor-checklist.component';
import { TranslateModule } from '@ngx-translate/core';





const routes: Routes = [
  { path: '', component: DashboardComponent },
  { path: 'Master_doc', component: MasterVendorComponent },
  { path: 'new_doc', component: NewVendorComponent },
  { path: 'checklist', component: VendorChecklistComponent },
];

@NgModule({
  declarations: [
    MasterVendorComponent,
    NewVendorComponent,
    DashboardComponent,
    VendorChecklistComponent,
  ],
  imports: [
    SharedModule, TranslateModule,
    CommonModule,
    ClarityModule,
    FormsModule,
    RouterModule.forChild(routes),
  ],
})
export class DocumentModule {}
