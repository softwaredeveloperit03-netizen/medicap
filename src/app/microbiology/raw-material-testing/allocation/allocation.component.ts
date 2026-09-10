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
  isView = false;
  isProceed = false;
  employees;
  tests = [];
  batches =[];
  isDone = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getTestings();
  }

  getTestings() {
    this.service.get('qc/testing/raw.php?type=getPendingAllocationTestings').subscribe(response => {
      this.materials = response;
    });
  }

  viewForm(index) {
    this.selectedMaterial = this.materials[index];
    this.isView = true;
    if (this.selectedMaterial['isspecification'] == 'YES') {
      this.getTestingPersons();
    }
  }

  getTestingPersons() {
    this.service.get('employee.php?type=getQCExecutiveOfficers').subscribe(response => {
      this.employees = response;
    });
  }


  proceed(index){
    let bat = this.selectedMaterial['batches'];

  //  this.selectedMaterial = this.materials[index];
    this.tests = this.selectedMaterial['tests'];

    this.batches = bat[index];
    console.log('spec',this.batches);

    if (this.selectedMaterial['isspecification'] == 'YES') {
      this.getTestingPersons();
    }

    this.isProceed = true;
  }

  viewTest(index){
    let bat = this.selectedMaterial['batches'];

  //  this.selectedMaterial = this.materials[index];
    this.tests = this.selectedMaterial['tests'];

    this.batches = bat[index];
    console.log('spec',this.batches);

    if (this.selectedMaterial['isspecification'] == 'YES') {
      this.getTestingPersons();
    }

    this.isDone = true;
  }

  allocate(data) {
    if (!data.valid) {
      alertify.error('Testing person is required!');
      return;
    }
    let temp = data.value;
    this.service.post('qc/testing/raw.php?type=allocateTestingPerson&testing_no=' + this.selectedMaterial['testing_no'], JSON.stringify(this.selectedMaterial)).subscribe(response => {
    if (response['status'] == 'success') {
        alertify.success('Testing Person allocated successfully');
        this.isView = false;
        this.getTestings();
      } else {
        alertify.error('An error occured, ' + response['error']);
      }
    });
  }


  
  saveTest(data) {
    if (!data.valid) {
      alertify.error('Testing person is required!');
      return;
    }
    let temp = data.value;
    temp['batch_no'] = this.batches['batch_no'];
    temp['grn_no'] = this.batches['grn_no'];
  
    //allocateTestingPerson
      this.service.post('qc/testing/raw.php?type=allocateTestingPerson&testing_no=' + this.selectedMaterial['testing_no'], JSON.stringify(this.selectedMaterial)).subscribe(response => {
 
    if (response['status'] == 'success') {
        alertify.success('Testing Person allocated successfully');
        this.isProceed = false;
        this.getTestings();
        this.isDone = true;
      } else {
        alertify.error('An error occured, ' + response['error']);
      }
    });
  }



}
