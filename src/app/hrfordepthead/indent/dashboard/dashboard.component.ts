import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify: any;

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css']
})
export class DashboardComponent implements OnInit {

  dept = '';
  /** All = plant-wide pending Dept Head PRs. */
  materialType = 'All';
  results: any[] = [];
  loading = false;

  currentPage = 1;
  pageSize = 10;

  isView = false;
  isrevert = false;
  materials: any[] = [];
  selectedResult: any = {};
  hodRemark = '';
  dept_head_remark = '';

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.dept = localStorage.getItem('department') || '';
    this.getPendingIndends(this.materialType);
  }

  getPendingIndends(value: string) {
    this.materialType = value || 'All';
    this.isView = false;
    this.loading = true;
    this.currentPage = 1;

    // Same plant-wide scope for All / RM/PM / General so counts add up:
    // All = RM/PM pending + General pending (excludes To_Purchase_Head extras).
    let endpoint =
      'store/indent_approval.php?type=getAllForDeptHeadApproval&all_depts=1&department_name=all';

    if (this.materialType === 'RM/PM Material') {
      endpoint =
        'store/indent_approval.php?type=getRmpmForApproval&all_depts=1&department_name=all';
    } else if (this.materialType === 'General Material') {
      endpoint =
        'store/indent_approval.php?type=getGeneralForApproval&all_depts=1&department_name=all';
    }

    this.service.loadList(endpoint).subscribe(
      (rows) => {
        this.results = Array.isArray(rows) ? rows : [];
        this.loading = false;
      },
      () => {
        this.results = [];
        this.loading = false;
      }
    );
  }

  calculateStartSrNo(): number {
    return (this.currentPage - 1) * this.pageSize;
  }

  onPageChange(page: number) {
    this.currentPage = page;
  }

  view(indend: any) {
    this.selectedResult = indend || {};
    this.materials = (this.selectedResult && this.selectedResult['materials']) || [];
    this.isView = true;
  }

  viewf() {
    this.isView = false;
    this.currentPage = 1;
    this.pageSize = 10;
  }

  openRevert() {
    this.isrevert = true;
  }

  private resolveApproveType(): 'approveRmpm' | 'approveGeneral' {
    const st = String(this.selectedResult?.status || '');
    const mt = String(this.selectedResult?.material_type || '');
    if (
      st === 'To_HOD_RMPM' ||
      st === 'To_Store_Head' ||
      mt === 'Raw Material' ||
      mt === 'Packing Material' ||
      mt === 'RM/PM Material'
    ) {
      return 'approveRmpm';
    }
    if (this.materialType === 'RM/PM Material') {
      return 'approveRmpm';
    }
    return 'approveGeneral';
  }

  approveIndend(status: string) {
    for (const m of this.materials || []) {
      const qty = String(m?.req_qty ?? '').trim();
      if (!qty || isNaN(Number(qty)) || Number(qty) <= 0) {
        alertify.error('Please enter a valid Purchase Requisition Qty for all materials');
        return;
      }
    }

    const temp: any = {};
    temp['materials'] = this.materials;
    temp['hodRemark'] = this.hodRemark;

    const type = this.resolveApproveType();
    let finalStatus = status;
    if (status !== 'Rejected') {
      finalStatus = 'pending';
    }

    this.service.post('store/indent_approval.php?type=' + type + '&status=' + finalStatus, JSON.stringify(temp))
      .subscribe((response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success('Record updated successfully');
          this.isView = false;
          this.getPendingIndends(this.materialType);
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  revertapproveIndend(status: string) {
    if (this.dept_head_remark === '') {
      alertify.error('Please Add Remark');
      return;
    }
    const temp: any = {};
    temp['materials'] = this.materials;
    temp['dept_head_remark'] = this.dept_head_remark;

    this.service.post('store/indent_approval.php?type=revertGeneral&status=' + status, JSON.stringify(temp))
      .subscribe((response: any) => {
        if (response && response['status'] === 'success') {
          alertify.success('Record updated successfully');
          this.isView = false;
          this.isrevert = false;
          this.getPendingIndends(this.materialType);
        } else {
          alertify.error('Failed: An error occured, please try again!');
        }
      });
  }

  statusLabel(status: string): string {
    const s = String(status || '');
    if (s === 'TO_HOD' || s === 'To_HOD_RMPM' || s === 'To_Store_Head') {
      return 'Dept Head Approval Pending';
    }
    if (s.indexOf('Revert_') === 0) {
      return 'Reverted — Pending';
    }
    return s || 'Pending';
  }
}
