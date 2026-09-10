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

  isAccept = false;
  isReject = false;
  constructor(private service: DataAccessService) { }

  ngOnInit() {
    this.getDispensingRequests();
  }

  getDispensingRequests() {
    this.service.get('store/dispensing/packing.php?type=getDispensingRequests').subscribe(response => {
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
    this.service.post('store/dispensing/packing.php?type=allocatePerson&id=' + this.selectedResult['id'] + '&person=' + this.person, JSON.stringify(dispensing)).subscribe(response => {
      if (response['status'] == 'success') {
        alertify.success('Person allocated successfully!');
        this.isView = false;
        this.getDispensingRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  reject(status) {
    this.service.get('store/dispensing.php?type=rejectDispensing&status=' + status + '&id=' + this.selectedResult['id']).subscribe(response => {
      if (response['status'] == 'success'){
        alertify.success('Dispensing ' + status + ' Successfully');
        this.isView = false;
        this.isAccept = false;
        this.isReject = false;
        this.getDispensingRequests();
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    });
  }

  checkList() {
    this.isReject = false;
    let materials = this.selectedResult['materials'];
    for (let i = 0; i < materials.length; i++) {
      let material = materials[i];
      material['diff'] = +material['physical_qty'] - +material['avl_qty'];
      if (material['diff'] < 0) {
        this.isReject = true;
      }
    }

    if (this.isReject == false) {
      this.isAccept = true;
    }
  }

}
