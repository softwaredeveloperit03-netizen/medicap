import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
import { DeptNavigationService } from 'src/app/shared/dept-navigation.service';
import { ActivatedRoute } from '@angular/router';
declare let alertify;

@Component({
  selector: 'app-depthead-calibration',
  templateUrl: './depthead-calibration.component.html',
  styleUrls: ['./depthead-calibration.component.css'],
})
export class DeptheadCalibrationComponent implements OnInit {
  loading = false;
  department = '';
  rows: any[] = [];
  searchQuery = '';
  isViewDetails = false;
  selectedResult: any = {};

  constructor(
    private service: DataAccessService,
    private route: ActivatedRoute,
    private deptNav: DeptNavigationService
  ) {}

  ngOnInit(): void {
    this.department = localStorage.getItem('department') || '';
    this.loadRows();
  }

  loadRows(): void {
    this.loading = true;
    const dept = encodeURIComponent(this.department || '');
    this.service.get('engineering/calibration.php?type=get_dept_head_pending_calibrations&department=' + dept).subscribe({
      next: (response: any) => {
        this.rows = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.rows = [];
        this.loading = false;
      },
    });
  }

  get filteredRows(): any[] {
    const list = Array.isArray(this.rows) ? this.rows : [];
    if (!this.searchQuery || !this.searchQuery.trim()) {
      return list;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return list.filter((row) =>
      Object.entries(row).some(([, value]) => value && value.toString().toLowerCase().includes(query))
    );
  }

  viewDetails(index: number): void {
    this.selectedResult = this.filteredRows[index] || {};
    this.isViewDetails = true;
  }

  approve(index: number): void {
    const row = this.filteredRows[index];
    if (!row?.id) {
      alertify.error('Invalid calibration record.');
      return;
    }
    this.service
      .post(
        'engineering/calibration.php?type=approveDeptHeadCalibration&id=' +
          row.id +
          '&department=' +
          encodeURIComponent(this.department || ''),
        {}
      )
      .subscribe({
        next: (response: any) => {
          const result = typeof response === 'object' ? response : {};
          if (result.status === 'success') {
            const nextStatus = String(result.cali_status || '').toLowerCase();
            if (nextStatus === 'done') {
              alertify.success('Approved. Calibration completed.');
            } else {
              alertify.success('Approved and sent to Engineering calibration.');
            }
            this.loadRows();
          } else {
            alertify.error('Failed: ' + (result.status || 'Unable to approve'));
          }
        },
        error: () => alertify.error('Unable to approve calibration.'),
      });
  }

  onClose(): void {
    this.deptNav.goBack(this.route, '/hrfordepthead');
  }
}
