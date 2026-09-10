import { Component, OnInit } from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
import { readEquipmentDeptCalibrationConfig } from '../equipment-dept-calibration-context';
declare let alertify;

@Component({
  selector: 'app-equipment-dept-calibration-log',
  templateUrl: './log.component.html',
})
export class EquipmentDeptCalibrationLogComponent implements OnInit {
  loading = false;
  results: any[] = [];
  performDepartment = '';
  closeRoute = '/';
  from_date = '';
  to_date = '';

  constructor(private service: DataAccessService, private route: ActivatedRoute) {}

  ngOnInit(): void {
    const config = readEquipmentDeptCalibrationConfig(this.route);
    this.performDepartment = config.performDepartment;
    this.closeRoute = config.closeRoute;
    this.from_date = '';
    this.to_date = '';
    this.getCalibrationLog();
  }

  getCalibrationLog(): void {
    this.loading = true;
    const dept = encodeURIComponent(this.performDepartment || '');
    let url =
      'engineering/calibration.php?type=get_equipment_calibration_log&perform_department=' + dept;
    if (this.from_date && this.to_date) {
      url +=
        '&from_date=' +
        encodeURIComponent(this.from_date) +
        '&to_date=' +
        encodeURIComponent(this.to_date);
    }
    this.service.getJsonArray(url).subscribe({
      next: (response) => {
        this.results = Array.isArray(response) ? response : [];
        this.loading = false;
      },
      error: () => {
        this.results = [];
        this.loading = false;
        alertify.error('Unable to load calibration log.');
      },
    });
  }
}
