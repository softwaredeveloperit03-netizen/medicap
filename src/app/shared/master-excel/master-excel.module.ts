import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MasterExcelProvisionComponent } from './master-excel-provision.component';

@NgModule({
  declarations: [MasterExcelProvisionComponent],
  imports: [CommonModule, FormsModule],
  exports: [MasterExcelProvisionComponent],
})
export class MasterExcelModule {}
