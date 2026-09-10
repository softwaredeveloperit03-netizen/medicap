import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-request',
  templateUrl: './request.component.html',
  styleUrls: ['./request.component.css']
})
export class RequestComponent implements OnInit {

  isView = false;
  results;

  selectedSampling = [];
  employees;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getSamplingRecords();
  }

  getSamplingRecords() {
    this.service.get('qc/sampling/raw.php?type=getPendingAllocation').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedSampling = this.results[index];
    this.isView = true;
    this.getQcPersons();
  }

  getQcPersons() {
    this.service.get('qc/sampling/raw.php?type=getQcPersons').subscribe(response => {
      this.employees = response;
    });
  }

  allocatePerson(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    temp['id'] = this.selectedSampling['id'];
    temp['inword_no'] = this.selectedSampling['inword_no'];
    temp['grn_no'] = this.selectedSampling['grn_no'];
    temp['material_code'] = this.selectedSampling['material_code'];
    temp['batch_no'] = this.selectedSampling['batch_no'];
    this.service.post('qc/sampling/raw.php?type=allocatePerson&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response =>{
    //this.service.post('qc/sampling/raw.php?type=allocatePerson', JSON.stringify(temp)).subscribe(response => 
      if (response['status'] == 'success') {
        alertify.success('Sampling Person Allocated Successfully');
        this.isView = false;
        this.getSamplingRecords();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
