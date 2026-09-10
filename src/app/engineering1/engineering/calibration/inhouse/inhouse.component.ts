import { Component, OnInit } from '@angular/core';
import { NgForm } from '@angular/forms';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-inhouse',
  templateUrl: './inhouse.component.html',
  styleUrls: ['./inhouse.component.css']
})
export class InhouseComponent implements OnInit {
  isLast = false;
  isViewDetails = false;
  loading = false;
  equipmentslog: any[] = [];
  selectedResult: any = {};
  calibration_date = '';
  remark = '';
  searchQuery = '';

  currentDate: Date;
  displayMonth: string;

  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.currentDate = new Date();
    this.updateDisplayMonth(this.currentDate);
  }

  previousMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
    this.updateDisplayMonth(this.currentDate);
  }

  currentMonth() {
    this.currentDate = new Date();
    this.updateDisplayMonth(this.currentDate);
  }

  nextMonth() {
    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
    this.updateDisplayMonth(this.currentDate);
  }

  updateDisplayMonth(date: Date) {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    const month = date.getMonth();
    const year = date.getFullYear();
    this.displayMonth = `${monthNames[month]} ${year}`;
    this.getMaintenanceData(month, year);
  }

  getMaintenanceData(month: number, year: number) {
    month = month + 1;
    this.loading = true;
    this.service.get('engineering/calibration.php?type=get_monthly_schedule&month=' + month + '&year=' + year + '&due_type=Inhouse&for_engineering=1').subscribe({
      next: (response: any) => {
        this.equipmentslog = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.equipmentslog = [];
        this.loading = false;
      }
    });
  }

  isPendingCalibration(row: any): boolean {
    const status = String(row?.cali_status || 'Pending').trim().toLowerCase();
    return status === '' || status === 'pending' || status === 'pending engineering';
  }

  view(index: number) {
    this.selectedResult = this.filteredMaterials[index] || {};
    this.calibration_date = '';
    this.remark = '';
    this.isLast = true;
  }

  viewDetails(index: number) {
    this.selectedResult = this.filteredMaterials[index] || {};
    this.isViewDetails = true;
  }

  closePerformModal() {
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
    return list.filter(material => {
      return Object.entries(material).some(([, value]) => {
        return value && value.toString().toLowerCase().includes(query);
      });
    });
  }

  saveInhouseCalibration(form: NgForm) {
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

    this.service.post('engineering/calibration.php?type=saveInhouseCalibration&id=' + this.selectedResult['id'], uploadData).subscribe({
      next: (response: any) => {
        const result = typeof response === 'object' ? response : {};
        if (result.status === 'success') {
          alertify.success('Calibration completed and marked Done.');
          this.closePerformModal();
          form.resetForm();
          this.updateDisplayMonth(this.currentDate);
        } else {
          alertify.error('Failed: ' + (result.status || 'Unable to save'));
        }
      },
      error: () => alertify.error('Unable to save calibration.')
    });
  }
}
