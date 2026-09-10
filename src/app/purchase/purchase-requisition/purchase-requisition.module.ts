import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { RouterModule, Routes } from '@angular/router';
import { ClarityModule } from '@clr/angular';
import { TranslateModule } from '@ngx-translate/core';

import { PurchaseRequisitionHubComponent } from './hub/hub.component';
import { PurchaseRequisitionRmpmComponent } from './rmpm/rmpm.component';
import { PurchaseRequisitionGeneralComponent } from './general/general.component';

const routes: Routes = [
  { path: '', component: PurchaseRequisitionHubComponent },
  { path: 'rmpm', component: PurchaseRequisitionRmpmComponent },
  { path: 'general', component: PurchaseRequisitionGeneralComponent },
];

@NgModule({
  declarations: [
    PurchaseRequisitionHubComponent,
    PurchaseRequisitionRmpmComponent,
    PurchaseRequisitionGeneralComponent,
  ],
  imports: [
    CommonModule,
    FormsModule,
    ClarityModule,
    TranslateModule,
    RouterModule.forChild(routes),
  ],
})
export class PurchaseRequisitionModule {}
