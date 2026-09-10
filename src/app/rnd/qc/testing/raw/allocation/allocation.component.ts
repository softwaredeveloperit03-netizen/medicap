import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {

  materials;
  selectedMaterial;
  isViewForm = false;
  // isUser = false;
  // isChecker = false;
  // isApprover = false;

  employees;
  labs;

  microPerson;
  constructor(private service: DataAccessService) {
    // this.isUser = Boolean(JSON.parse(localStorage.getItem('user')));
    // this.isChecker = Boolean(JSON.parse(localStorage.getItem('checker')));
    // this.isApprover = Boolean(JSON.parse(localStorage.getItem('approver')));
  }

  ngOnInit() {
    this.getTestings();
    this.getMicroPerson();
  }

  getTestings() {
    this.service.get('qc/testing/raw.php?type=getPendingAllocationTestings').subscribe(response => {
      this.materials = response;
    });
  }

  viewForm(index) {
    this.selectedMaterial = this.materials[index];
    this.isViewForm = true;
    this.getTestingPersons();
    this.getLabs();
  }

  getTestingPersons() {
    this.service.get('common.php?type=getTestingPersons').subscribe(response => {
      this.employees = response;
    });
  }

  getLabs() {
    this.service.get('common.php?type=getLabs').subscribe(response => {
      this.labs = response;
    });
  }

  getMicroPerson(){
    this.service.get('common.php?type=getMicroPersons').subscribe(response => {
      this.microPerson = response;
    });
  }

  allocateTestingPerson() {
    this.service.post('qc/testing/raw.php?type=allocateTestingPerson&testing_no=' + this.selectedMaterial['testing_no'], JSON.stringify(this.selectedMaterial)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Testing Person allocated successfully');
        this.isViewForm = false;
        this.getTestings();
      } else {
        alertify.error('An error occured, ' + response['error']);
      }
    });
  }

  updateTests(index, action, value) {
    let test = this.selectedMaterial['spec_tests'];
    if (action === 'outside') {
      test[index].isoutside = value;
    } else if (action === 'person') {
      test[index].person = value;
    }else if (action === 'lab') {
      test[index].lab_name = value;
    }
    this.selectedMaterial['spec_tests'] = test;
  }

}
