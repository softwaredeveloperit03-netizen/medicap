import { Component, OnInit } from '@angular/core';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;
@Component({
  selector: 'app-allocation',
  templateUrl: './allocation.component.html',
  styleUrls: ['./allocation.component.css']
})
export class AllocationComponent implements OnInit {

  isView = false;
  results;

  selectedResult = [];
  employees;

  person = '';
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDispensingRequests();
  }

  getDispensingRequests() {
    this.service.get('store/dispensing.php?type=getDispensingRequests').subscribe(response => {
      this.results = response;
    });
  }

  view(index) {
    this.selectedResult = this.results[index];
    this.isView = true;
    this.getStorePersons();
  }

  getStorePersons() {
    this.service.get('employee.php?type=getStorePersons').subscribe(response => {
      this.employees = response;
    });
  }

  allocate() {
    let dispensing = this.selectedResult['data'];
    this.service.post('store/dispensing.php?type=allocatePerson&id=' + this.selectedResult['id'] + '&person=' + this.person, JSON.stringify(dispensing)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Person allocated successfully!');
        this.isView = false;
        this.getDispensingRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

}
