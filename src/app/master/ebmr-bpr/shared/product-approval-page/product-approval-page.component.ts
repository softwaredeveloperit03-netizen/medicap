import { Component, EventEmitter, Input, Output } from '@angular/core';
import {
  PAP_EXECUTION_FIELDS,
  PAP_MASTER_LOCKED_FIELDS,
  ProductApprovalPage,
} from '../product-approval.util';

export type ProductApprovalMode = 'master' | 'execution' | 'view';

@Component({
  selector: 'app-product-approval-page',
  templateUrl: './product-approval-page.component.html',
  styleUrls: ['./product-approval-page.component.css'],
})
export class ProductApprovalPageComponent {
  @Input() page: ProductApprovalPage = {} as ProductApprovalPage;
  @Input() mode: ProductApprovalMode = 'view';
  @Output() pageChange = new EventEmitter<ProductApprovalPage>();

  readonly masterLocked = PAP_MASTER_LOCKED_FIELDS;
  readonly executionFields = PAP_EXECUTION_FIELDS;

  emit(): void {
    this.pageChange.emit(this.page);
  }

  val(field: keyof ProductApprovalPage): string {
    return this.page?.[field] || '';
  }

  isEditable(field: keyof ProductApprovalPage): boolean {
    if (this.mode === 'execution') {
      return PAP_EXECUTION_FIELDS.includes(field);
    }
    return false;
  }

  isLockedMaster(field: keyof ProductApprovalPage): boolean {
    return this.mode === 'master' && PAP_MASTER_LOCKED_FIELDS.includes(field);
  }

  showExecutionPending(field: keyof ProductApprovalPage): boolean {
    return this.mode === 'master' && PAP_EXECUTION_FIELDS.includes(field);
  }

  isReadOnlyDisplay(field: keyof ProductApprovalPage): boolean {
    if (this.mode === 'view') return true;
    if (this.mode === 'master') {
      return PAP_MASTER_LOCKED_FIELDS.includes(field) || PAP_EXECUTION_FIELDS.includes(field);
    }
    if (this.mode === 'execution') {
      return PAP_MASTER_LOCKED_FIELDS.includes(field);
    }
    return true;
  }
}
