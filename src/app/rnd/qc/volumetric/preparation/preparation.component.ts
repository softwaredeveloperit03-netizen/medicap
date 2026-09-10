import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-preparation',
  templateUrl: './preparation.component.html',
  styleUrls: ['./preparation.component.css'],
})
export class PreparationComponent implements OnInit {
  isView = false;
  results;
  materials;
  solutions;
  constructor(private service: DataAccessService) {  this.loggedInDept = localStorage.getItem('department');}

  ngOnInit() {
    this.getVolumetricPreparation();
    this.getMaterials();
    this.getVolumetricSolutions();
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
  getVolumetricPreparation() {
    this.service
      .get('qc/volumetric.php?type=getVolumetricPreparation')
      .subscribe((response) => {
        this.results = response;
      });
  }

  getMaterials() {
    this.service
      .get('qc/volumetric.php?type=getMaterials')
      .subscribe((response) => {
        this.materials = response;
      });
  }

  getVolumetricSolutions() {
    this.service
      .get('qc/volumetric.php?type=getVolumetricMaster')
      .subscribe((response) => {
        this.solutions = response;
      });
  }

  saveVolumetricPreparation(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    this.service
      .post(
        'qc/volumetric.php?type=saveVolumetricPreparation',
        JSON.stringify(data.value)
      )
      .subscribe((response) => {
        if (response['status'] == 'success') {
          alertify.success(this.service.t('common.savedSuccess'));
          this.getVolumetricPreparation();
          this.isView = false;
        } else {
          alertify.error('An error occured, Please try again!');
        }
      });
  }
}
