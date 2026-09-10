import { Component, OnInit } from '@angular/core';
import { Router } from '@angular/router';
import { DataAccessService } from 'src/app/data-access.service';
declare let alertify;

@Component({
  selector: 'app-new',
  templateUrl: './new.component.html',
  styleUrls: ['./new.component.css']
})
export class NewComponent implements OnInit {

  affecting_product = 'yes';
  affecting_equipment = 'no';

  departments = [
    { name: 'Store', status: false},
    { name: 'Quality Control', status: false},
    { name: 'Production', status: false},
    { name: 'Packing', status: false},
    { name: 'Engineering', status: false}
  ];

  constructor(private service: DataAccessService, private router: Router) { }

  ngOnInit() {
  }

  updateDept(value, i) {
    this.departments[i].status = value;
  }

  saveDeviation(data) {
    if (!data.valid) {
      alertify.error('All fields are required');
      return;
    }
    let temp = data.value;

    let test = [];
    for (let i = 0; i < this.departments.length; i++) {
      let department = this.departments[i];
      if (department['status']) {
        test[test.length] = department['name'];
      }
    }

    temp['departmentlist'] = test;
    this.service.post('deviation.php?type=saveDeviations', JSON.stringify(temp)).subscribe(response => {
      if(response['status'] == 'success'){
        this.router.navigate(['/qa/deviation']);
        alertify.success('Record Inserted Successfully');
      } else {
        alertify.error('Failed: An error occured, please try again!');
      }
    })
  }

}
