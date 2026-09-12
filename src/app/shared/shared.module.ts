import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { NumberDirective } from './numbers-only.directive';
import { CanadianPhoneDirective } from './canadian-phone.directive';
import { CanadianPhonePipe } from './canadian-phone.pipe';
import { DashboardBaseService } from './dashboard-base.service';
import { TranslateModule } from '@ngx-translate/core';
import { FilterPipeModule } from './filter-pipe.module';
import { QcModuleDashboardModule } from './qc-module-dashboard/qc-module-dashboard.module';
import { RouterModule } from '@angular/router';
import { CfrSignatureBlockComponent } from './cfr-signature-block/cfr-signature-block.component';
import { DeptCalibrationSidebarLinkComponent } from './dept-calibration/dept-calibration-sidebar-link.component';
import { EquipmentWorkOrderSidebarLinkComponent } from './equipment-work-order-sidebar-link.component';

@NgModule({
  declarations: [NumberDirective, CanadianPhoneDirective, CanadianPhonePipe, CfrSignatureBlockComponent, DeptCalibrationSidebarLinkComponent, EquipmentWorkOrderSidebarLinkComponent],
  imports: [TranslateModule, CommonModule, FilterPipeModule, QcModuleDashboardModule, RouterModule],
  exports: [
    NumberDirective,
    CanadianPhoneDirective,
    CanadianPhonePipe,
    FilterPipeModule,
    QcModuleDashboardModule,
    CfrSignatureBlockComponent,
    DeptCalibrationSidebarLinkComponent,
    EquipmentWorkOrderSidebarLinkComponent,
  ],
  providers: [DashboardBaseService],
})
export class SharedModule {}
