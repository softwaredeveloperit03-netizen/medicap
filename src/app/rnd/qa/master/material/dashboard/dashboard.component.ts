import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';

@Component({
  selector: 'app-dashboard',
  templateUrl: './dashboard.component.html',
  styleUrls: ['./dashboard.component.css'],
})
export class DashboardComponent implements OnInit {
  isView = false;
  results;
  isRawMaterial = true;
  isPackingMaterial = false;
  material_type = '';
  material_subtype = '';
  status = '';
  loading = false;
  selectedResult = [];

  constructor(private service: DataAccessService) {this.loggedInDept = localStorage.getItem('department');}

  ngOnInit(): void {
    this.getMaterialsLog();
    this.get_rights();
  }
  // -----------------------------------------12th july------------------------------------------//

  isuser = 'No';
  ischecker = 'No';
  isapprover = 'No';
  qms_approver = 'No';
  dept_head = 'No';
  isauditor = 'No';
  plant_head = 'No';
  shift_allocator = 'No';
  rights;
  loggedInDept;

  get_rights() {
    this.service
      .get(
        'hr/employee.php?type=getrights&emp_id=' +
          localStorage.getItem('emp_id') +
          '&dep_name=' +
          this.loggedInDept
      )
      .subscribe((response) => {
        this.rights = response;
        this.isuser = this.rights[0].isuser;
        this.ischecker = this.rights[0].ischecker;
        this.isapprover = this.rights[0].isapprover;
        this.qms_approver = this.rights[0].qms_approver;
        this.dept_head = this.rights[0].dept_head;
        this.isauditor = this.rights[0].isauditor;
        this.plant_head = this.rights[0].plant_head;
        this.shift_allocator = this.rights[0].shift_allocator;
      });
  }
  //---------------------------------------------------------------------------------//

  getMaterialsLog() {
    this.loading = true;
    this.service
      .get(
        'rnd/qa/master/material.php?type=getMaterialsLog&material_type=' +
          encodeURIComponent(this.material_type || '') +
          '&material_subtype=' +
          encodeURIComponent(this.material_subtype || '') +
          '&status=' +
          encodeURIComponent(this.status || '')
      )
      .subscribe({
        next: (response) => {
          this.results = Array.isArray(response) ? response : [];
          this.loading = false;
        },
        error: () => {
          this.results = [];
          this.loading = false;
        }
      });
  }
  checkMaterialType(material) {
    if (material === 'Raw Material') {
      this.isRawMaterial = true;
      this.isPackingMaterial = false;
    } else if (material === 'Packing Material') {
      this.isRawMaterial = false;
      this.isPackingMaterial = true;
    } else if (material === 'Chemicals') {
      this.isRawMaterial = false;
      this.isPackingMaterial = false;
    } else {
      this.isRawMaterial = true;
      this.isPackingMaterial = false;
    }
    this.material_subtype = '';
    this.getMaterialsLog();
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
  }

  download(id) {
    this.service.open(
      'rnd/qa/master/material.php?type=materialmasterpdf&id=' + id
    );
  }

  downloadReport() {
    this.service.open(
      'rnd/qa/master/material.php?type=materialmasterlog&material_type=' +
        this.material_type +
        '&material_subtype=' +
        this.material_subtype +
        '&status=' +
        this.status
    );
  }
}
