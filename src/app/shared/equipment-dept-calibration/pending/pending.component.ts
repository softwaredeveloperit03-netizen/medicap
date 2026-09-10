import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { readEquipmentDeptCalibrationConfig } from '../equipment-dept-calibration-context';
declare let alertify;

@Component({
  selector: 'app-equipment-dept-calibration-pending',
  templateUrl: './pending.component.html',
  styleUrls: ['./pending.component.css'],
})
export class EquipmentDeptCalibrationPendingComponent implements OnInit {
  loading = false;
  isLast = false;
  isViewDetails = false;
  equipmentslog: any[] = [];
  selectedResult: any = {};
  calibration_date = '';
  remark = '';
  searchQuery = '';
  performDepartment = '';
  closeRoute = '/';
  currentDate: Date;
  displayMonth = '';

  constructor(private service: DataAccessService, private route: ActivatedRoute) {}

  ngOnInit(): void {
    const config = readEquipmentDeptCalibrationConfig(this.route);
    this.performDepartment = config.performDepartment;
    this.closeRoute = config.closeRoute;
    this.currentDate = new Date();
    this.updateDisplayMonth(this.currentDate);
  }

  previousMonth(): void {
    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
    this.updateDisplayMonth(this.currentDate);
  }

  currentMonth(): void {
    this.currentDate = new Date();
    this.updateDisplayMonth(this.currentDate);
  }

  nextMonth(): void {
    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
    this.updateDisplayMonth(this.currentDate);
  }

  updateDisplayMonth(date: Date): void {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const month = date.getMonth();
    const year = date.getFullYear();
    this.displayMonth = `${monthNames[month]} ${year}`;
    this.getMaintenanceData(month, year);
  }

  getMaintenanceData(month: number, year: number): void {
    month = month + 1;
    this.loading = true;
    const dept = encodeURIComponent(this.performDepartment || '');
    this.service
      .get(
        'engineering/calibration.php?type=get_monthly_schedule&month=' +
          month +
          '&year=' +
          year +
          '&due_type=Inhouse&pending_only=1&inhouse_department=' +
          dept
      )
      .subscribe({
        next: (response: any) => {
          this.equipmentslog = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.equipmentslog = [];
          this.loading = false;
        },
      });
  }

  isPendingCalibration(row: any): boolean {
    const status = String(row?.cali_status || 'Pending').trim().toLowerCase();
    return status === '' || status === 'pending';
  }

  view(index: number): void {
    this.selectedResult = this.filteredMaterials[index] || {};
    this.calibration_date = '';
    this.remark = '';
    this.isLast = true;
  }

  viewDetails(index: number): void {
    this.selectedResult = this.filteredMaterials[index] || {};
    this.isViewDetails = true;
  }

  closePerformModal(): void {
    this.isLast = false;
    this.calibration_date = '';
    this.remark = '';
  }

  get filteredMaterials(): any[] {
    const list = Array.isArray(this.equipmentslog) ? this.equipmentslog : [];
    if (!this.searchQuery || this.searchQuery.trim() === '') {
      return list;
    }
    const query = this.searchQuery.toLowerCase().trim();
    return list.filter((material) =>
      Object.entries(material).some(([, value]) => value && value.toString().toLowerCase().includes(query))
    );
  }

  saveInhouseCalibration(form: NgForm): void {
    if (!form.valid) {
      alertify.error('Please enter calibration date.');
      return;
    }
    if (!this.selectedResult?.id) {
      alertify.error('Invalid calibration record.');
      return;
    }

    const uploadData = new FormData();
    uploadData.append('calibration_date', this.calibration_date);
    uploadData.append('remark', this.remark || '');
    uploadData.append('workflow', 'dept_perform');

    this.service
      .post(
        'engineering/calibration.php?type=saveInhouseCalibration&workflow=dept_perform&id=' + this.selectedResult['id'],
        uploadData
      )
      .subscribe({
      next: (response: any) => {
        const result = typeof response === 'object' ? response : {};
        if (result.status === 'success') {
          alertify.success('Calibration saved and sent to Dept Head for approval.');
          this.closePerformModal();
          form.resetForm();
          this.updateDisplayMonth(this.currentDate);
        } else {
          alertify.error('Failed: ' + (result.status || 'Unable to save'));
        }
      },
      error: () => alertify.error('Unable to save calibration.'),
    });
  }

  get performSubtitle(): string {
    const row = this.selectedResult || {};
    return String(row.description || row.equipment_name || '').trim();
  }
}
