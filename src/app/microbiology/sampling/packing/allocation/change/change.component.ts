import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-change',
  templateUrl: './change.component.html',
  styleUrls: ['./change.component.css']
})
export class ChangeComponent implements OnInit {

 
  isView = false;
  results;
  employees;

  selectedSampling =[];
  constructor(private service:DataAccessService) { }

  ngOnInit(): void {
    this.getAllocationLog();
  }

  getAllocationLog(){
    this.service.get('qc/sampling/packing.php?type=getAllocationLog').subscribe(response => {
      this.results = response;
    });
  }

  view(index){
    this.selectedSampling = this.results[index];
    this.isView = true;
    this.getQcPersons();
  }

  getQcPersons() {
    this.service.get('employee.php?type=getQCPersons').subscribe(response => {
      this.employees = response;
    });
  }

  update(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;
    //temp['id'] = this.selectedSampling['id'];
    this.service.post('qc/sampling/packing.php?type=changePerson&id=' + this.selectedSampling['id'], JSON.stringify(temp)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('New Sampling Person Allocated Successfully!');
        this.isView = false;
        this.getAllocationLog();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
